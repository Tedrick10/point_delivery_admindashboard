<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Services\DispatchOrderAuditService;
use App\Services\DispatchOrderWorkflowService;

class PickupParcelDispatchService
{
    public function isDispatchPickupOrder(Order $order): bool
    {
        return (int) ($order->is_photo_order ?? 0) === 1
            || (int) ($order->is_text_order ?? 0) === 1
            || (int) ($order->is_shop_order ?? 0) === 1
            || (int) ($order->is_gate_order ?? 0) === 1;
    }

    public function assignPickupRider(Order $order, int $riderId): Order
    {
        $wasPickupError = ($order->status ?? '') === 'pickup_error';

        if ($wasPickupError && ! in_array($order->pickup_error_choice, ['express', 'next_day'], true)) {
            throw new \InvalidArgumentException(__('message.pickup_error_rider_assign_requires_choice'));
        }

        $order->update([
            'delivery_man_id' => $riderId,
            'status' => 'courier_assigned',
            'assign_datetime' => now(),
        ]);

        $order = $order->fresh();

        if ($this->isDispatchPickupOrder($order)) {
            $this->ensureDispatchItems($order);
            $order = $order->fresh();
            if ((int) ($order->is_text_order ?? 0) === 1) {
                $hasItems = DispatchOrderItem::query()
                    ->where('order_id', $order->id)
                    ->where('status', 'collected')
                    ->exists();
                if (! $hasItems) {
                    app(TextOrderDispatchService::class)->seedBlankParcels($order);
                }
            }
        }

        if ($wasPickupError) {
            DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('status', 'collected')
                ->update(['delivery_man_id' => $riderId]);

            DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->whereIn('status', ['assigned', 'courier_assigned'])
                ->update([
                    'delivery_man_id' => $riderId,
                    'status' => 'courier_assigned',
                ]);
        }

        saveOrderHistory([
            'history_type' => 'courier_assigned',
            'order_id' => $order->id,
            'order' => $order,
        ]);

        $rider = \App\Models\User::find($riderId);
        if ($rider) {
            app(DispatchOrderAuditService::class)->logPickupRiderAssigned($order, $rider);
        }

        app(DispatchOrderWorkflowService::class)->syncOrderWorkflow($order);

        return $order->fresh();
    }

    public function ensureAssignedForPickup(Order $order): Order
    {
        if (empty($order->delivery_man_id)) {
            return $order;
        }

        if (in_array($order->status, ['courier_picked_up', 'courier_departed', 'completed', 'cancelled'], true)) {
            return $order;
        }

        if ($order->status === 'courier_assigned') {
            return $order;
        }

        return $this->assignPickupRider($order, (int) $order->delivery_man_id);
    }

    public function ensureDispatchItems(Order $order): void
    {
        // Once collected parcels exist, never create/delete mid-pickup.
        // Rider photo uploads set photo_id > 0; old text-order sync treated those
        // as "missing" and created duplicate panels on every upload.
        $hasCollectedItems = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->exists();

        if ($hasCollectedItems) {
            return;
        }

        if ((int) ($order->is_photo_order ?? 0) === 1) {
            app(PhotoOrderDispatchService::class)->sync($order->fresh());
            return;
        }

        if ((int) ($order->is_text_order ?? 0) === 1) {
            // Do not auto-seed blanks here — empty text orders are filled by the client.
            // Rider assignment seeds blanks only when still empty at pickup time.
            app(TextOrderDispatchService::class)->ensureTextOrderItem($order->fresh());
            return;
        }

        if (app(TextOrderDispatchService::class)->supports($order)) {
            app(TextOrderDispatchService::class)->sync($order->fresh());
        }
    }

