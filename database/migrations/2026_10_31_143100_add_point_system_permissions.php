<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $parentName = 'point-system';
        $actions = ['list', 'show', 'add', 'edit', 'confirm'];

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

        $permissionIds = [(int) $parentId];

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
                DB::table('permissions')->where('id', $childId)->update([
                    'parent_id' => $parentId,
                    'updated_at' => $now,
                ]);
            }
            $permissionIds[] = (int) $childId;
        }

        // Manager role gets follow-up list/show; give/confirm stay SuperAdmin-gated in code.
        $roleId = DB::table('roles')
            ->whereRaw('LOWER(name) = ?', ['manager'])
            ->value('id');

        if ($roleId) {
            foreach ([$parentId, DB::table('permissions')->where('name', 'point-system-list')->value('id'), DB::table('permissions')->where('name', 'point-system-show')->value('id')] as $permissionId) {
                if (! $permissionId) {
                    continue;
                }
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
        $names = [
            'point-system-list',
            'point-system-show',
            'point-system-add',
            'point-system-edit',
            'point-system-confirm',
            'point-system',
        ];

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
