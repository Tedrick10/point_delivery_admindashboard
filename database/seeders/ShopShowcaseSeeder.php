<?php

namespace Database\Seeders;

use App\Models\ShopBanner;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        if (ShopProduct::count() > 0) {
            $this->command?->warn('Shop showcase data already exists. Clearing and reseeding...');
        }

        DB::transaction(function () {
            ShopProduct::query()->forceDelete();
            ShopCategory::query()->forceDelete();
            ShopBanner::query()->forceDelete();

            $categories = $this->seedCategories();
            $this->seedBanners($categories);
            $this->seedProducts($categories);
        });

        $this->command?->info('Shop showcase demo data seeded successfully.');
    }

    private function seedCategories(): array
    {
        $definitions = [
            'electronics' => [
                'name' => 'Electronics',
                'category_group' => 'product_type',
                'sort_order' => 1,
                'image' => 'images/shop/categories/electronics.jpg',
            ],
            'fashion' => [
                'name' => 'Fashion',
                'category_group' => 'product_type',
                'sort_order' => 2,
                'image' => 'images/shop/categories/fashion.jpg',
            ],
            'beauty' => [
                'name' => 'Beauty',
                'category_group' => 'product_type',
                'sort_order' => 3,
                'image' => 'images/shop/categories/beauty.jpg',
            ],
            'home' => [
                'name' => 'Home & Living',
                'category_group' => 'product_type',
                'sort_order' => 4,
                'image' => 'images/shop/categories/home.jpg',
            ],
            'sports' => [
                'name' => 'Sports',
                'category_group' => 'product_type',
                'sort_order' => 5,
                'image' => 'images/shop/categories/sports.jpg',
            ],
            'groceries' => [
                'name' => 'Groceries',
                'category_group' => 'product_type',
                'sort_order' => 6,
                'image' => 'images/shop/categories/groceries.jpg',
            ],
            'quick_flash' => [
                'name' => 'Flash Sale',
                'category_group' => 'quick_link',
                'sort_order' => 1,
                'image' => 'images/shop/categories/quick-flash.jpg',
            ],
            'quick_new' => [
                'name' => 'New Arrival',
                'category_group' => 'quick_link',
                'sort_order' => 2,
                'image' => 'images/shop/categories/quick-new.jpg',
            ],
            'quick_best' => [
                'name' => 'Best Seller',
                'category_group' => 'quick_link',
                'sort_order' => 3,
                'image' => 'images/shop/categories/quick-best.jpg',
            ],
            'quick_accessories' => [
                'name' => 'Accessories',
                'category_group' => 'quick_link',
                'sort_order' => 4,
                'image' => 'images/shop/categories/quick-accessories.jpg',
            ],
        ];

        $created = [];
        foreach ($definitions as $key => $definition) {
            $category = ShopCategory::create([
                'name' => $definition['name'],
                'category_group' => $definition['category_group'],
                'sort_order' => $definition['sort_order'],
                'status' => 1,
            ]);

            $this->attachMedia($category, 'category_image', $definition['image']);
            $created[$key] = $category;
        }

        return $created;
    }

    private function seedBanners(array $categories): void
    {
        $flashCategoryId = $categories['quick_flash']->id;
        $electronicsId = $categories['electronics']->id;

        $banners = [
            [
                'title' => 'Point Shop Summer Sale',
                'banner_type' => 'carousel',
                'link_type' => 'category',
                'link_value' => (string) $flashCategoryId,
                'sort_order' => 1,
                'image' => 'images/shop/banners/carousel-1.jpg',
            ],
            [
                'title' => 'New Arrivals Collection',
                'banner_type' => 'carousel',
                'link_type' => 'category',
                'link_value' => (string) $categories['quick_new']->id,
                'sort_order' => 2,
                'image' => 'images/shop/banners/carousel-2.jpg',
            ],
            [
                'title' => 'Electronics Mega Deals',
                'banner_type' => 'carousel',
                'link_type' => 'category',
                'link_value' => (string) $electronicsId,
                'sort_order' => 3,
                'image' => 'images/shop/banners/carousel-3.jpg',
            ],
            [
                'title' => 'Free Delivery on Orders Over 50,000',
                'banner_type' => 'middle',
                'link_type' => 'none',
                'link_value' => null,
                'sort_order' => 1,
                'image' => 'images/shop/banners/middle-1.jpg',
            ],
            [
                'title' => 'Member Exclusive Offers',
                'banner_type' => 'middle',
                'link_type' => 'none',
                'link_value' => null,
                'sort_order' => 2,
                'image' => 'images/shop/banners/middle-2.jpg',
            ],
        ];

        foreach ($banners as $bannerData) {
            $image = $bannerData['image'];
            unset($bannerData['image']);

            $banner = ShopBanner::create(array_merge($bannerData, ['status' => 1]));
            $this->attachMedia($banner, 'banner_image', $image);
        }
    }

    private function seedProducts(array $categories): void
    {
        $flashEndsAt = Carbon::now()->addDays(3)->endOfDay();

        $products = [
            [
                'name' => 'Samsung Galaxy A55 5G',
                'category' => 'electronics',
                'description' => '6.6" Super AMOLED display, 50MP triple camera, 5000mAh battery. Perfect for everyday use with smooth 5G performance.',
                'sku' => 'PS-ELEC-001',
                'price' => 649000,
                'sale_price' => 579000,
                'home_section' => 'flash_sale',
                'flash_sale_ends_at' => $flashEndsAt,
                'storage_options' => ['128GB', '256GB'],
                'color_options' => ['Awesome Navy', 'Awesome Iceblue'],
                'sort_order' => 1,
                'image' => 'images/shop/products/phone-1.jpg',
            ],
            [
                'name' => 'MacBook Air M2',
                'category' => 'electronics',
                'description' => 'Ultra-thin laptop with Apple M2 chip, 13.6" Liquid Retina display, up to 18 hours battery life.',
                'sku' => 'PS-ELEC-002',
                'price' => 1899000,
                'sale_price' => 1699000,
                'home_section' => 'flash_sale',
                'flash_sale_ends_at' => $flashEndsAt,
                'storage_options' => ['256GB', '512GB'],
                'color_options' => ['Midnight', 'Starlight'],
                'sort_order' => 2,
                'image' => 'images/shop/products/laptop-1.jpg',
            ],
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'category' => 'electronics',
                'description' => 'Industry-leading noise cancellation with premium sound quality and all-day comfort.',
                'sku' => 'PS-ELEC-003',
                'price' => 459000,
                'sale_price' => 389000,
                'home_section' => 'flash_sale',
                'flash_sale_ends_at' => $flashEndsAt,
                'color_options' => ['Black', 'Silver'],
                'sort_order' => 3,
                'image' => 'images/shop/products/headphones-1.jpg',
            ],
            [
                'name' => 'Anker PowerCore 20000mAh',
                'category' => 'electronics',
                'description' => 'Fast-charging portable power bank with dual USB ports. Keep your devices powered on the go.',
                'sku' => 'PS-ELEC-004',
                'price' => 89000,
                'sale_price' => 69000,
                'home_section' => 'flash_sale',
                'flash_sale_ends_at' => $flashEndsAt,
                'color_options' => ['Black'],
                'sort_order' => 4,
                'image' => 'images/shop/products/powerbank-1.jpg',
            ],
            [
                'name' => 'Apple Watch Series 9',
                'category' => 'electronics',
                'description' => 'Advanced health features, bright Always-On Retina display, and seamless iPhone integration.',
                'sku' => 'PS-ELEC-005',
                'price' => 549000,
                'home_section' => 'new_arrival',
                'color_options' => ['Midnight', 'Starlight', 'Product Red'],
                'sort_order' => 1,
                'image' => 'images/shop/products/watch-1.jpg',
            ],
            [
                'name' => 'iPad Air 11"',
                'category' => 'electronics',
                'description' => 'Powerful M2 chip, stunning Liquid Retina display, perfect for work, study, and entertainment.',
                'sku' => 'PS-ELEC-006',
                'price' => 899000,
                'home_section' => 'new_arrival',
                'storage_options' => ['128GB', '256GB'],
                'color_options' => ['Blue', 'Purple', 'Starlight'],
                'sort_order' => 2,
                'image' => 'images/shop/products/tablet-1.jpg',
            ],
            [
                'name' => 'Canon EOS R50 Mirrorless',
                'category' => 'electronics',
                'description' => 'Compact mirrorless camera with 24.2MP sensor, 4K video, and easy content creation tools.',
                'sku' => 'PS-ELEC-007',
                'price' => 1299000,
                'home_section' => 'new_arrival',
                'sort_order' => 3,
                'image' => 'images/shop/products/camera-1.jpg',
            ],
            [
                'name' => 'JBL Tune 770NC Earbuds',
                'category' => 'electronics',
                'description' => 'Wireless earbuds with active noise cancelling and 24-hour total playtime.',
                'sku' => 'PS-ELEC-008',
                'price' => 129000,
                'home_section' => 'new_arrival',
                'color_options' => ['Black', 'Blue'],
                'sort_order' => 4,
                'image' => 'images/shop/products/earbuds-1.jpg',
            ],
            [
                'name' => 'Midea Stand Fan 16"',
                'category' => 'home',
                'description' => 'Powerful 3-speed stand fan with wide oscillation. Ideal for Myanmar summer heat.',
                'sku' => 'PS-HOME-001',
                'price' => 89000,
                'sale_price' => 69000,
                'home_section' => 'fans_collection',
                'color_options' => ['White', 'Blue'],
                'sort_order' => 1,
                'image' => 'images/shop/products/fan-1.jpg',
            ],
            [
                'name' => 'Panasonic Desk Fan 12"',
                'category' => 'home',
                'description' => 'Compact desk fan with quiet operation and adjustable tilt. Perfect for office and bedroom.',
                'sku' => 'PS-HOME-002',
                'price' => 59000,
                'home_section' => 'fans_collection',
                'sort_order' => 2,
                'image' => 'images/shop/products/fan-2.jpg',
            ],
            [
                'name' => 'Rechargeable Mini Fan',
                'category' => 'home',
                'description' => 'Portable USB rechargeable mini fan with 3 speed levels. Take cool air anywhere.',
                'sku' => 'PS-HOME-003',
                'price' => 25000,
                'sale_price' => 19000,
                'home_section' => 'fans_collection',
                'color_options' => ['Pink', 'White', 'Green'],
                'sort_order' => 3,
                'image' => 'images/shop/products/fan-3.jpg',
            ],
            [
                'name' => 'Point Delivery Backpack',
                'category' => 'fashion',
                'description' => 'Durable waterproof delivery backpack with multiple compartments and reflective strips.',
                'sku' => 'PS-FASH-001',
                'price' => 45000,
                'home_section' => 'accessories',
                'color_options' => ['Black', 'Orange'],
                'sort_order' => 1,
                'image' => 'images/shop/products/bag-1.jpg',
            ],
            [
                'name' => 'Leather Crossbody Bag',
                'category' => 'fashion',
                'description' => 'Premium PU leather crossbody bag with adjustable strap and secure zip closure.',
                'sku' => 'PS-FASH-002',
                'price' => 35000,
                'price_max' => 55000,
                'home_section' => 'accessories',
                'color_options' => ['Brown', 'Black', 'Tan'],
                'sort_order' => 2,
                'image' => 'images/shop/products/bag-2.jpg',
            ],
            [
                'name' => 'Ray-Ban Classic Sunglasses',
                'category' => 'fashion',
                'description' => 'Timeless aviator sunglasses with UV400 protection and lightweight metal frame.',
                'sku' => 'PS-FASH-003',
                'price' => 79000,
                'home_section' => 'accessories',
                'sort_order' => 3,
                'image' => 'images/shop/products/sunglasses-1.jpg',
            ],
            [
                'name' => 'Fast USB-C Charger 65W',
                'category' => 'electronics',
                'description' => 'GaN fast charger compatible with phones, tablets, and laptops. Compact travel design.',
                'sku' => 'PS-ACC-001',
                'price' => 39000,
                'home_section' => 'accessories',
                'sort_order' => 4,
                'image' => 'images/shop/products/charger-1.jpg',
            ],
            [
                'name' => 'Nike Air Max Sneakers',
                'category' => 'sports',
                'description' => 'Comfortable running shoes with responsive cushioning and breathable mesh upper.',
                'sku' => 'PS-SPORT-001',
                'price' => 189000,
                'price_max' => 219000,
                'home_section' => 'featured',
                'color_options' => ['White/Red', 'Black/White'],
                'sort_order' => 1,
                'image' => 'images/shop/products/shoes-1.jpg',
            ],
            [
                'name' => 'Laneige Water Bank Cream',
                'category' => 'beauty',
                'description' => 'Deep hydration moisturizer with green mineral water for glowing, healthy skin.',
                'sku' => 'PS-BEAU-001',
                'price' => 69000,
                'home_section' => 'featured',
                'sort_order' => 2,
                'image' => 'images/shop/products/skincare-1.jpg',
            ],
            [
                'name' => 'Chanel Chance Eau Tendre',
                'category' => 'beauty',
                'description' => 'Elegant floral fragrance with soft, feminine notes. A timeless classic.',
                'sku' => 'PS-BEAU-002',
                'price' => 249000,
                'home_section' => 'featured',
                'sort_order' => 3,
                'image' => 'images/shop/products/perfume-1.jpg',
            ],
            [
                'name' => 'Organic Green Tea Set',
                'category' => 'groceries',
                'description' => 'Premium Myanmar green tea leaves with traditional ceramic tea set. Gift-ready packaging.',
                'sku' => 'PS-GROC-001',
                'price' => 28000,
                'home_section' => 'none',
                'sort_order' => 1,
                'image' => 'images/shop/products/skincare-2.jpg',
            ],
            [
                'name' => 'Vitamin C Brightening Serum',
                'category' => 'beauty',
                'description' => 'Daily brightening serum with 15% vitamin C for even skin tone and radiance.',
                'sku' => 'PS-BEAU-003',
                'price' => 45000,
                'home_section' => 'none',
                'sort_order' => 2,
                'image' => 'images/shop/products/skincare-2.jpg',
            ],
        ];

        foreach ($products as $productData) {
            $categoryKey = $productData['category'];
            $image = $productData['image'];
            unset($productData['category'], $productData['image']);

            $product = ShopProduct::create(array_merge($productData, [
                'category_id' => $categories[$categoryKey]->id,
                'stock_status' => 'in_stock',
                'status' => 1,
            ]));

            $this->attachMedia($product, 'product_image', $image);
            $this->attachMedia($product, 'product_gallery', $image);
        }
    }

    private function attachMedia($model, string $collection, string $publicPath): void
    {
        $fullPath = public_path($publicPath);

        if (! file_exists($fullPath) || filesize($fullPath) < 1000) {
            $fallback = public_path('images/default.png');
            if (! file_exists($fallback)) {
                return;
            }
            $fullPath = $fallback;
        }

        if ($collection === 'product_gallery') {
            if ($model->getMedia('product_gallery')->isNotEmpty()) {
                return;
            }
            $model->addMedia($fullPath)->preservingOriginal()->toMediaCollection($collection);

            return;
        }

        $model->clearMediaCollection($collection);
        $model->addMedia($fullPath)->preservingOriginal()->toMediaCollection($collection);
    }
}