    public function dispatchItemCount(Order $order): int
    {
        if (! $this->isDispatchPickupOrder($order)) {
            return max(1, (int) $order->total_parcel);
        }

        $this->ensureDispatchItems($order->fresh());
        $order = $order->fresh();

        $statuses = app(DispatchOrderWorkflowService::class)->clientVisibleItemStatuses();

        $itemCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->whereIn('status', $statuses)
            ->count();

        if ($itemCount > 0) {
            return $itemCount;
        }

        return max(1, (int) $order->total_parcel);
    }

    public function requiresRiderPickupPhotos(Order $order): bool
    {
        if (! $this->isDispatchPickupOrder($order)) {
            return false;
        }

        // Client already uploaded parcel photos when placing a photo order.
        if ((int) ($order->is_photo_order ?? 0) === 1) {
            return false;
        }

        $this->ensureDispatchItems($order->fresh());

        return DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->where('photo_id', 0)->orWhereNull('photo_id');
            })
            ->exists();
    }

    public function requiredPickupPhotoCount(Order $order): int
    {
        if (! $this->requiresRiderPickupPhotos($order)) {
            return 0;
        }

        $this->ensureDispatchItems($order->fresh());
        $order = $order->fresh();

        $itemsNeedingPhoto = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->where('photo_id', 0)->orWhereNull('photo_id');
            })
            ->count();

        if ($itemsNeedingPhoto > 0) {
            return $itemsNeedingPhoto;
        }

        return $this->dispatchItemCount($order);
    }

    public function attachPickupPhotos(Order $order, array $mediaIds): int
    {
        $mediaIds = array_values(array_filter(array_map('intval', $mediaIds), fn ($id) => $id > 0));

        if ($mediaIds === []) {
            return 0;
        }

        $this->ensureDispatchItems($order->fresh());
        $order = $order->fresh();

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->where('photo_id', 0)->orWhereNull('photo_id');
            })
            ->orderBy('id')
            ->get();

        $mapped = 0;

        foreach ($items as $index => $item) {
            if (! isset($mediaIds[$index])) {
                break;
            }

            $item->forceFill(['photo_id' => $mediaIds[$index]])->save();
            $mapped++;
        }

        return $mapped;
    }

    public function allCollectedItemsHavePickupPhotos(Order $order): bool
    {
        if (! $this->isDispatchPickupOrder($order)) {
            return true;
        }

        $this->ensureDispatchItems($order);

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->get();

        if ($items->isEmpty()) {
            return false;
        }

        // Rider-added extras always need a pickup photo, even on photo orders.
        $extrasNeedPhoto = $items->filter(fn (DispatchOrderItem $item) => ($item->remark ?? '') === 'rider_added')
            ->contains(fn (DispatchOrderItem $item) => (int) $item->photo_id <= 0);

        if ($extrasNeedPhoto) {
            return false;
        }

        if (! $this->requiresRiderPickupPhotos($order)) {
            return $this->dispatchItemCount($order) > 0;
        }

        return $items->every(fn (DispatchOrderItem $item) => (int) $item->photo_id > 0);
    }

    public function attachPickupPhotoToItem(Order $order, int $itemId, int $mediaId): bool
    {
        if ($mediaId <= 0 || $itemId <= 0) {
            return false;
        }

        // Do not call ensureDispatchItems here — sync can recreate items and change IDs.
        $item = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $itemId)
            ->where('status', 'collected')
            ->first();

        if (! $item) {
            return false;
        }

        // Photo-order base parcels already point at the client's uploaded image.
        // Overwriting photo_id with a pickup-proof media id made Admin sync treat
        // the row as an orphan and recreate it with the original User App payment.
        $isPhotoOrder = (int) ($order->is_photo_order ?? 0) === 1;
        $isRiderExtra = ($item->remark ?? '') === 'rider_added';
        if ($isPhotoOrder && ! $isRiderExtra && (int) ($item->photo_id ?? 0) > 0) {
            return true;
        }

        $item->forceFill(['photo_id' => $mediaId])->save();

        return true;
    }

    public function allCollectedItemsHaveSize(Order $order): bool
    {
        if (! $this->isDispatchPickupOrder($order)) {
            return true;
        }

        $this->ensureDispatchItems($order);

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->get();

        if ($items->isEmpty()) {
            return false;
        }

        return $items->every(fn (DispatchOrderItem $item) => (float) ($item->weight ?? 0) > 0);
    }

    public function itemRequiresPickupPhoto(Order $order, DispatchOrderItem $item): bool
    {
        if (($item->remark ?? '') === 'rider_added') {
            return (int) ($item->photo_id ?? 0) <= 0;
        }

        if ((int) ($order->is_photo_order ?? 0) === 1) {
            return false;
        }

        return (int) ($item->photo_id ?? 0) <= 0;
    }

    public function inferPickupPayMode(DispatchOrderItem $item): string
    {
        $stored = (string) ($item->pickup_pay_mode ?? '');
        if (in_array($stored, ['os_pay', 'customer_pay', 'pay_done'], true)) {
            return $stored;
        }

        if ((float) ($item->os_paid ?? 0) > 0) {
            return 'pay_done';
        }

        return (($item->credit_to ?? 'customer') === 'os') ? 'os_pay' : 'customer_pay';
    }

    /**
     * Apply rider pickup payment fields onto an item (same rules as delivery app).
     */
    public function applyPickupItemPayment(DispatchOrderItem $item, string $payMode, float $deliAmount, float $itemValue): array
    {
        if (! in_array($payMode, ['os_pay', 'customer_pay', 'pay_done'], true)) {
            $payMode = $this->inferPickupPayMode($item);
        }

        if ($payMode === 'pay_done') {
            $creditTo = 'os';
            $osPaid = $deliAmount;
        } elseif ($payMode === 'os_pay') {
            $creditTo = 'os';
            $osPaid = 0;
        } else {
            $creditTo = 'customer';
            $osPaid = 0;
        }

        $amounts = DispatchOrderItem::computeAmounts(
            $itemValue,
            $deliAmount,
            (float) ($item->advance_paid ?? 0),
            $osPaid,
            $creditTo
        );

        return [
            'item_value' => $itemValue,
            'deli_amount' => $deliAmount,
            'credit_to' => $creditTo,
            'os_paid' => $osPaid,
            'pickup_pay_mode' => $payMode,
            'cust_get' => $amounts['cust_get'],
            'os_to_pay' => $amounts['os_to_pay'],
        ];
    }

    public function pickupProofImageUrls(Order $order): array
    {
        if (! $this->isDispatchPickupOrder($order)) {
            return [];
        }

        $order->loadMissing(['profofPictures']);

        $urls = [];

        foreach ($order->profofPictures as $picture) {
            if (! in_array($picture->type, ['pickup_time', 'photo_order'], true)) {
                continue;
            }

            foreach ($picture->getMedia('prof_file') as $media) {
                if (getFileExistsCheck($media)) {
                    $urls[] = mediaAbsoluteUrl($media);
                }
            }
        }

        foreach ($order->getMedia('order_photo') as $media) {
            if (getFileExistsCheck($media)) {
                    $urls[] = mediaAbsoluteUrl($media);
            }
        }

        $this->ensureDispatchItems($order->fresh());
        $photoService = app(PhotoOrderDispatchService::class);

        $statuses = app(DispatchOrderWorkflowService::class)->clientVisibleItemStatuses();

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->whereIn('status', $statuses)
            ->where('photo_id', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($items as $item) {
            $media = $photoService->findPhotoMedia((int) $item->photo_id);
            if ($media && getFileExistsCheck($media)) {
                    $urls[] = mediaAbsoluteUrl($media);
            }
        }

        return array_values(array_unique($urls));
    }

    public function gatePassImageUrls(Order $order): array
    {
        if ((int) ($order->is_gate_order ?? 0) !== 1) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (array $image) => $image['url'] ?? null,
            collectGatePassImages($order)
        )));
    }
}
