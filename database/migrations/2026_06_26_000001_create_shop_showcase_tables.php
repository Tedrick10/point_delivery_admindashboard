<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shop_banners')) {
            Schema::create('shop_banners', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('banner_type')->default('carousel'); // carousel, middle
                $table->string('link_type')->default('none'); // none, product, category, url
                $table->string('link_value')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('shop_categories')) {
            Schema::create('shop_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category_group')->default('product_type'); // quick_link, product_type
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('shop_products')) {
            Schema::create('shop_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('sku')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->decimal('price_max', 12, 2)->nullable();
                $table->string('stock_status')->default('in_stock'); // in_stock, out_of_stock
                $table->string('home_section')->default('none'); // flash_sale, new_arrival, fans_collection, accessories, featured, none
                $table->timestamp('flash_sale_ends_at')->nullable();
                $table->json('storage_options')->nullable();
                $table->json('color_options')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('category_id')->references('id')->on('shop_categories')->nullOnDelete();
            });
        }

        $maxId = DB::table('permissions')->max('id') ?? 120;
        $parentId = $maxId + 1;
        $permissions = [
            ['id' => $parentId, 'name' => 'shop-showcase', 'guard_name' => 'web', 'parent_id' => null],
            ['id' => $parentId + 1, 'name' => 'shop-banner-list', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 2, 'name' => 'shop-banner-add', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 3, 'name' => 'shop-banner-edit', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 4, 'name' => 'shop-banner-delete', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 5, 'name' => 'shop-category-list', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 6, 'name' => 'shop-category-add', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 7, 'name' => 'shop-category-edit', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 8, 'name' => 'shop-category-delete', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 9, 'name' => 'shop-product-list', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 10, 'name' => 'shop-product-add', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 11, 'name' => 'shop-product-edit', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 12, 'name' => 'shop-product-delete', 'guard_name' => 'web', 'parent_id' => $parentId],
        ];

        foreach ($permissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => Carbon::now(),
                    'updated_at' => null,
                ]));
            }
        }

        foreach ($permissions as $perm) {
            $permId = DB::table('permissions')->where('name', $perm['name'])->value('id');
            if ($permId && !DB::table('role_has_permissions')->where('permission_id', $permId)->where('role_id', 1)->exists()) {
                DB::table('role_has_permissions')->insert(['permission_id' => $permId, 'role_id' => 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
        Schema::dropIfExists('shop_banners');
    }
};
