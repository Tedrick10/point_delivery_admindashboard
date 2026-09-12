<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const HUB_MDY_MEN = [
        'rider.ygn1@demo.local' => [
            'name' => 'မန္တလေး (MDY)',
            'email' => 'rider.mdy.ygn1@demo.local',
            'phone' => '+95922221901',
        ],
        'rider.ygn2@demo.local' => [
            'name' => 'မန္တလေး (MDY)',
            'email' => 'rider.mdy.ygn2@demo.local',
            'phone' => '+95922222901',
        ],
    ];

    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_mdy_return')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('is_mdy_return')->default(0)->after('is_dispatch_hub');
            });
        }

        if (! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'hub_parent_id')
            || ! Schema::hasColumn('users', 'is_mdy_return')
        ) {
            return;
        }

        $mdyBranchId = (int) (\App\Models\Branch::query()
            ->where('status', 1)
            ->where('name', 'မန္တလေး')
            ->value('id') ?? 0);

        $now = now();
        foreach (self::HUB_MDY_MEN as $hubEmail => $demo) {
            $hub = User::query()->where('email', $hubEmail)->first();
            if (! $hub) {
                continue;
            }

            $user = User::withTrashed()->where('email', $demo['email'])->first();
            $payload = [
                'name' => $demo['name'],
                'username' => strstr($demo['email'], '@', true) ?: $demo['email'],
                'contact_number' => $demo['phone'],
                'user_type' => 'delivery_man',
                'status' => 1,
                'is_dispatch_hub' => 0,
                'is_mdy_return' => 1,
                'hub_parent_id' => (int) $hub->id,
                'branch_id' => $mdyBranchId > 0 ? $mdyBranchId : ((int) ($hub->branch_id ?? 0) ?: null),
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

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            User::withTrashed()
                ->whereIn('email', array_column(self::HUB_MDY_MEN, 'email'))
                ->forceDelete();

            if (Schema::hasColumn('users', 'is_mdy_return')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('is_mdy_return');
                });
            }
        }
    }
};
