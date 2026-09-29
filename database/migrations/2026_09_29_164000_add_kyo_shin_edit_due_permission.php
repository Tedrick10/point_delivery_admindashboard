<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $parentId = DB::table('permissions')->where('name', 'kyo-shin')->value('id');
        if (! $parentId) {
            $parentId = DB::table('permissions')->insertGetId([
                'name' => 'kyo-shin',
                'guard_name' => 'web',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $exists = DB::table('permissions')->where('name', 'kyo-shin-edit-due')->exists();
        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'kyo-shin-edit-due',
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
        $permId = DB::table('permissions')->where('name', 'kyo-shin-edit-due')->value('id');
        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }

        $parentId = DB::table('permissions')->where('name', 'kyo-shin')->value('id');
        if ($parentId) {
            $hasChildren = DB::table('permissions')->where('parent_id', $parentId)->exists();
            if (! $hasChildren) {
                DB::table('permissions')->where('id', $parentId)->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
