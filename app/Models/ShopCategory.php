<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ShopCategory extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name', 'category_group', 'sort_order', 'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(ShopProduct::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1)->orderBy('sort_order')->orderBy('name');
    }
}
