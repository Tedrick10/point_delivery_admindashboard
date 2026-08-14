<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'staff' => [
                'order-list', 'order-show', 'client-list', 'client-show',
                'deliveryman-list', 'deliveryman-show', 'push notification-list',
            ],
            'accountant' => [
                'order-list', 'order-show', 'payment-list', 'withdrawrequest-list',
                'wallet-list', 'report-list',
            ],
            'manager' => [
                'order-list', 'order-show', 'order-add', 'order-edit',
                'client-list', 'client-show', 'client-add', 'client-edit',
                'deliveryman-list', 'deliveryman-show', 'deliveryman-add', 'deliveryman-edit',
                'subadmin-list', 'subadmin-add', 'subadmin-edit',
                'role-list', 'permission-list',
                'push notification-list', 'push notification-add',
                'report-list', 'payment-list',
            ],
        ];

        foreach ($roles as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['status' => 1, 'created_at' => Carbon::now(), 'updated_at' => null]
            );

            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
            $role->syncPermissions($permissionIds);
        }

        if (!Permission::where('name', 'push notification-edit')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'push notification-edit',
                'guard_name' => 'web',
                'parent_id' => Permission::where('name', 'push notification')->value('id'),
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ]);
        }
    }
}
