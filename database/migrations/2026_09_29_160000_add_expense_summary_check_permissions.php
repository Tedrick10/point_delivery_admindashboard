<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $parentId = DB::table('permissions')->where('name', 'expense-summary')->value('id');
        if (! $parentId) {
            $parentId = DB::table('permissions')->insertGetId([
                'name' => 'expense-summary',
                'guard_name' => 'web',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (['expense-summary-check', 'expense-summary-check-nn', 'expense-summary-check-ss'] as $name) {
            $exists = DB::table('permissions')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }
            DB::table('permissions')->insert([
                'name' => $name,
                'guard_name' => 'web',
                'parent_id' => $parentId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $names = ['expense-summary-check', 'expense-summary-check-nn', 'expense-summary-check-ss'];
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        $parentId = DB::table('permissions')->where('name', 'expense-summary')->value('id');
        if ($parentId) {
            $hasChildren = DB::table('permissions')->where('parent_id', $parentId)->exists();
            if (! $hasChildren) {
                DB::table('permissions')->where('id', $parentId)->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
