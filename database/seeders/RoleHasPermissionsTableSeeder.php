<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleHasPermissionsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('role_has_permissions')->delete();

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ?? 1;
        $permissionIds = DB::table('permissions')->orderBy('id')->pluck('id');

        $rows = $permissionIds->map(function ($permissionId) use ($adminRoleId) {
            return [
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ];
        })->all();

        if (!empty($rows)) {
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('role_has_permissions')->insert($chunk);
            }
        }
    }
}
