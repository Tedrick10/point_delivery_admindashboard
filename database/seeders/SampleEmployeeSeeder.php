<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleEmployeeSeeder extends Seeder
{
    /**
     * Create sample Account Creation (employee) users for staff roles.
     */
    public function run(): void
    {
        $password = bcrypt('12345678');

        $employees = [
            // Managers
            [
                'name' => 'Kyaw Zin',
                'username' => 'kyaw.manager',
                'email' => 'kyaw.manager@pds.local',
                'contact_number' => '09110000001',
                'user_type' => 'manager',
            ],
            [
                'name' => 'Aye Aye',
                'username' => 'aye.manager',
                'email' => 'aye.manager@pds.local',
                'contact_number' => '09110000002',
                'user_type' => 'manager',
            ],
            [
                'name' => 'Hla Hla',
                'username' => 'hla.manager',
                'email' => 'hla.manager@pds.local',
                'contact_number' => '09110000003',
                'user_type' => 'manager',
            ],
            // Accountants
            [
                'name' => 'Su Su',
                'username' => 'su.accountant',
                'email' => 'su.accountant@pds.local',
                'contact_number' => '09110000004',
                'user_type' => 'accountant',
            ],
            [
                'name' => 'Min Min',
                'username' => 'min.accountant',
                'email' => 'min.accountant@pds.local',
                'contact_number' => '09110000005',
                'user_type' => 'accountant',
            ],
            [
                'name' => 'Thiri Aung',
                'username' => 'thiri.accountant',
                'email' => 'thiri.accountant@pds.local',
                'contact_number' => '09110000006',
                'user_type' => 'accountant',
            ],
            // Staff
            [
                'name' => 'Zaw Zaw',
                'username' => 'zaw.staff',
                'email' => 'zaw.staff@pds.local',
                'contact_number' => '09110000007',
                'user_type' => 'staff',
            ],
            [
                'name' => 'Moe Moe',
                'username' => 'moe.staff',
                'email' => 'moe.staff@pds.local',
                'contact_number' => '09110000008',
                'user_type' => 'staff',
            ],
            [
                'name' => 'Lin Lin',
                'username' => 'lin.staff',
                'email' => 'lin.staff@pds.local',
                'contact_number' => '09110000009',
                'user_type' => 'staff',
            ],
            [
                'name' => 'Nandar',
                'username' => 'nandar.staff',
                'email' => 'nandar.staff@pds.local',
                'contact_number' => '09110000010',
                'user_type' => 'staff',
            ],
        ];

        foreach ($employees as $data) {
            if (! Role::where('name', $data['user_type'])->exists()) {
                $this->command?->warn("Role [{$data['user_type']}] missing — skipped {$data['username']}");
                continue;
            }

            $user = User::withTrashed()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'contact_number' => $data['contact_number'],
                    'password' => $password,
                    'user_type' => $data['user_type'],
                    'status' => 1,
                    'email_verified_at' => now(),
                    'deleted_at' => null,
                ]
            );

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$data['user_type']]);
            } else {
                $user->assignRole($data['user_type']);
            }
        }

        $this->command?->info('Sample employees seeded (password: 12345678).');
    }
}
