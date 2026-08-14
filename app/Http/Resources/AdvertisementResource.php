<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'ad_type' => $this->ad_type,
            'placement' => $this->placement,
            'target_app' => $this->target_app,
            'link_url' => $this->link_url,
            'image' => getSingleMedia($this, 'ad_image'),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'approval_status' => $this->approval_status,
            'status' => $this->status,
        ];
    }
}
