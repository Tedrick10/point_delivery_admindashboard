<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopProductResource extends JsonResource
{
    public function toArray($request)
    {
        $images = getAttachments($this->getMedia('product_gallery'));
        if (empty($images)) {
            $cover = getSingleMedia($this, 'product_image');
            if ($cover) {
                $images = [$cover];
            }
        }

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => optional($this->category)->name,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'price_max' => $this->price_max !== null ? (float) $this->price_max : null,
            'stock_status' => $this->stock_status,
            'home_section' => $this->home_section,
            'flash_sale_ends_at' => $this->flash_sale_ends_at?->toIso8601String(),
            'storage_options' => $this->storage_options ?? [],
            'color_options' => $this->color_options ?? [],
            'image' => $images[0] ?? null,
            'images' => $images,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}
