<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'hub_parent_id')
            || ! Schema::hasColumn('users', 'is_dispatch_hub')
        ) {
            return;
        }

        $hubs = [
            'rider.ygn1@demo.local' => [
                ['name' => 'NLS Rider 1', 'email' => 'rider.nls1@demo.local', 'phone' => '+95922221001'],
                ['name' => 'NLS Rider 2', 'email' => 'rider.nls2@demo.local', 'phone' => '+95922221002'],
            ],
            'rider.ygn2@demo.local' => [
                ['name' => 'M2M Rider 1', 'email' => 'rider.m2m1@demo.local', 'phone' => '+95922222001'],
                ['name' => 'M2M Rider 2', 'email' => 'rider.m2m2@demo.local', 'phone' => '+95922222002'],
            ],
        ];

        $now = now();
        foreach ($hubs as $hubEmail => $riders) {
            $hub = User::query()->where('email', $hubEmail)->first();
            if (! $hub) {
                continue;
            }

            foreach ($riders as $demo) {
                $user = User::withTrashed()->where('email', $demo['email'])->first();
                $payload = [
                    'name' => $demo['name'],
                    'username' => strstr($demo['email'], '@', true) ?: $demo['email'],
                    'contact_number' => $demo['phone'],
                    'user_type' => 'delivery_man',
                    'status' => 1,
                    'is_dispatch_hub' => 0,
                    'hub_parent_id' => (int) $hub->id,
                    'branch_id' => (int) ($hub->branch_id ?? 0) ?: null,
                    'country_id' => (int) ($hub->country_id ?? 1) ?: 1,
                    'city_id' => (int) ($hub->city_id ?? 1) ?: 1,
                    'rider_work_on' => true,
                    'email_verified_at' => $now,
                    'otp_verify_at' => $now,
                    'document_verified_at' => $now,
                    'is_autoverified_email' => 1,
                    'is_autoverified_mobile' => 1,
                    'is_autoverified_document' => 1,
                    'deleted_at' => null,
                ];

                if ($user) {
                    $user->fill($payload)->save();
                } else {
                    $payload['email'] = $demo['email'];
                    $payload['password'] = Hash::make('12345678');
                    $user = User::create($payload);
                }

                if ($user && method_exists($user, 'assignRole') && ! $user->hasRole('delivery_man')) {
                    $user->assignRole('delivery_man');
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        User::withTrashed()
            ->whereIn('email', [
                'rider.nls1@demo.local',
                'rider.nls2@demo.local',
                'rider.m2m1@demo.local',
                'rider.m2m2@demo.local',
            ])
            ->forceDelete();
    }
};
