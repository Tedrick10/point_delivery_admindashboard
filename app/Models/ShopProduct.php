<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ShopProduct extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'category_id', 'name', 'description', 'sku', 'price', 'sale_price', 'price_max',
        'stock_status', 'home_section', 'flash_sale_ends_at', 'storage_options', 'color_options',
        'sort_order', 'status',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'price_max' => 'decimal:2',
        'storage_options' => 'array',
        'color_options' => 'array',
        'flash_sale_ends_at' => 'datetime',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInSection($query, string $section)
    {
        return $query->active()
            ->where('home_section', $section)
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public static function defaultHomeSections(): array
    {
        return [
            'none' => 'None',
            'flash_sale' => 'Flash Sale',
            'new_arrival' => 'New Arrival',
            'fans_collection' => 'Fans Collection',
            'accessories' => 'Accessories',
            'featured' => 'Featured',
        ];
    }

    public static function homeSectionOptions(): array
    {
        $options = static::defaultHomeSections();

        $existing = static::query()
            ->whereNotNull('home_section')
            ->where('home_section', '!=', '')
            ->distinct()
            ->orderBy('home_section')
            ->pluck('home_section');

        foreach ($existing as $section) {
            if (! isset($options[$section])) {
                $options[$section] = static::formatHomeSectionLabel($section);
            }
        }

        return $options;
    }

    public static function formatHomeSectionLabel(string $section): string
    {
        return ucwords(str_replace('_', ' ', $section));
    }

    public static function normalizeHomeSection(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 'none';
        }

        $slug = \Illuminate\Support\Str::slug($value, '_');

        return $slug !== '' ? $slug : 'none';
    }
}
