<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PhotoOrderDispatchService
{
    public function sync(Order $order): int
    {
        if ((int) $order->is_photo_order !== 1) {
            return 0;
        }

        $order->loadMissing('profofPictures');
        $mediaItems = $this->collectPhotoMedia($order);
        $mediaIds = $mediaItems->pluck('id')->all();

        foreach ($mediaItems as $index => $media) {
            $pickup = is_array($order->pickup_point) ? $order->pickup_point : [];
            $delivery = is_array($order->delivery_point) ? $order->delivery_point : [];
            // Photo orders: Admin fills Customer Name/Phone/Address from the photo — do not auto-fill.
            $remark = stripAutoOrderRemarkTip($delivery['instruction'] ?? $pickup['instruction'] ?? '');
            $payment = is_array($delivery['payment'] ?? null) ? $delivery['payment'] : [];
            $collectMoney = (int) ($payment['collect_money'] ?? 0) === 1;
            $creditToRaw = $payment['credit_to'] ?? 'customer';
            $creditTo = in_array($creditToRaw, ['os', 'customer'], true) ? $creditToRaw : 'customer';
            $itemValue = $collectMoney ? (float) ($payment['item_value'] ?? 0) : 0;
            $deliAmount = $collectMoney ? (float) ($payment['deli_amount'] ?? 0) : 0;
            $osPaid = $collectMoney && $creditTo === 'os' ? (float) ($payment['os_paid'] ?? 0) : 0;
            $amounts = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, $osPaid, $creditTo);

            $item = DispatchOrderItem::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'photo_id' => $media->id,
                ],
                [
                    'received_date' => $order->pickup_datetime ?? $order->created_at ?? now(),
                    'status' => 'collected',
                    'code' => DispatchOrderItem::generateCode(),
                    'item_name' => $media->file_name ?? ('Photo ' . ($index + 1)),
                    'customer_name' => '',
                    'customer_phone' => '',
                    'customer_address' => '',
                    'remark' => $remark,
                    'delivery_city' => defaultDeliveryRouteForBranch()['city']
                        ?: config('dispatch_item_cities.default_delivery_city', 'Mandalay'),
                    'township' => defaultDeliveryRouteForBranch()['township']
                        ?: config('dispatch_item_cities.default_township', 'ချမ်းမြသာစည်'),
                    'credit_to' => $creditTo,
                    'item_value' => $itemValue,
                    'deli_amount' => $deliAmount,
                    'advance_paid' => 0,
                    'os_paid' => $osPaid,
                    'cust_get' => $amounts['cust_get'],
                    'os_to_pay' => $amounts['os_to_pay'],
                ]
            );

            // Backfill township/city only. Never re-apply User App payment onto
            // existing parcels — rider/admin may already have updated amounts.
            if ($item->wasRecentlyCreated === false) {
                $patch = [];
                if (trim((string) ($item->remark ?? '')) === '' && $remark !== '') {
                    $patch['remark'] = $remark;
                }
                if (trim((string) ($item->township ?? '')) === '') {
                    $patch['township'] = defaultDeliveryRouteForBranch()['township']
                        ?: config('dispatch_item_cities.default_township', 'ချမ်းမြသာစည်');
                }
                if (trim((string) ($item->delivery_city ?? '')) === '') {
                    $patch['delivery_city'] = defaultDeliveryRouteForBranch()['city']
                        ?: config('dispatch_item_cities.default_delivery_city', 'Mandalay');
                }
                // Seed User App money-collect only when still blank AND rider has not set pay mode.
                if ($collectMoney
                    && empty($item->pickup_pay_mode)
                    && (float) ($item->item_value ?? 0) == 0
                    && (float) ($item->deli_amount ?? 0) == 0
                    && (float) ($item->cust_get ?? 0) == 0
                    && (float) ($item->os_paid ?? 0) == 0) {
                    $patch['item_value'] = $itemValue;
                    $patch['deli_amount'] = $deliAmount;
                    $patch['credit_to'] = $creditTo;
                    $patch['os_paid'] = $osPaid;
                    $patch['cust_get'] = $amounts['cust_get'];
                    $patch['os_to_pay'] = $amounts['os_to_pay'];
                }
                if ($patch !== []) {
                    $item->forceFill($patch)->save();
                }
            }
        }

        // Never delete collected items once a pickup rider is assigned / pickup started.
        // Rider may replace photo_id with a pickup-proof media id; orphan cleanup would
        // soft-delete those rows and firstOrCreate would recreate User App payment values.
        $pickupStarted = ! empty($order->delivery_man_id)
            || in_array($order->status, [
                'courier_assigned',
                'courier_arrived',
                'courier_picked_up',
                'courier_departed',
                'completed',
                'active',
            ], true);

        if ($mediaIds !== [] && ! $pickupStarted) {
            DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('photo_id', '>', 0)
                ->whereNotIn('photo_id', $mediaIds)
                ->where('status', 'collected')
                ->where(function ($query) {
                    $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
                })
                ->where(function ($query) {
                    $query->whereNull('pickup_pay_mode')->orWhere('pickup_pay_mode', '');
                })
                ->delete();
        }

        $this->syncOrderTotals($order->fresh());

        return count($mediaIds);
    }

    public function collectPhotoMedia(Order $order): Collection
    {
        $images = collect();

            foreach ($order->profofPictures as $picture) {
            // Rider pickup proofs must not create extra photo-order panels.
            $type = (string) ($picture->type ?? '');
            if ($type === 'pickup_time' || $type === 'gate_pass' || str_starts_with($type, 'recipient_')) {
                continue;
            }

            foreach ($picture->getMedia('prof_file') as $file) {
                if ($this->isImage($file)) {
                    $images->push($file);
                }
            }
        }

        foreach ($order->getMedia('order_photo') as $file) {
            if ($this->isImage($file)) {
                $images->push($file);
            }
        }

        return $images->unique('id')->values();
    }

    public function ensurePickUpState(Order $order): void
    {
        if ((int) $order->is_photo_order !== 1) {
            return;
        }

        if ($order->status === 'pending' && $order->payment_type === 'online') {
            return;
        }

        if (!empty($order->delivery_man_id) || in_array($order->status, [
            'courier_assigned',
            'courier_arrived',
            'courier_picked_up',
            'courier_departed',
            'active',
            'completed',
        ], true)) {
            return;
        }

        if ($order->status !== 'create') {
            $order->update(['status' => 'create']);
        }
    }

    public function findPhotoMedia(int $photoId): ?Media
    {
        if ($photoId <= 0) {
            return null;
        }

        return Media::query()->find($photoId);
    }

    private function isImage(Media $file): bool
    {
        return str_starts_with((string) ($file->mime_type ?? ''), 'image/');
    }

    private function syncOrderTotals(Order $order): void
    {
        $collectedCount = $order->dispatchItems()->where('status', 'collected')->count();
        $imageCount = $this->collectPhotoMedia($order)->count();

        $order->update([
            'total_parcel' => 1,
            'total_amount' => (float) $order->dispatchItems()->sum('deli_amount'),
            'is_photo_order' => 1,
        ]);
    }
}
