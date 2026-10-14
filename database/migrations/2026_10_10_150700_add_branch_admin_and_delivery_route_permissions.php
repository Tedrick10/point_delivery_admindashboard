<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $modules = [
            'branch' => ['list', 'show', 'add', 'edit', 'delete'],
            'branch-admin' => ['list', 'show', 'add', 'edit', 'delete'],
            'delivery-route' => ['list', 'show', 'add', 'edit', 'delete'],
        ];

        $permissionIds = [];

        foreach ($modules as $parentName => $actions) {
            $parentId = DB::table('permissions')->where('name', $parentName)->whereNull('parent_id')->value('id');
            if (! $parentId) {
                $parentId = DB::table('permissions')->insertGetId([
                    'name' => $parentName,
                    'guard_name' => 'web',
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $permissionIds[] = (int) $parentId;

            foreach ($actions as $action) {
                $childName = $parentName.'-'.$action;
                $childId = DB::table('permissions')->where('name', $childName)->value('id');
                if (! $childId) {
                    $childId = DB::table('permissions')->insertGetId([
                        'name' => $childName,
                        'guard_name' => 'web',
                        'parent_id' => $parentId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $currentParent = DB::table('permissions')->where('id', $childId)->value('parent_id');
                    if ((int) $currentParent !== (int) $parentId) {
                        DB::table('permissions')->where('id', $childId)->update([
                            'parent_id' => $parentId,
                            'updated_at' => $now,
                        ]);
                    }
                }
                $permissionIds[] = (int) $childId;
            }
        }

        $roleId = DB::table('roles')
            ->whereRaw('LOWER(name) = ?', ['manager'])
            ->value('id');

        if ($roleId) {
            foreach (array_unique($permissionIds) as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $childNames = [];
        foreach (['branch-admin', 'delivery-route'] as $parentName) {
            foreach (['list', 'show', 'add', 'edit', 'delete'] as $action) {
                $childNames[] = $parentName.'-'.$action;
            }
        }

        $ids = DB::table('permissions')->whereIn('name', $childNames)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        foreach (['branch-admin', 'delivery-route'] as $parentName) {
            $parentId = DB::table('permissions')->where('name', $parentName)->whereNull('parent_id')->value('id');
            if (! $parentId) {
                continue;
            }
            $hasChildren = DB::table('permissions')->where('parent_id', $parentId)->exists();
            if (! $hasChildren) {
                DB::table('role_has_permissions')->where('permission_id', $parentId)->delete();
                DB::table('model_has_permissions')->where('permission_id', $parentId)->delete();
                DB::table('permissions')->where('id', $parentId)->delete();
            }
        }

        // Do not remove legacy `branch` permissions on rollback.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
