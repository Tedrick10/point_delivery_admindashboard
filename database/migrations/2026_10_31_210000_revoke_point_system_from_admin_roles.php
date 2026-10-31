<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Point System is Super Admin–only. Remove from Admin Panel role assignments
 * (and permission UI catalog is already filtered in PermissionController).
 */
return new class extends Migration
{
    public function up(): void
    {
        $names = [
            'point-system',
            'point-system-list',
            'point-system-show',
            'point-system-add',
            'point-system-edit',
            'point-system-confirm',
        ];

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally empty — Super Admin embeds Point System; Admin roles should not regain these.
    }
};
