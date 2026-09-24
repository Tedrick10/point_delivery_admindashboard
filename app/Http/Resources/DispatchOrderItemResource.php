<?php

namespace App\Http\Resources;

use App\Models\DispatchOrderItem;
use App\Services\DispatchOrderWorkflowService;
use App\Services\PhotoOrderDispatchService;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchOrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        $photoUrl = $this->resolvePhotoUrl();

        return [
            'id' => (int) $this->id,
            'order_id' => (int) $this->order_id,
            'item_name' => $this->item_name,
            'remark' => $this->remark,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_address' => $this->customer_address,
            'delivery_city' => $this->delivery_city,
            'delivery_city_label' => DispatchOrderItem::deliveryCityLabel($this->delivery_city),
            'township' => $this->township,
            'from_branch' => optional($this->fromBranch)->name,
            'to_branch' => optional($this->toBranch)->name,
            'item_value' => (float) ($this->item_value ?? 0),
            'deli_amount' => (float) ($this->deli_amount ?? 0),
            'weight' => (float) ($this->weight ?? 0),
            'size' => formatDispatchItemSize($this->weight),
            'photo_id' => (int) ($this->photo_id ?? 0),
            'photo_url' => $photoUrl,
            'pending_photo_id' => (int) ($this->pending_photo_id ?? 0),
            'pending_photo_url' => $this->resolvePendingPhotoUrl(),
            'pending_remarks' => $this->resolvePendingRemarks(),
            'delivered_photo_id' => (int) ($this->delivered_photo_id ?? 0),
            'delivered_photo_url' => $this->resolveDeliveredPhotoUrl(),
            'delivered_type' => $this->delivered_type,
            'gate_amount' => (float) ($this->gate_amount ?? 0),
            'is_kyo_shin' => $this->resolveKyoShinItem() !== null,
            'kyo_shin_status' => $this->resolveKyoShinStatus(),
            'kyo_shin_due_date' => $this->resolveKyoShinDueDate(),
            'kyo_shin_paid_at' => $this->resolveKyoShinPaidAt(),
            'advance_paid' => (float) ($this->advance_paid ?? 0),
            'os_paid' => (float) ($this->os_paid ?? 0),
            'os_to_pay' => $this->resource instanceof DispatchOrderItem
                ? $this->resource->displayOsToPay()
                : (float) ($this->os_to_pay ?? 0),
            'cust_get' => (float) ($this->cust_get ?? 0),
            'credit_to' => $this->credit_to,
            'pickup_pay_mode' => $this->pickup_pay_mode
                ?: ((float) ($this->os_paid ?? 0) > 0
                    ? 'pay_done'
                    : ((($this->credit_to ?? 'customer') === 'os') ? 'os_pay' : 'customer_pay')),
            'admin_finished_at' => $this->admin_finished_at
                ? \Carbon\Carbon::parse($this->admin_finished_at)->toIso8601String()
                : null,
            'status' => $this->status,
            'return_type' => $this->resource instanceof DispatchOrderItem
                ? $this->resource->returnType()
                : ($this->return_type ?? null),
            'has_return_deli_fee' => $this->resource instanceof DispatchOrderItem
                ? $this->resource->hasReturnDeliFee()
                : false,
            'os_name' => $this->resolveOsContactField('name'),
            'os_phone' => $this->resolveOsContactField('phone'),
            'os_address' => $this->resolveOsContactField('address'),
            'track_kind' => in_array((string) $this->status, [
                'assigned',
                'courier_assigned',
                'courier_departed',
                'pending',
                'completed',
                'return',
                'os_returned',
            ], true)
                ? 'order'
                : 'pickup',
            'delivery_locked' => (bool) ($this->delivery_locked ?? false),
            'return_reassigned' => $this->resource instanceof DispatchOrderItem
                ? $this->resource->isReturnReassigned()
                : false,
            'assigned_from_return' => $this->resource instanceof DispatchOrderItem
                ? $this->resource->isAssignedFromReturn()
                : false,
            'assigned_name' => optional($this->deliveryMan)->name,
            'assigned_phone' => optional($this->deliveryMan)->riderAssignedPhone(),
            'delivery_man_id' => $this->delivery_man_id ? (int) $this->delivery_man_id : null,
            'can_rate_rider' => $this->resolveCanRateRider($request),
            'my_rider_rating' => $this->resolveMyRiderRating($request),
            'rating_presets' => \App\Models\Ratings::presetComments(),
            'status_label' => app(DispatchOrderWorkflowService::class)
                ->clientItemStatusLabel($this->resource),
            'allowed_actions' => app(DispatchOrderWorkflowService::class)
                ->allowedDeliveryStatusActions($this->resource),
            'code' => $this->code,
            'received_date' => $this->received_date
                ? \Carbon\Carbon::parse($this->received_date)->format('d-m-Y')
                : null,
            'updated_at' => $this->updated_at
                ? \Carbon\Carbon::parse($this->updated_at)->toIso8601String()
                : null,
            'admin_updated_at' => $this->admin_updated_at
                ? \Carbon\Carbon::parse($this->admin_updated_at)->toIso8601String()
                : null,
            'chat_message_count' => (int) ($this->chat_message_count ?? 0),
            'chat_unread_count' => (int) ($this->chat_unread_count ?? 0),
            'last_chat_message' => $this->last_chat_message ?? null,
            'order' => $this->when($this->relationLoaded('order') && $this->order, function () {
                $order = $this->order;
                $pickup = is_array($order->pickup_point)
                    ? $order->pickup_point
                    : (json_decode((string) $order->pickup_point, true) ?: []);

                $rider = $this->relationLoaded('deliveryMan')
                    ? $this->deliveryMan
                    : null;

                return [
                    'id' => (int) $order->id,
                    'os_name' => $pickup['name'] ?? null,
                    'os_phone' => $pickup['contact_number'] ?? ($pickup['phone'] ?? null),
                    'os_address' => $pickup['address'] ?? null,
                    'order_count' => (int) ($order->total_parcel ?? 0),
                    'assigned_name' => $rider?->name,
                    'assigned_phone' => $rider?->riderAssignedPhone(),
                    'region' => DispatchOrderItem::deliveryCityLabel($this->delivery_city)
                        ?: (optional($order->city)->name),
                    'status' => $order->status,
                ];
            }),
        ];
    }

    protected function resolvePhotoUrl(): ?string
    {
        $photoId = (int) ($this->photo_id ?? 0);
        if ($photoId <= 0) {
            return null;
        }

        $media = $this->relationLoaded('photoMedia')
            ? $this->photoMedia
            : app(PhotoOrderDispatchService::class)->findPhotoMedia($photoId);

        if ($media && getFileExistsCheck($media)) {
            return mediaAbsoluteUrl($media);
        }

        return null;
    }

    protected function resolvePendingRemarks(): array
    {
        if (! $this->resource instanceof DispatchOrderItem) {
            return [];
        }

        return $this->resource->displayPendingRemarks()
            ->map(function ($row) {
                return [
                    'remark' => (string) ($row->remark ?? ''),
                    'photo_id' => (int) ($row->photo_id ?? 0),
                    'photo_url' => $row->photoUrl(),
                    'pending_date' => $row->pendingDateLabel(),
                    'pending_at' => $row->pending_at
                        ? $row->pending_at->copy()->timezone('Asia/Yangon')->toIso8601String()
                        : null,
                    'delivery_man_id' => $row->delivery_man_id ? (int) $row->delivery_man_id : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function resolvePendingPhotoUrl(): ?string
    {
        $photoId = (int) ($this->pending_photo_id ?? 0);
        if ($photoId <= 0) {
            return null;
        }

        $media = $this->relationLoaded('pendingPhotoMedia')
            ? $this->pendingPhotoMedia
            : app(PhotoOrderDispatchService::class)->findPhotoMedia($photoId);

        if ($media && getFileExistsCheck($media)) {
            return mediaAbsoluteUrl($media);
        }

        return null;
    }

    protected function resolveDeliveredPhotoUrl(): ?string
    {
        $photoId = (int) ($this->delivered_photo_id ?? 0);
        if ($photoId <= 0) {
            return null;
        }

        $media = $this->relationLoaded('deliveredPhotoMedia')
            ? $this->deliveredPhotoMedia
            : app(PhotoOrderDispatchService::class)->findPhotoMedia($photoId);

        if ($media && getFileExistsCheck($media)) {
            return mediaAbsoluteUrl($media);
        }

        return null;
    }

    protected function resolveCanRateRider($request): bool
    {
        $user = $request->user();
        if (! $user || ($user->user_type ?? '') !== 'client') {
            return false;
        }
        if (($this->status ?? '') !== 'completed' || empty($this->delivery_man_id)) {
            return false;
        }
        if ($this->relationLoaded('order') && $this->order) {
            return (int) $this->order->client_id === (int) $user->id;
        }

        return true;
    }

    protected function resolveMyRiderRating($request): ?array
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $rating = \App\Models\Ratings::query()
            ->where('dispatch_order_item_id', $this->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $rating) {
            return null;
        }

        return [
            'id' => (int) $rating->id,
            'rating' => (float) $rating->rating,
            'comment' => $rating->comment,
        ];
    }

    protected function resolveKyoShinItem(): ?\App\Models\KyoShinItem
    {
        if (! $this->resource instanceof DispatchOrderItem) {
            return null;
        }
        if (! \Illuminate\Support\Facades\Schema::hasTable('kyo_shin_items')) {
            return null;
        }

        return $this->relationLoaded('kyoShinItem')
            ? $this->kyoShinItem
            : $this->kyoShinItem()->first();
    }

    protected function resolveKyoShinStatus(): ?string
    {
        $row = $this->resolveKyoShinItem();

        return $row?->status;
    }

    protected function resolveKyoShinDueDate(): ?string
    {
        $row = $this->resolveKyoShinItem();
        if (! $row?->due_finished_at) {
            return null;
        }

        return \Carbon\Carbon::parse($row->due_finished_at, 'Asia/Yangon')->format('d-m-Y');
    }

    protected function resolveKyoShinPaidAt(): ?string
    {
        $row = $this->resolveKyoShinItem();
        if (! $row?->advanced_paid_at) {
            return null;
        }

        return $row->advanced_paid_at->copy()->timezone('Asia/Yangon')->format('d-m-Y');
    }

    protected function resolveOsContactField(string $field): ?string
    {
        $order = $this->relationLoaded('order') ? $this->order : null;
        if (! $order) {
            return null;
        }

        $value = match ($field) {
            'name' => resolveDispatchOsName($order),
            'phone' => resolveDispatchOsPhone($order),
            'address' => resolveDispatchOsAddress($order),
            default => null,
        };

        if ($value === null || $value === '-' || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }
}
