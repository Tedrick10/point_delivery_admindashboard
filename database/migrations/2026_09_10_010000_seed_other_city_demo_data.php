<?php

use App\Models\Branch;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('branches')) {
            return;
        }

        $branchIds = Branch::query()
            ->whereIn('name', ['မန္တလေး', 'ရန်ကုန်', 'လားရှိုး', 'တောင်ကြီး', 'ပြင်ဦးလွင်', 'Food(မန္တလေး)'])
            ->pluck('id', 'name');

        $cityIds = $this->ensureCities();
        $this->assignExistingClients($branchIds, $cityIds);
        $this->seedOnlineShops($branchIds, $cityIds);
        $this->seedPickupRiders($branchIds, $cityIds);
        $this->relocateHubCities($cityIds);
        $this->seedYangonDemoOrders($branchIds, $cityIds);
    }

    /**
     * @return array<string,int>
     */
    private function ensureCities(): array
    {
        $template = City::query()->where('name', 'Yangon')->first()
            ?: City::query()->where('name', 'MDY')->first();

        $names = [
            'Yangon',
            'Hlaing',
            'Yankin',
            'Lashio',
            'Taunggyi',
            'Pyin Oo Lwin',
            'MDY',
            'Food',
        ];

        $now = now();
        foreach ($names as $name) {
            $city = City::withTrashed()->where('name', $name)->first();
            $payload = [
                'name' => $name,
                'country_id' => (int) ($city->country_id ?? $template->country_id ?? 1) ?: 1,
                'status' => 1,
                'deleted_at' => null,
                'fixed_charges' => $city->fixed_charges ?? $template->fixed_charges ?? 1000,
                'cancel_charges' => $city->cancel_charges ?? $template->cancel_charges ?? 1000,
                'min_distance' => $city->min_distance ?? $template->min_distance ?? 1,
                'min_weight' => $city->min_weight ?? $template->min_weight ?? 1,
                'per_distance_charges' => $city->per_distance_charges ?? $template->per_distance_charges ?? 0,
                'per_weight_charges' => $city->per_weight_charges ?? $template->per_weight_charges ?? 0,
                'commission_type' => $city->commission_type ?? $template->commission_type ?? 'percentage',
                'admin_commission' => $city->admin_commission ?? $template->admin_commission ?? 0,
                'updated_at' => $now,
            ];

            if ($city) {
                $city->fill($payload)->save();
            } else {
                $payload['created_at'] = $now;
                City::query()->create($payload);
            }
        }

        return City::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<string,int>  $branchIds
     * @param  array<string,int>  $cityIds
     */
    private function assignExistingClients($branchIds, array $cityIds): void
    {
        $mdyId = (int) ($branchIds['မန္တလေး'] ?? 0);
        $ygnId = (int) ($branchIds['ရန်ကုန်'] ?? 0);
        $foodId = (int) ($branchIds['Food(မန္တလေး)'] ?? 0);
        $yangonCityIds = array_values(array_filter([
            (int) ($cityIds['Yangon'] ?? 0),
            (int) (City::query()->where('name', 'Ygn to Ygn')->value('id') ?? 0),
            (int) ($cityIds['Hlaing'] ?? 0),
            (int) ($cityIds['Yankin'] ?? 0),
        ]));
        $foodCityId = (int) ($cityIds['Food'] ?? 0);

        User::query()
            ->where('user_type', 'client')
            ->where(function ($query) {
                $query->whereNull('branch_id')->orWhere('branch_id', 0);
            })
            ->each(function (User $user) use ($mdyId, $ygnId, $foodId, $yangonCityIds, $foodCityId) {
                $cityId = (int) ($user->city_id ?? 0);
                if ($foodId && $cityId === $foodCityId) {
                    $user->branch_id = $foodId;
                } elseif ($ygnId && in_array($cityId, $yangonCityIds, true)) {
                    $user->branch_id = $ygnId;
                } elseif ($mdyId) {
                    $user->branch_id = $mdyId;
                }
                $user->save();
            });
    }

    /**
     * @param  \Illuminate\Support\Collection<string,int>  $branchIds
     * @param  array<string,int>  $cityIds
     */
    private function seedOnlineShops($branchIds, array $cityIds): void
    {
        $shops = [
            [
                'branch' => 'ရန်ကုန်',
                'city' => 'Hlaing',
                'name' => 'Su Hlaing',
                'email' => 'os.ygn.suhlaing@demo.local',
                'phone' => '+95922223001',
                'address' => 'Hlaing Township, Yangon',
            ],
            [
                'branch' => 'ရန်ကုန်',
                'city' => 'Yankin',
                'name' => 'Min Khant',
                'email' => 'os.ygn.minkhant@demo.local',
                'phone' => '+95922223002',
                'address' => 'Yankin Township, Yangon',
            ],
            [
                'branch' => 'လားရှိုး',
                'city' => 'Lashio',
                'name' => 'Nang Hom',
                'email' => 'os.lso.nanghom@demo.local',
                'phone' => '+95933331001',
                'address' => 'Lashio Market, Lashio',
            ],
            [
                'branch' => 'တောင်ကြီး',
                'city' => 'Taunggyi',
                'name' => 'Sai Lin',
                'email' => 'os.tgy.sailin@demo.local',
                'phone' => '+95944441001',
                'address' => 'Myo Ma, Taunggyi',
            ],
            [
                'branch' => 'ပြင်ဦးလွင်',
                'city' => 'Pyin Oo Lwin',
                'name' => 'May Thu',
                'email' => 'os.pol.maythu@demo.local',
                'phone' => '+95955551001',
                'address' => 'Purcell Street, Pyin Oo Lwin',
            ],
        ];

        $now = now();
        foreach ($shops as $shop) {
            $branchId = (int) ($branchIds[$shop['branch']] ?? 0);
            $cityId = (int) ($cityIds[$shop['city']] ?? 0);
            if ($branchId <= 0 || $cityId <= 0) {
                continue;
            }

            $user = User::withTrashed()->where('email', $shop['email'])->first();
            $payload = [
                'name' => $shop['name'],
                'username' => strstr($shop['email'], '@', true) ?: $shop['email'],
                'contact_number' => $shop['phone'],
                'address' => $shop['address'],
                'user_type' => 'client',
                'status' => 1,
                'approval_status' => User::APPROVAL_APPROVED,
                'branch_id' => $branchId,
                'city_id' => $cityId,
                'country_id' => 1,
                'created_by_admin' => 1,
                'is_temp_password' => 1,
                'email_verified_at' => $now,
                'otp_verify_at' => $now,
                'deleted_at' => null,
            ];

            if ($user) {
                $user->fill($payload)->save();
            } else {
                $payload['email'] = $shop['email'];
                $payload['password'] = Hash::make('12345678');
                $payload['referral_code'] = function_exists('generateRandomCode')
                    ? generateRandomCode()
                    : strtoupper(substr(md5($shop['email']), 0, 8));
                $user = User::create($payload);
            }

            if ($user && method_exists($user, 'assignRole') && ! $user->hasRole('client')) {
                $user->assignRole('client');
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string,int>  $branchIds
     * @param  array<string,int>  $cityIds
     */
    private function seedPickupRiders($branchIds, array $cityIds): void
    {
        $riders = [
            ['branch' => 'ရန်ကုန်', 'city' => 'Yangon', 'name' => 'YGN Pickup 1', 'email' => 'rider.ygn.pickup1@demo.local', 'phone' => '+95922224001'],
            ['branch' => 'ရန်ကုန်', 'city' => 'Yangon', 'name' => 'YGN Pickup 2', 'email' => 'rider.ygn.pickup2@demo.local', 'phone' => '+95922224002'],
            ['branch' => 'လားရှိုး', 'city' => 'Lashio', 'name' => 'LSO Pickup 1', 'email' => 'rider.lso.pickup1@demo.local', 'phone' => '+95933332001'],
            ['branch' => 'တောင်ကြီး', 'city' => 'Taunggyi', 'name' => 'TGY Pickup 1', 'email' => 'rider.tgy.pickup1@demo.local', 'phone' => '+95944442001'],
            ['branch' => 'ပြင်ဦးလွင်', 'city' => 'Pyin Oo Lwin', 'name' => 'POL Pickup 1', 'email' => 'rider.pol.pickup1@demo.local', 'phone' => '+95955552001'],
        ];

        $now = now();
        foreach ($riders as $demo) {
            $branchId = (int) ($branchIds[$demo['branch']] ?? 0);
            $cityId = (int) ($cityIds[$demo['city']] ?? $cityIds['Yangon'] ?? 0);
            if ($branchId <= 0) {
                continue;
            }

            $user = User::withTrashed()->where('email', $demo['email'])->first();
            $payload = [
                'name' => $demo['name'],
                'username' => strstr($demo['email'], '@', true) ?: $demo['email'],
                'contact_number' => $demo['phone'],
                'user_type' => 'delivery_man',
                'status' => 1,
                'branch_id' => $branchId,
                'city_id' => $cityId ?: null,
                'country_id' => 1,
                'rider_work_on' => true,
                'email_verified_at' => $now,
                'otp_verify_at' => $now,
                'document_verified_at' => $now,
                'is_autoverified_email' => 1,
                'is_autoverified_mobile' => 1,
                'is_autoverified_document' => 1,
                'deleted_at' => null,
            ];
            if (Schema::hasColumn('users', 'is_dispatch_hub')) {
                $payload['is_dispatch_hub'] = 0;
            }
            if (Schema::hasColumn('users', 'hub_parent_id')) {
                $payload['hub_parent_id'] = null;
            }

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

        User::query()
            ->where('user_type', 'delivery_man')
            ->where('email', 'like', 'rider.%@demo.local')
            ->update(['rider_work_on' => true]);
    }

    /**
     * @param  array<string,int>  $cityIds
     */
    private function relocateHubCities(array $cityIds): void
    {
        $yangonCityId = (int) ($cityIds['Yangon'] ?? 0);
        if ($yangonCityId <= 0 || ! Schema::hasColumn('users', 'is_dispatch_hub')) {
            return;
        }

        User::query()
            ->where('is_dispatch_hub', 1)
            ->update(['city_id' => $yangonCityId]);
    }

    /**
     * @param  \Illuminate\Support\Collection<string,int>  $branchIds
     * @param  array<string,int>  $cityIds
     */
    private function seedYangonDemoOrders($branchIds, array $cityIds): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $pickup = User::query()->where('email', 'rider.ygn.pickup1@demo.local')->first();
        $shops = User::query()
            ->whereIn('email', [
                'os.ygn.suhlaing@demo.local',
                'os.ygn.minkhant@demo.local',
            ])
            ->get()
            ->keyBy('email');

        if (! $pickup || $shops->isEmpty()) {
            return;
        }

        $yangonNow = now('Asia/Yangon');
        foreach ($shops as $email => $client) {
            $marker = 'YGN-DEMO-'.$client->id;
            $exists = Order::withTrashed()
                ->where('client_id', $client->id)
                ->where('description', $marker)
                ->exists();
            if ($exists) {
                continue;
            }

            $order = Order::create([
                'client_id' => $client->id,
                'pickup_point' => [
                    'name' => $client->name,
                    'contact_number' => $client->contact_number,
                    'address' => $client->address,
                ],
                'delivery_point' => [
                    'name' => '',
                    'contact_number' => '',
                    'address' => '',
                ],
                'is_text_order' => 1,
                'is_photo_order' => 0,
                'is_shop_order' => 0,
                'is_gate_order' => 0,
                'parcel_type' => 'စာဖြင့် အော်ဒါ',
                'total_weight' => 1,
                'total_parcel' => 1,
                'payment_collect_from' => 'on_pickup',
                'description' => $marker,
                'delivery_man_id' => (int) $pickup->id,
                'status' => 'courier_assigned',
                'country_id' => 1,
                'city_id' => (int) ($client->city_id ?: ($cityIds['Yangon'] ?? 0)),
                'date' => $yangonNow->toDateTimeString(),
                'pickup_datetime' => $yangonNow->toDateTimeString(),
                'delivery_datetime' => $yangonNow->copy()->addDay()->toDateTimeString(),
                'total_amount' => 0,
                'fixed_charges' => 0,
                'weight_charge' => 0,
                'distance_charge' => 0,
                'vehicle_charge' => 0,
                'currency' => 'MMK',
                'milisecond' => 'YGN'.$client->id.now()->format('His'),
                'assign_datetime' => now(),
            ]);

            if (class_exists(\App\Services\TextOrderDispatchService::class)) {
                try {
                    app(\App\Services\TextOrderDispatchService::class)->seedBlankParcels($order->fresh());
                } catch (\Throwable $e) {
                    // Demo seed should not block migrate.
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $emails = [
            'os.ygn.suhlaing@demo.local',
            'os.ygn.minkhant@demo.local',
            'os.lso.nanghom@demo.local',
            'os.tgy.sailin@demo.local',
            'os.pol.maythu@demo.local',
            'rider.ygn.pickup1@demo.local',
            'rider.ygn.pickup2@demo.local',
            'rider.lso.pickup1@demo.local',
            'rider.tgy.pickup1@demo.local',
            'rider.pol.pickup1@demo.local',
        ];

        $clientIds = User::withTrashed()->whereIn('email', [
            'os.ygn.suhlaing@demo.local',
            'os.ygn.minkhant@demo.local',
        ])->pluck('id');

        if ($clientIds->isNotEmpty() && Schema::hasTable('orders')) {
            Order::withTrashed()
                ->whereIn('client_id', $clientIds)
                ->where('description', 'like', 'YGN-DEMO-%')
                ->forceDelete();
        }

        User::withTrashed()->whereIn('email', $emails)->forceDelete();
    }
};
