<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    private const HUB_EMAILS = [
        'rider.ygn1@demo.local',
        'rider.ygn2@demo.local',
    ];

    private const HUB_PERMISSIONS = [
        'order-list',
        'order-show',
        'order-edit',
        'deliveryman-list',
        'deliveryman-show',
        'deliveryman-add',
        'deliveryman-edit',
    ];

    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'is_dispatch_hub')) {
                    $table->unsignedTinyInteger('is_dispatch_hub')->default(0)->after('created_by_admin');
                }
                if (! Schema::hasColumn('users', 'hub_parent_id')) {
                    $table->unsignedBigInteger('hub_parent_id')->nullable()->after('is_dispatch_hub');
                }
            });
        }

        if (Schema::hasTable('dispatch_order_items')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
                    $table->unsignedBigInteger('hub_user_id')->nullable()->after('delivery_man_id');
                }
                if (! Schema::hasColumn('dispatch_order_items', 'hub_inbox_at')) {
                    $table->timestamp('hub_inbox_at')->nullable()->after('hub_user_id');
                }
                if (! Schema::hasColumn('dispatch_order_items', 'hub_accepted_at')) {
                    $table->timestamp('hub_accepted_at')->nullable()->after('hub_inbox_at');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_dispatch_hub')) {
            DB::table('users')
                ->whereIn('email', self::HUB_EMAILS)
                ->update(['is_dispatch_hub' => 1, 'hub_parent_id' => null]);
        }

        $this->ensureDispatchHubRole();
    }

    public function down(): void
    {
        if (Schema::hasTable('dispatch_order_items')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                if (Schema::hasColumn('dispatch_order_items', 'hub_accepted_at')) {
                    $table->dropColumn('hub_accepted_at');
                }
                if (Schema::hasColumn('dispatch_order_items', 'hub_inbox_at')) {
                    $table->dropColumn('hub_inbox_at');
                }
                if (Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
                    $table->dropColumn('hub_user_id');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'hub_parent_id')) {
                    $table->dropColumn('hub_parent_id');
                }
                if (Schema::hasColumn('users', 'is_dispatch_hub')) {
                    $table->dropColumn('is_dispatch_hub');
                }
            });
        }
    }

    private function ensureDispatchHubRole(): void
    {
        if (! class_exists(Role::class) || ! Schema::hasTable('roles')) {
            return;
        }

        $role = Role::firstOrCreate(
            ['name' => 'dispatch_hub', 'guard_name' => 'web'],
            ['status' => 1]
        );

        $permissionIds = Permission::query()
            ->whereIn('name', self::HUB_PERMISSIONS)
            ->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            $role->syncPermissions($permissionIds);
        }

        $hubIds = DB::table('users')
            ->whereIn('email', self::HUB_EMAILS)
            ->pluck('id');
        if ($hubIds->isEmpty() || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        foreach ($hubIds as $userId) {
            $exists = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        if (function_exists('app') && config('permission.cache.key')) {
            try {
                app('cache')->forget(config('permission.cache.key'));
            } catch (\Throwable $e) {
                // Role cache is best-effort during migrate.
            }
        }
    }
};
