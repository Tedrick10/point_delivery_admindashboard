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
        // Advertisements / Marketing banners
        if (!Schema::hasTable('advertisements')) {
            Schema::create('advertisements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('ad_type')->default('promotion'); // promotion, discount, banner
                $table->string('placement')->default('home'); // home, order, delivery_home
                $table->string('target_app')->default('client'); // client, delivery_man, both
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('approval_status')->default('pending'); // pending, approved, rejected
                $table->string('link_url')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Welcome promotion settings
        if (!Schema::hasTable('welcome_promotions')) {
            Schema::create('welcome_promotions', function (Blueprint $table) {
                $table->id();
                $table->string('title')->default('Welcome Promotion');
                $table->integer('max_orders')->default(10);
                $table->string('discount_type')->default('percentage'); // percentage, fixed
                $table->decimal('discount_value', 10, 2)->default(5);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

            DB::table('welcome_promotions')->insert([
                'title' => 'Welcome Promotion - First 10 Orders',
                'max_orders' => 10,
                'discount_type' => 'percentage',
                'discount_value' => 5,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Order chat messages (for admin monitoring)
        if (!Schema::hasTable('order_chat_messages')) {
            Schema::create('order_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->string('sender_type')->nullable(); // client, delivery_man, admin
                $table->string('way_type')->default('general'); // pickup, delivery, general
                $table->text('message')->nullable();
                $table->string('message_type')->default('text'); // text, image
                $table->string('image_url')->nullable();
                $table->timestamps();
            });
        }

        // User feature columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_vip')) {
                $table->tinyInteger('is_vip')->default(0)->after('status');
            }
            if (!Schema::hasColumn('users', 'welcome_orders_used')) {
                $table->integer('welcome_orders_used')->default(0)->after('is_vip');
            }
            if (!Schema::hasColumn('users', 'is_temp_password')) {
                $table->tinyInteger('is_temp_password')->default(0)->after('welcome_orders_used');
            }
            if (!Schema::hasColumn('users', 'created_by_admin')) {
                $table->tinyInteger('created_by_admin')->default(0)->after('is_temp_password');
            }
        });

        // Order feature columns
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'is_photo_order')) {
                $table->tinyInteger('is_photo_order')->default(0);
            }
            if (!Schema::hasColumn('orders', 'welcome_discount')) {
                $table->decimal('welcome_discount', 10, 2)->default(0);
            }
        });

        // Push notification VIP targeting
        Schema::table('push_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('push_notifications', 'target_type')) {
                $table->string('target_type')->default('all')->after('message'); // all, vip, selected
            }
        });

        // Permissions for new modules
        $maxId = DB::table('permissions')->max('id') ?? 108;
        $parentId = $maxId + 1;
        $permissions = [
            ['id' => $parentId, 'name' => 'advertisement', 'guard_name' => 'web', 'parent_id' => null],
            ['id' => $parentId + 1, 'name' => 'advertisement-list', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 2, 'name' => 'advertisement-add', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 3, 'name' => 'advertisement-edit', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 4, 'name' => 'advertisement-delete', 'guard_name' => 'web', 'parent_id' => $parentId],
            ['id' => $parentId + 5, 'name' => 'welcome-promotion', 'guard_name' => 'web', 'parent_id' => null],
            ['id' => $parentId + 6, 'name' => 'welcome-promotion-list', 'guard_name' => 'web', 'parent_id' => $parentId + 5],
            ['id' => $parentId + 7, 'name' => 'welcome-promotion-edit', 'guard_name' => 'web', 'parent_id' => $parentId + 5],
            ['id' => $parentId + 8, 'name' => 'order-chat', 'guard_name' => 'web', 'parent_id' => null],
            ['id' => $parentId + 9, 'name' => 'order-chat-list', 'guard_name' => 'web', 'parent_id' => $parentId + 8],
        ];

        foreach ($permissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => Carbon::now(),
                    'updated_at' => null,
                ]));
            }
        }

        // Assign to admin role (role_id = 1)
        foreach ($permissions as $perm) {
            $permId = DB::table('permissions')->where('name', $perm['name'])->value('id');
            if ($permId && !DB::table('role_has_permissions')->where('permission_id', $permId)->where('role_id', 1)->exists()) {
                DB::table('role_has_permissions')->insert(['permission_id' => $permId, 'role_id' => 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_chat_messages');
        Schema::dropIfExists('welcome_promotions');
        Schema::dropIfExists('advertisements');

        Schema::table('users', function (Blueprint $table) {
            $cols = ['is_vip', 'welcome_orders_used', 'is_temp_password', 'created_by_admin'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'is_photo_order')) {
                $table->dropColumn('is_photo_order');
            }
            if (Schema::hasColumn('orders', 'welcome_discount')) {
                $table->dropColumn('welcome_discount');
            }
        });

        Schema::table('push_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('push_notifications', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
