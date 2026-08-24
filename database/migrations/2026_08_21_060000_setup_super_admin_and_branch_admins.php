<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $now = now();

        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        if ((int) ($superAdminRole->status ?? 0) !== 1) {
            $superAdminRole->status = 1;
            $superAdminRole->save();
        }

        $permissionIds = Permission::query()->pluck('id')->all();
        if (! empty($permissionIds)) {
            $superAdminRole->syncPermissions($permissionIds);
        }

        $mdyBranchId = (int) (DB::table('branches')->where('name', 'MDY')->value('id')
            ?? DB::table('branches')->where('name', 'like', '%MDY%')->value('id')
            ?? 0);

        if ($mdyBranchId > 0) {
            DB::table('users')
                ->where('user_type', 'admin')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', 0);
                })
                ->update([
                    'branch_id' => $mdyBranchId,
                    'updated_at' => $now,
                ]);
        }

        $email = 'superadmin@admin.com';
        $existingId = DB::table('users')->where('email', $email)->value('id');

        if (! $existingId) {
            $existingId = DB::table('users')->insertGetId([
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'contact_number' => '09999999999',
                'address' => null,
                'email' => $email,
                'password' => Hash::make('12345678'),
                'email_verified_at' => $now,
                'user_type' => 'super_admin',
                'branch_id' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('users')->where('id', $existingId)->update([
                'user_type' => 'super_admin',
                'branch_id' => null,
                'status' => 1,
                'updated_at' => $now,
            ]);
        }

        $roleId = (int) $superAdminRole->id;
        $hasRole = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $existingId)
            ->exists();

        if (! $hasRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $existingId,
            ]);
        }

        // Drop leftover admin role assignment if any (keep only super_admin).
        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');
        if ($adminRoleId) {
            DB::table('model_has_roles')
                ->where('role_id', $adminRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $existingId)
                ->delete();
        }
    }

    public function down(): void
    {
        $userId = DB::table('users')->where('email', 'superadmin@admin.com')->value('id');
        if ($userId) {
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->delete();
            DB::table('users')->where('id', $userId)->delete();
        }

        $role = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($role) {
            $role->permissions()->detach();
            $role->delete();
        }
    }
};
