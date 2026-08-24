<?php

namespace App\Http\Resources;

use App\Http\Resources\DispatchOrderItemResource;
use App\Models\City;
use App\Models\ExtraCharge;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pdfUrl = null;
         if($this->status == 'completed' ){
             $pdfUrl = route('api-order-invoice', ['id' => $this->id]);
         }
         $basetotal = $this->weight_charge  + $this->distance_charge + $this->vehicle_charge + $this->insurance_charge + $this->fixed_charges;
         $extraCharge = ExtraCharge::where('city_id',$this->city_id)->get();
         $cityData = City::find($this->city_id);
         $pickupService = app(\App\Services\PickupParcelDispatchService::class);

        return [
            'order_tracking_id'              => $this->milisecond,
            'id'                             => (int)$this->id,
            'client_id'                      => (int)$this->client_id,
            'client_name'                    => optional($this->client)->name,
            'date'                           => $this->date,
            'pickup_point'                   => $this->pickup_point,
            'delivery_point'                 => $this->delivery_point,
            'country_id'                     => (int)$this->country_id,
            'country_name'                   => optional($this->country)->name,
            'city_id'                        => (int)$this->city_id,
            'city_name'                      => optional($this->city)->name,
            'parcel_type'                    => $this->parcel_type,
            'total_weight'                   => $this->total_weight,
            'total_distance'                 => $this->total_distance,
            'weight_charge'                  => $this->weight_charge,
            'distance_charge'                => $this->distance_charge,
            'vehicle_charge'                 => $this->vehicle_charge,
            'pickup_datetime'                => $this->pickup_datetime,
            'delivery_datetime'              => $this->delivery_datetime,
            'parent_order_id'                => (int)$this->parent_order_id,
            'status'                         => $this->status,
            'payment_id'                     => (int)($this->payment_id ?? null),
            'payment_type'                   => optional($this->payment)->payment_type,
            'payment_status'                 => optional($this->payment)->payment_status,
            'payment_collect_from'           => $this->payment_collect_from,
            'delivery_man_id'                => (int)$this->delivery_man_id,
            'delivery_man_name'              => optional($this->delivery_man)->name,
            'delivery_man_contact_number'    => optional($this->delivery_man)->riderAssignedPhone(),
            'can_rate_rider'                 => $this->resolveCanRatePickupRider($request),
            'my_rider_rating'                => $this->resolveMyPickupRiderRating($request),
            'rating_presets'                 => \App\Models\Ratings::presetComments(),
            'fixed_charges'                  => $this->fixed_charges,
            'extra_charges'                  => $this->extra_charges,
            'total_amount'                   => $this->total_amount,
            'insurance_charge'               => $this->insurance_charge,
            'total_parcel'                   => $this->total_parcel,
            'is_photo_order'                 => (int) ($this->is_photo_order ?? 0),
            'is_text_order'                  => (int) ($this->is_text_order ?? 0),
            'is_shop_order'                  => (int) ($this->is_shop_order ?? 0),
            'is_gate_order'                  => (int) ($this->is_gate_order ?? 0),
            'is_self_order'                  => (int) ($this->is_self_order ?? 1),
            'requires_pickup_parcel_photos'  => $pickupService->requiresRiderPickupPhotos($this->resource) ? 1 : 0,
            'pickup_parcel_count'            => $pickupService->dispatchItemCount($this->resource),
            'dispatch_items'                 => $this->when(
                $pickupService->isDispatchPickupOrder($this->resource),
                fn () => DispatchOrderItemResource::collection($this->clientVisibleDispatchItems())
            ),
            'pickup_proof_images'            => $pickupService->pickupProofImageUrls($this->resource),
            'gate_pass_images'               => $pickupService->gatePassImageUrls($this->resource),
            'reason'                         => $this->reason,
            'pickup_error_at'                => optional($this->pickup_error_at)?->toIso8601String(),
            'pickup_error_choice'            => $this->pickup_error_choice,
            'pickup_error_choice_label'      => pickupErrorChoiceLabel($this->pickup_error_choice),
            'pickup_error_choice_at'         => optional($this->pickup_error_choice_at)?->toIso8601String(),
            'pickup_error'                   => (
                $this->status === 'pickup_error'
                || ($this->status === 'cancelled' && ($this->pickup_error_choice ?? '') === 'express')
            ) ? pickupErrorClientWindow($this->resource) : null,
            'pickup_confirm_by_client'       => $this->pickup_confirm_by_client,
            'pickup_confirm_by_delivery_man' => $this->pickup_confirm_by_delivery_man,
            'pickup_time_signature'          =>  getSingleMedia($this, 'pickup_time_signature', null),
            'delivery_time_signature'        =>  getSingleMedia($this, 'delivery_time_signature', null),
            'auto_assign'                    => $this->auto_assign,
            'is_reschedule'                  => $this->is_reschedule,
            'rescheduledatetime'             => $this->rescheduledatetime ? Carbon::parse($this->rescheduledatetime)->toDateString() : null,
            'is_shipped'                     => $this->is_shipped,
            'shipped_verify_at'              => $this->shipped_verify_at,
            'cancelled_delivery_man_ids'     => $this->cancelled_delivery_man_ids,
            'deleted_at'                     => $this->deleted_at,
            'return_order_id'                => $this->retunOrdered->count() > 0 ? true : false,
            'vehicle_id'                     => $this->vehicle_id,
            'pickup_vehicle_type'            => $this->pickup_vehicle_type ?? 'motorcycle',
            'vehicle_data'                   => $this->vehicle_data,
            'vehicle_image'                  => getMediaFileExit(optional($this->vehicle), 'vehicle_image') ? getSingleMedia(optional($this->vehicle), 'vehicle_image', null) : null,
            'is_return'                      => $this->is_return,
            'invoice'                        => $pdfUrl,
            'extra_charge_list'              => $extraCharge,
            'city_details_list'              => $cityData,
            'base_total'                     =>(int) $basetotal,
        ];
    }

    private function clientVisibleDispatchItems()
    {
        $statuses = app(\App\Services\DispatchOrderWorkflowService::class)->clientVisibleItemStatuses();

        if ($this->relationLoaded('dispatchItems')) {
            return $this->dispatchItems
                ->whereIn('status', $statuses)
                ->sortBy('id')
                ->values();
        }

        return $this->dispatchItems()
            ->whereIn('status', $statuses)
            ->with(['fromBranch', 'toBranch', 'photoMedia'])
            ->orderBy('id')
            ->get();
    }

    protected function resolveCanRatePickupRider($request): bool
    {
        $user = $request->user();
        if (! $user || ($user->user_type ?? '') !== 'client') {
            return false;
        }
        if ((int) $this->client_id !== (int) $user->id) {
            return false;
        }
        if (empty($this->delivery_man_id)) {
            return false;
        }

        return in_array((string) $this->status, [
            'courier_picked_up',
            'courier_departed',
            'completed',
        ], true);
    }

    protected function resolveMyPickupRiderRating($request): ?array
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $rating = \App\Models\Ratings::query()
            ->where('order_id', $this->id)
            ->where('user_id', $user->id)
            ->whereNull('dispatch_order_item_id')
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
}
