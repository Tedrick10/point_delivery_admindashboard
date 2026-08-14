<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopBannerResource;
use App\Http\Resources\ShopCategoryResource;
use App\Http\Resources\ShopProductResource;
use App\Models\ShopBanner;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Http\Request;

class ShopShowcaseController extends Controller
{
    public function home()
    {
        $carouselBanners = ShopBanner::active()->where('banner_type', 'carousel')->get();
        $middleBanners = ShopBanner::active()->where('banner_type', 'middle')->get();
        $quickCategories = ShopCategory::active()->where('category_group', 'quick_link')->get();
        $productCategories = ShopCategory::active()->where('category_group', 'product_type')->get();

        $flashProducts = ShopProduct::inSection('flash_sale')->limit(20)->get();
        $flashEndsAt = ShopProduct::active()
            ->where('home_section', 'flash_sale')
            ->whereNotNull('flash_sale_ends_at')
            ->orderBy('flash_sale_ends_at')
            ->value('flash_sale_ends_at');

        $knownMeta = [
            'new_arrival' => ['title' => 'New Arrival', 'emoji' => '🌟'],
            'fans_collection' => ['title' => 'Fans Collection', 'emoji' => '🪭'],
            'accessories' => ['title' => 'Latest Accessories', 'emoji' => '🎁'],
            'featured' => ['title' => 'Featured', 'emoji' => '✨'],
        ];

        $sections = ShopProduct::active()
            ->whereNotIn('home_section', ['none', 'flash_sale'])
            ->select('home_section')
            ->distinct()
            ->orderBy('home_section')
            ->pluck('home_section')
            ->map(function ($key) use ($knownMeta) {
                $products = ShopProduct::inSection($key)->limit(12)->get();
                if ($products->isEmpty()) {
                    return null;
                }

                $meta = $knownMeta[$key] ?? [
                    'title' => ShopProduct::formatHomeSectionLabel($key),
                    'emoji' => '🛍️',
                ];

                return [
                    'key' => $key,
                    'title' => $meta['title'],
                    'emoji' => $meta['emoji'],
                    'products' => ShopProductResource::collection($products),
                ];
            })
            ->filter()
            ->values();

        return json_custom_response([
            'carousel_banners' => ShopBannerResource::collection($carouselBanners),
            'middle_banners' => ShopBannerResource::collection($middleBanners),
            'quick_categories' => ShopCategoryResource::collection($quickCategories),
            'product_categories' => ShopCategoryResource::collection($productCategories),
            'flash_sale' => [
                'ends_at' => $flashEndsAt?->toIso8601String(),
                'products' => ShopProductResource::collection($flashProducts),
            ],
            'sections' => $sections,
        ]);
    }

    public function categories()
    {
        $categories = ShopCategory::active()->get();

        return json_custom_response([
            'data' => ShopCategoryResource::collection($categories),
        ]);
    }

    public function categoryProducts(Request $request, $id)
    {
        $category = ShopCategory::active()->find($id);
        if (!$category) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.category')]), 404);
        }

        $perPage = (int) $request->get('per_page', 20);
        $products = ShopProduct::active()
            ->where('category_id', $id)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);

        return json_custom_response([
            'category' => new ShopCategoryResource($category),
            'pagination' => json_pagination_response($products),
            'data' => ShopProductResource::collection($products),
        ]);
    }

    public function products(Request $request)
    {
        $query = ShopProduct::active()->with('category');

        if ($request->filled('section')) {
            $query->where('home_section', $request->section);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->get('per_page', 20);
        $products = $query->orderBy('sort_order')->orderByDesc('id')->paginate($perPage);

        return json_custom_response([
            'pagination' => json_pagination_response($products),
            'data' => ShopProductResource::collection($products),
        ]);
    }

    public function productDetail($id)
    {
        $product = ShopProduct::active()->with('category')->find($id);
        if (!$product) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.product')]), 404);
        }

        return json_custom_response([
            'data' => new ShopProductResource($product),
        ]);
    }
}
