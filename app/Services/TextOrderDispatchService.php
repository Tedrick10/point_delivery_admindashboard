<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use Carbon\Carbon;

class TextOrderDispatchService
{
    private const ADVANCED_STATUSES = ['assigned', 'courier_assigned', 'courier_departed', 'completed'];

    public function supports(Order $order): bool
    {
        return (int) ($order->is_text_order ?? 0) === 1
            || (int) ($order->is_shop_order ?? 0) === 1
            || (int) ($order->is_gate_order ?? 0) === 1;
    }

    public function isTextOrder(Order $order): bool
    {
        return (int) ($order->is_text_order ?? 0) === 1;
    }

    public function hasAdvancedParcelItems(Order $order): bool
    {
        return DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('photo_id', 0)
            ->whereIn('status', self::ADVANCED_STATUSES)
            ->exists();
    }

    public function ensureTextOrderItem(Order $order, ?array $itemData = null): int
    {
        if (! $this->isTextOrder($order)) {
            return 0;
        }

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->get();

        // Text orders may have many client/admin-added items — never trim them.
        if ($items->isNotEmpty()) {
            $this->normalizeOrderCount($order->fresh());

            return $items->count();
        }

        // Empty text orders stay empty until client/admin adds items,
        // unless the caller explicitly provides item payload.
        if ($itemData !== null) {
            return $this->syncWithItemData($order, $itemData, 1);
        }

        return 0;
    }

    /**
     * Seed blank collected slots from total_parcel (for rider pickup when no items yet).
     */
    public function seedBlankParcels(Order $order): int
    {
        if (! $this->isTextOrder($order)) {
            return 0;
        }

        $existing = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->count();

        if ($existing > 0) {
            return $existing;
        }

        $targetCount = max(1, (int) $order->total_parcel);
        $payload = $this->normalizeItemPayload($this->itemDataFromOrder($order));
        $amounts = DispatchOrderItem::computeAmounts(
            (float) ($payload['item_value'] ?? 0),
            (float) ($payload['deli_amount'] ?? 0),
            (float) ($payload['advance_paid'] ?? 0),
            (float) ($payload['os_paid'] ?? 0),
            (string) ($payload['credit_to'] ?? 'customer')
        );
        $receivedDate = $this->parseReceivedDate($payload['received_date'] ?? null, $order);
        $itemPrefix = $this->itemPrefix($order);

        for ($i = 1; $i <= $targetCount; $i++) {
            DispatchOrderItem::create(array_merge($payload, $amounts, [
                'order_id' => $order->id,
                'photo_id' => 0,
                'received_date' => $receivedDate,
                'status' => 'collected',
                'code' => DispatchOrderItem::generateCode(),
                'item_name' => $itemPrefix . ' ' . $i,
            ]));
        }

        $this->normalizeOrderCount($order->fresh());

        return $targetCount;
    }

