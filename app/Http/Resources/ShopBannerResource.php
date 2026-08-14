<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopBannerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'banner_type' => $this->banner_type,
            'link_type' => $this->link_type,
            'link_value' => $this->link_value,
            'image' => getSingleMedia($this, 'banner_image'),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}
