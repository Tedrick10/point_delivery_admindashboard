<?php

namespace Database\Seeders;

use App\Http\Controllers\PermissionController;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffRoleSeeder extends Seeder
{
    /**
     * Accountant may use Admin Panel modules except staff/setup modules.
     *
     * @var array<int, string>
     */
    public const ACCOUNTANT_EXCLUDED_PARENTS = [
        'subadmin',       // Account Creation
        'users',          // Online Shop List
        'deliveryman',    // Deliveryman
        'branch',         // Branch
        'branch-admin',   // Branch Admins
        'delivery-route', // From/To/City
    ];

    public function run(): void
    {
        $roles = [
            'staff' => [
                'order-list', 'order-show', 'client-list', 'client-show',
                'deliveryman-list', 'deliveryman-show', 'push notification-list',
            ],
            'accountant' => $this->accountantPermissionNames(),
            'manager' => [
                'order-list', 'order-show', 'order-add', 'order-edit',
                'client-list', 'client-show', 'client-add', 'client-edit',
                'deliveryman-list', 'deliveryman-show', 'deliveryman-add', 'deliveryman-edit',
                'subadmin-list', 'subadmin-add', 'subadmin-edit', 'subadmin-delete',
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

        if (! Permission::where('name', 'push notification-edit')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'push notification-edit',
                'guard_name' => 'web',
                'parent_id' => Permission::where('name', 'push notification')->value('id'),
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ]);
        }
    }

    /**
     * All Admin Panel card permissions except Account Creation / OS / Deliveryman /
     * Branch / Branch Admins / From-To-City.
     *
     * @return array<int, string>
     */
    public function accountantPermissionNames(): array
    {
        $exclude = array_fill_keys(self::ACCOUNTANT_EXCLUDED_PARENTS, true);
        $modules = app(PermissionController::class)->adminPanelPermissionModules();

        $names = [];
        foreach ($modules as $module) {
            $parent = (string) ($module['key'] ?? '');
            if ($parent === '' || isset($exclude[$parent])) {
                continue;
            }
            foreach ($module['permissions'] ?? [] as $permission) {
                $name = trim((string) ($permission['name'] ?? ''));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }
}