    public function syncWithItemData(Order $order, array $itemData, ?int $itemCount = null): int
    {
        if (! $this->supports($order)) {
            return 0;
        }

        $existingCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->count();

        // Never wipe multi-item text orders (client/admin add-item flow).
        if ($this->isTextOrder($order) && $existingCount > 0) {
            $this->normalizeOrderCount($order->fresh());

            return $existingCount;
        }

        if ($this->hasAdvancedParcelItems($order)) {
            $this->normalizeOrderCount($order->fresh());

            return DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('status', 'collected')
                ->count();
        }

        $targetCount = max(1, $itemCount ?? 1);
        $normalized = $this->normalizeItemPayload($itemData);
        $amounts = DispatchOrderItem::computeAmounts(
            (float) ($normalized['item_value'] ?? 0),
            (float) ($normalized['deli_amount'] ?? 0),
            (float) ($normalized['advance_paid'] ?? 0),
            (float) ($normalized['os_paid'] ?? 0),
            (string) ($normalized['credit_to'] ?? 'customer')
        );

        // Replace blank base slots only — never wipe rider-added extras.
        DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('photo_id', 0)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->whereNull('remark')->orWhere('remark', '!=', 'rider_added');
            })
            ->delete();

        $receivedDate = $this->parseReceivedDate($normalized['received_date'] ?? null, $order);
        $itemPrefix = $this->itemPrefix($order);

        for ($i = 1; $i <= $targetCount; $i++) {
            $itemName = trim((string) ($normalized['item_name'] ?? ''));
            if ($targetCount > 1) {
                $itemName = $itemName !== '' ? $itemName . " ($i)" : $itemPrefix . ' ' . $i;
            } elseif ($itemName === '') {
                $itemName = $itemPrefix . ' 1';
            }

            DispatchOrderItem::create(array_merge($normalized, $amounts, [
                'order_id' => $order->id,
                'photo_id' => 0,
                'received_date' => $receivedDate,
                'status' => 'collected',
                'code' => DispatchOrderItem::generateCode(),
                'item_name' => $itemName,
            ]));
        }

        $this->normalizeOrderCount($order->fresh());

        return $targetCount;
    }

    public function sync(Order $order, ?int $itemCount = null): int
    {
        if ($this->isTextOrder($order)) {
            return $this->ensureTextOrderItem($order);
        }

        if (! $this->supports($order)) {
            return 0;
        }

        if ($this->isMultiRecipientOrder($order) && ! empty($this->recipientsFromOrder($order))) {
            return $this->syncMultiRecipientItems($order);
        }

        if ($this->hasAdvancedParcelItems($order)) {
            $this->normalizeOrderCount($order->fresh());

            return DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('photo_id', 0)
                ->where('status', 'collected')
                ->count();
        }

        $targetCount = $this->resolveTargetCount($order, $itemCount);
        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->get();

        $currentCount = $items->count();
        $itemPrefix = $this->itemPrefix($order);

        if ($currentCount > $targetCount) {
            $toDelete = $currentCount - $targetCount;
            foreach ($items->reverse() as $item) {
                if ($toDelete <= 0) {
                    break;
                }
                if (($item->remark ?? '') === 'rider_added') {
                    continue;
                }
                $item->delete();
                $toDelete--;
            }
        } elseif ($currentCount < $targetCount) {
            $payload = $this->normalizeItemPayload($this->itemDataFromOrder($order));
            $amounts = DispatchOrderItem::computeAmounts(
                (float) ($payload['item_value'] ?? 0),
                (float) ($payload['deli_amount'] ?? 0),
                (float) ($payload['advance_paid'] ?? 0),
                (float) ($payload['os_paid'] ?? 0),
                (string) ($payload['credit_to'] ?? 'customer')
            );
            $receivedDate = $this->parseReceivedDate($payload['received_date'] ?? null, $order);

            for ($i = $currentCount + 1; $i <= $targetCount; $i++) {
                $itemName = trim((string) ($payload['item_name'] ?? ''));
                if ($itemName === '') {
                    $itemName = $itemPrefix . ' ' . $i;
                } elseif ($targetCount > 1) {
                    $itemName = $itemName . ' (' . $i . ')';
                }

                DispatchOrderItem::create(array_merge($payload, $amounts, [
                    'order_id' => $order->id,
                    'photo_id' => 0,
                    'received_date' => $receivedDate,
                    'status' => 'collected',
                    'code' => DispatchOrderItem::generateCode(),
                    'item_name' => $itemName,
                ]));
            }
        }

        // Backfill customer fields on existing empty slots (legacy blank parcels).
        $this->backfillCustomerFieldsIfEmpty($order->fresh());
        $this->applyOrderPaymentToCollectedItems($order->fresh());

        $this->normalizeOrderCount($order->fresh());

        return $targetCount;
    }

    /**
     * Apply order-level collect-money amounts to collected text/shop/gate parcels.
     */
    public function applyOrderPaymentToCollectedItems(Order $order): void
    {
        if (! $this->supports($order) || $this->isMultiRecipientOrder($order)) {
            return;
        }

        $delivery = is_array($order->delivery_point) ? $order->delivery_point : [];
        $payment = is_array($delivery['payment'] ?? null) ? $delivery['payment'] : [];
        if ((int) ($payment['collect_money'] ?? 0) !== 1) {
            return;
        }

        $fields = $this->paymentFieldsFromSource($payment);
        $amounts = DispatchOrderItem::computeAmounts(
            (float) ($fields['item_value'] ?? 0),
            (float) ($fields['deli_amount'] ?? 0),
            (float) ($fields['advance_paid'] ?? 0),
            (float) ($fields['os_paid'] ?? 0),
            (string) ($fields['credit_to'] ?? 'customer')
        );

        DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->where(function ($query) {
                $query->where('photo_id', 0)->orWhereNull('photo_id');
            })
            ->where(function ($query) {
                $query->whereNull('pickup_pay_mode')->orWhere('pickup_pay_mode', '');
            })
            ->where(function ($query) {
                $query->where('item_value', 0)->orWhereNull('item_value');
            })
            ->where(function ($query) {
                $query->where('deli_amount', 0)->orWhereNull('deli_amount');
            })
            ->get()
            ->each(function (DispatchOrderItem $item) use ($fields, $amounts) {
                $item->forceFill(array_merge($fields, $amounts))->save();
            });
    }

    public function isMultiRecipientOrder(Order $order): bool
    {
        if ((int) ($order->is_self_order ?? 1) === 1) {
            return false;
        }

        return (int) ($order->is_shop_order ?? 0) === 1
            || (int) ($order->is_gate_order ?? 0) === 1;
    }

    public function recipientsFromOrder(Order $order): array
    {
        $delivery = is_array($order->delivery_point) ? $order->delivery_point : [];
        $recipients = $delivery['recipients'] ?? [];

        return is_array($recipients) ? array_values($recipients) : [];
    }

    public function sumRecipientParcels(array $recipients): int
    {
        $total = 0;

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $total += max(1, (int) ($recipient['parcel_count'] ?? 1));
        }

        return max(1, $total);
    }

    public function syncMultiRecipientItems(Order $order): int
    {
        if (! $this->supports($order) || ! $this->isMultiRecipientOrder($order)) {
            return 0;
        }

        if ($this->hasAdvancedParcelItems($order)) {
            $this->normalizeOrderCount($order->fresh());

            return DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('photo_id', 0)
                ->where('status', 'collected')
                ->count();
        }

        $recipients = $this->recipientsFromOrder($order);
        if (empty($recipients)) {
            return 0;
        }

        // Keep existing collected items during pickup (photo/size updates).
        // Delete+recreate would change IDs and break rider uploads mid-flow.
        $existingItems = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->get();

        if ($existingItems->isNotEmpty()) {
            $this->backfillMultiRecipientCustomerFields($order, $existingItems, $recipients);
            $this->normalizeOrderCount($order->fresh());

            return $existingItems->count();
        }

        $basePayload = $this->normalizeItemPayload($this->itemDataFromOrder($order));
        $receivedDate = $this->parseReceivedDate($basePayload['received_date'] ?? null, $order);
        $itemPrefix = $this->itemPrefix($order);

        DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('photo_id', 0)
            ->where('status', 'collected')
            ->delete();

        $itemNumber = 1;

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $parcelCount = max(1, (int) ($recipient['parcel_count'] ?? 1));
            $customerName = trim((string) ($recipient['name'] ?? ''));
            $customerPhone = normalizeContactNumber(trim((string) ($recipient['contact_number'] ?? '')));
            $customerAddress = trim((string) ($recipient['address'] ?? ''));

            for ($p = 0; $p < $parcelCount; $p++) {
                $payload = array_merge($basePayload, [
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'customer_address' => $customerAddress,
                    'item_name' => $itemPrefix.' '.$itemNumber,
                ], $this->paymentFieldsFromSource($recipient));

                $amounts = DispatchOrderItem::computeAmounts(
                    (float) ($payload['item_value'] ?? 0),
                    (float) ($payload['deli_amount'] ?? 0),
                    (float) ($payload['advance_paid'] ?? 0),
                    (float) ($payload['os_paid'] ?? 0),
                    (string) ($payload['credit_to'] ?? 'customer')
                );

                DispatchOrderItem::create(array_merge($payload, $amounts, [
                    'order_id' => $order->id,
                    'photo_id' => 0,
                    'received_date' => $receivedDate,
                    'status' => 'collected',
                    'code' => DispatchOrderItem::generateCode(),
                ]));

                $itemNumber++;
            }
        }

        $createdCount = max(0, $itemNumber - 1);
        $this->normalizeOrderCount($order->fresh());

        return $createdCount;
    }

    /**
     * Attach uploaded recipient photos to dispatch items for one delivery address.
     * Photos are mapped 1:1 onto that recipient's parcel slots (by order id).
     */
    public function attachRecipientPhotos(Order $order, int $recipientIndex, array $mediaIds): int
    {
        $mediaIds = array_values(array_filter(array_map('intval', $mediaIds), fn ($id) => $id > 0));
        if ($mediaIds === [] || $recipientIndex < 0) {
            return 0;
        }

        $this->sync($order->fresh());
        $order = $order->fresh();

        $recipients = $this->recipientsFromOrder($order);
        if (! isset($recipients[$recipientIndex]) || ! is_array($recipients[$recipientIndex])) {
            return 0;
        }

        $offset = 0;
        for ($i = 0; $i < $recipientIndex; $i++) {
            if (! is_array($recipients[$i] ?? null)) {
                continue;
            }
            $offset += max(1, (int) ($recipients[$i]['parcel_count'] ?? 1));
        }

        $parcelCount = max(1, (int) ($recipients[$recipientIndex]['parcel_count'] ?? 1));

        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->skip($offset)
            ->take($parcelCount)
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

    private function trimTextOrderItemsToOne(Order $order): void
    {
        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->get();

        $baseItems = $items->reject(fn ($item) => ($item->remark ?? '') === 'rider_added');

        if ($baseItems->count() <= 1) {
            return;
        }

        $baseItems->slice(1)->each->delete();
    }

    private function itemDataFromOrder(Order $order): array
    {
        $pickup = is_array($order->pickup_point) ? $order->pickup_point : [];
        $delivery = is_array($order->delivery_point) ? $order->delivery_point : [];
        $branchId = $this->defaultBranchId();

        // Text orders: customer is entered per item — never seed from OS pickup
        // (admin create used to copy pickup → delivery, which polluted Customer fields).
        if ($this->isTextOrder($order)) {
            $customerName = '';
            $customerPhone = '';
            $customerAddress = '';
        } else {
            $customerName = trim((string) ($delivery['name'] ?? ''));
            $customerPhone = normalizeContactNumber(trim((string) ($delivery['contact_number'] ?? '')));
            $customerAddress = trim((string) ($delivery['address'] ?? ''));

            // If delivery was incorrectly mirrored from OS pickup, treat as empty.
            if ($this->deliveryLooksLikePickupOs($pickup, $delivery)) {
                $customerName = '';
                $customerPhone = '';
                $customerAddress = '';
            }
        }

        // Instruction only — never auto tip / order.description pollution.
        $remark = stripAutoOrderRemarkTip(
            $delivery['instruction'] ?? $pickup['instruction'] ?? ''
        );

        return array_merge([
            'received_date' => formatDispatchYangonDate($order->pickup_datetime ?? $order->created_at ?? now()),
            'from_branch_id' => $branchId,
            'to_branch_id' => $branchId,
            'delivery_city' => config('dispatch_item_cities.default_delivery_city', 'Mandalay'),
            'township' => config('dispatch_item_cities.default_township', 'ချမ်းမြသာစည်'),
            'item_name' => trim((string) ($pickup['description'] ?? '')),
            'remark' => $remark,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
            'weight' => 0,
        ], $this->paymentFieldsFromSource(is_array($delivery['payment'] ?? null) ? $delivery['payment'] : []));
    }

    /**
     * True when delivery name/phone/address match pickup (OS) — not a real customer.
     */
    private function deliveryLooksLikePickupOs(array $pickup, array $delivery): bool
    {
        $pName = trim((string) ($pickup['name'] ?? ''));
        $pPhone = normalizeContactNumber(trim((string) ($pickup['contact_number'] ?? '')));
        $pAddr = trim((string) ($pickup['address'] ?? ''));
        $dName = trim((string) ($delivery['name'] ?? ''));
        $dPhone = normalizeContactNumber(trim((string) ($delivery['contact_number'] ?? '')));
        $dAddr = trim((string) ($delivery['address'] ?? ''));

        if ($dName === '' && $dPhone === '' && $dAddr === '') {
            return false;
        }

        $nameSame = $pName !== '' && $dName !== '' && mb_strtolower($pName) === mb_strtolower($dName);
        $phoneSame = $pPhone !== '' && $dPhone !== '' && $pPhone === $dPhone;
        $addrSame = $pAddr !== '' && $dAddr !== '' && mb_strtolower($pAddr) === mb_strtolower($dAddr);

        return ($nameSame && $addrSame) || ($nameSame && $phoneSame) || ($addrSame && $phoneSame);
    }

    /**
     * Map client money-collect payload onto dispatch item amount fields.
     */
    private function paymentFieldsFromSource(array $source): array
    {
        if ((int) ($source['collect_money'] ?? 0) !== 1) {
            return [
                'item_value' => 0,
                'deli_amount' => 0,
                'credit_to' => 'customer',
                'advance_paid' => 0,
                'os_paid' => 0,
            ];
        }

        $creditToRaw = $source['credit_to'] ?? 'customer';
        $creditTo = in_array($creditToRaw, ['os', 'customer'], true) ? $creditToRaw : 'customer';

        return [
            'item_value' => (float) ($source['item_value'] ?? 0),
            'deli_amount' => (float) ($source['deli_amount'] ?? 0),
            'credit_to' => $creditTo,
            'advance_paid' => 0,
            'os_paid' => $creditTo === 'os' ? (float) ($source['os_paid'] ?? 0) : 0,
        ];
    }

    /**
     * Fill blank customer_* on collected items from order delivery/pickup data.
     * Fixes legacy Gate/Shop parcels that were created with item_name only.
     */
    public function backfillCustomerFieldsIfEmpty(Order $order): int
    {
        if (! $this->supports($order)) {
            return 0;
        }

        if ($this->isMultiRecipientOrder($order) && ! empty($this->recipientsFromOrder($order))) {
            $items = DispatchOrderItem::query()
                ->where('order_id', $order->id)
                ->where('status', 'collected')
                ->orderBy('id')
                ->get();

            return $this->backfillMultiRecipientCustomerFields($order, $items, $this->recipientsFromOrder($order));
        }

        $payload = $this->normalizeItemPayload($this->itemDataFromOrder($order));
        if (trim((string) ($payload['customer_name'] ?? '')) === ''
            && trim((string) ($payload['customer_address'] ?? '')) === '') {
            return 0;
        }

        $updated = 0;
        $items = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->orderBy('id')
            ->get();

        foreach ($items as $item) {
            if (($item->remark ?? '') === 'rider_added') {
                continue;
            }

            $patch = [];
            if (trim((string) ($item->customer_name ?? '')) === '' && trim((string) ($payload['customer_name'] ?? '')) !== '') {
                $patch['customer_name'] = $payload['customer_name'];
            }
            if (trim((string) ($item->customer_phone ?? '')) === '' && trim((string) ($payload['customer_phone'] ?? '')) !== '') {
                $patch['customer_phone'] = $payload['customer_phone'];
            }
            if (trim((string) ($item->customer_address ?? '')) === '' && trim((string) ($payload['customer_address'] ?? '')) !== '') {
                $patch['customer_address'] = $payload['customer_address'];
            }
            if (trim((string) ($item->remark ?? '')) === '' && trim((string) ($payload['remark'] ?? '')) !== '') {
                $patch['remark'] = $payload['remark'];
            }
            if (trim((string) ($item->township ?? '')) === '' && trim((string) ($payload['township'] ?? '')) !== '') {
                $patch['township'] = $payload['township'];
            }
            if (trim((string) ($item->delivery_city ?? '')) === '' && trim((string) ($payload['delivery_city'] ?? '')) !== '') {
                $patch['delivery_city'] = $payload['delivery_city'];
            }
            if (empty($item->from_branch_id) && ! empty($payload['from_branch_id'])) {
                $patch['from_branch_id'] = $payload['from_branch_id'];
            }
            if (empty($item->to_branch_id) && ! empty($payload['to_branch_id'])) {
                $patch['to_branch_id'] = $payload['to_branch_id'];
            }

            if ($patch === []) {
                continue;
            }

            $item->forceFill($patch)->save();
            $updated++;
        }

        return $updated;
    }

    private function backfillMultiRecipientCustomerFields(Order $order, $existingItems, array $recipients): int
    {
        $flat = [];
        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }
            $parcelCount = max(1, (int) ($recipient['parcel_count'] ?? 1));
            for ($p = 0; $p < $parcelCount; $p++) {
                $flat[] = [
                    'customer_name' => trim((string) ($recipient['name'] ?? '')),
                    'customer_phone' => normalizeContactNumber(trim((string) ($recipient['contact_number'] ?? ''))),
                    'customer_address' => trim((string) ($recipient['address'] ?? '')),
                ];
            }
        }

        if ($flat === []) {
            return 0;
        }

        $basePayload = $this->normalizeItemPayload($this->itemDataFromOrder($order));
        $updated = 0;
        $index = 0;

        foreach ($existingItems as $item) {
            if (($item->remark ?? '') === 'rider_added') {
                continue;
            }

            // Extra parcels beyond recipient count stay blank (no last-customer reuse).
            if (! array_key_exists($index, $flat)) {
                $index++;
                continue;
            }
            $recipient = $flat[$index];
            $index++;

            $patch = [];
            if (trim((string) ($item->customer_name ?? '')) === '' && ($recipient['customer_name'] ?? '') !== '') {
                $patch['customer_name'] = $recipient['customer_name'];
            }
            if (trim((string) ($item->customer_phone ?? '')) === '' && ($recipient['customer_phone'] ?? '') !== '') {
                $patch['customer_phone'] = $recipient['customer_phone'];
            }
            if (trim((string) ($item->customer_address ?? '')) === '' && ($recipient['customer_address'] ?? '') !== '') {
                $patch['customer_address'] = $recipient['customer_address'];
            }
            if (trim((string) ($item->township ?? '')) === '' && trim((string) ($basePayload['township'] ?? '')) !== '') {
                $patch['township'] = $basePayload['township'];
            }
            if (trim((string) ($item->delivery_city ?? '')) === '' && trim((string) ($basePayload['delivery_city'] ?? '')) !== '') {
                $patch['delivery_city'] = $basePayload['delivery_city'];
            }
            if (empty($item->from_branch_id) && ! empty($basePayload['from_branch_id'])) {
                $patch['from_branch_id'] = $basePayload['from_branch_id'];
            }
            if (empty($item->to_branch_id) && ! empty($basePayload['to_branch_id'])) {
                $patch['to_branch_id'] = $basePayload['to_branch_id'];
            }

            if ($patch === []) {
                continue;
            }

            $item->forceFill($patch)->save();
            $updated++;
        }

        return $updated;
    }

    private function defaultBranchId(): ?int
    {
        return resolveDefaultDispatchBranchId(
            config('dispatch_item_cities.default_from_branch', 'MDY To MDY')
        );
    }

    private function resolveTargetCount(Order $order, ?int $itemCount = null): int
    {
        if ($itemCount !== null && $itemCount > 0) {
            return $itemCount;
        }

        $collectedCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->count();

        if ($collectedCount > 0) {
            return $collectedCount;
        }

        // Legacy orders may still store parcel count in total_parcel.
        return max(1, (int) $order->total_parcel);
    }

    private function normalizeOrderCount(Order $order): void
    {
        $collectedCount = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected')
            ->count();

        // Keep declared parcel count for shop/gate; text orders follow actual item count.
        $totalParcel = $this->isTextOrder($order)
            ? $collectedCount
            : max(1, (int) $order->total_parcel, $collectedCount);

        $order->update([
            'total_parcel' => $totalParcel,
            'total_amount' => (float) $order->dispatchItems()->sum('deli_amount'),
        ]);
    }

    public function ensurePickUpState(Order $order): void
    {
        if (! $this->supports($order)) {
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

    private function itemPrefix(Order $order): string
    {
        if ((int) ($order->is_shop_order ?? 0) === 1) {
            return 'Shop Parcel';
        }

        if ((int) ($order->is_gate_order ?? 0) === 1) {
            return 'Gate Parcel';
        }

        return 'Parcel';
    }

    private function normalizeItemPayload(array $itemData): array
    {
        $creditToRaw = $itemData['credit_to'] ?? 'customer';
        $creditTo = in_array($creditToRaw, ['os', 'customer'], true) ? $creditToRaw : 'customer';

        $normalized = [
            'from_branch_id' => isset($itemData['from_branch_id']) ? (int) $itemData['from_branch_id'] : null,
            'to_branch_id' => isset($itemData['to_branch_id']) ? (int) $itemData['to_branch_id'] : null,
            'delivery_city' => trim((string) ($itemData['delivery_city'] ?? '')),
            'township' => trim((string) ($itemData['township'] ?? '')),
            'item_name' => trim((string) ($itemData['item_name'] ?? '')),
            'remark' => stripAutoOrderRemarkTip($itemData['remark'] ?? null),
            'customer_name' => trim((string) ($itemData['customer_name'] ?? '')),
            'customer_phone' => normalizeContactNumber(trim((string) ($itemData['customer_phone'] ?? ''))),
            'customer_address' => trim((string) ($itemData['customer_address'] ?? '')),
            'credit_to' => $creditTo,
            'city_id' => null,
            'received_date' => $itemData['received_date'] ?? null,
        ];

        foreach (['advance_paid', 'os_paid', 'item_value', 'deli_amount'] as $field) {
            $value = $itemData[$field] ?? 0;
            $normalized[$field] = ($value === '' || $value === null) ? 0 : (float) $value;
        }

        $normalized['weight'] = normalizeDispatchItemSize($itemData['weight'] ?? 0);

        if ($creditTo === 'customer') {
            $normalized['os_paid'] = 0;
        }

        return $normalized;
    }

    private function parseReceivedDate($value, Order $order): Carbon
    {
        if (! empty($value)) {
            try {
                return Carbon::createFromFormat('d-m-Y', (string) $value, 'Asia/Yangon')->startOfDay();
            } catch (\Exception $e) {
                try {
                    return Carbon::parse($value)->timezone('Asia/Yangon')->startOfDay();
                } catch (\Exception $e) {
                    // Fall through to order defaults.
                }
            }
        }

        return Carbon::parse($order->pickup_datetime ?? $order->created_at ?? now())
            ->timezone('Asia/Yangon')
            ->startOfDay();
    }
}
