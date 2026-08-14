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
            'status' => $this->status,
            'delivery_locked' => (bool) ($this->delivery_locked ?? false),
            'assigned_name' => optional($this->deliveryMan)->name,
            'assigned_phone' => optional($this->deliveryMan)->riderAssignedPhone(),
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
}
