<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_group' => $this->category_group,
            'image' => getSingleMedia($this, 'category_image'),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}
