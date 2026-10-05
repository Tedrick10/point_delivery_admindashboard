<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Pending Online Shop signups for Admin → Online Shop List → Pending tab.
 */
class OnlineShopPendingSampleSeeder extends Seeder
{
    private const TARGET = 30;

    private const SHOPS = [
        'Mingalar Fashion',
        'Shwe Pyi Cosmetics',
        'Golden Flower Shop',
        'Yadanar Accessories',
        'Cherry Beauty OS',
        'Mandalay Mart',
        'Aung Thukha Store',
        'Hlaing Mini Shop',
        'Padamyar Boutique',
        'Thiri Household',
        'Kyi Kyi Gift Shop',
        'Myat Noe Online',
        'Soe San Phone Shop',
        'Wai Wai Kids Wear',
        'Nay Chi Grocery',
        'Shwe Taung Pharmacy',
        'Lwin Oo Stationery',
        'May Myat Fashion',
        'Zaw Gyi Electronics',
        'Su Su Home Decor',
        'Thet Naing Watch',
        'Aye Aye Bag Shop',
        'Ko Ko Sports',
        'Nandar Skincare',
        'Hnin Si Florist',
        'Min Min Auto Parts',
        'Pann Nu Tea Shop',
        'Moe Moe Bakery OS',
        'Kyaw Kyaw Tools',
        'Hla Hla Baby Care',
    ];

    public function run(): void
    {
        $branch = Branch::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('name', 'မန္တလေး')->orWhere('name', 'like', '%MDY%');
            })
            ->orderBy('id')
            ->first()
            ?? Branch::query()->where('status', 1)->orderBy('id')->first();

        if (! $branch) {
            $this->command?->warn('No branch for pending OS seeder.');

            return;
        }

        $template = User::query()
            ->where('user_type', 'client')
            ->whereNotNull('city_id')
            ->orderBy('id')
            ->first(['city_id', 'country_id']);

        $cityId = $template?->city_id;
        $countryId = $template?->country_id;
        $now = Carbon::now('Asia/Yangon');

        $created = 0;
        foreach (self::SHOPS as $i => $name) {
            $n = $i + 1;
            $email = 'pending.os.'.str_pad((string) $n, 2, '0', STR_PAD_LEFT).'@point.demo';
            $username = 'pending_os_'.$n;
            $createdAt = $now->copy()->subHours(self::TARGET - $n)->utc();

            // First half: assigned to Mandalay. Second half: app signup (no branch).
            $branchId = $n <= 15 ? (int) $branch->id : null;

            $payload = [
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'user_type' => 'client',
                'status' => 0,
                'approval_status' => User::APPROVAL_PENDING,
                'branch_id' => $branchId,
                'city_id' => $cityId,
                'country_id' => $countryId,
                'contact_number' => '09'.str_pad((string) (400000000 + $n), 9, '0', STR_PAD_LEFT),
                'created_by_admin' => 0,
                'is_kyo_shin' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            $existing = User::withTrashed()
                ->where(function ($q) use ($email, $username) {
                    $q->where('email', $email)->orWhere('username', $username);
                })
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                if (empty($existing->password)) {
                    $payload['password'] = Hash::make('password');
                }
                $existing->fill($payload)->save();
                $user = $existing;
            } else {
                $payload['password'] = Hash::make('password');
                $user = User::query()->create($payload);
            }

            try {
                if (! $user->hasRole('client')) {
                    $user->assignRole('client');
                }
            } catch (\Throwable $e) {
            }

            $created++;
        }

        $pending = User::query()
            ->where('user_type', 'client')
            ->where(function ($q) {
                $q->where('approval_status', User::APPROVAL_PENDING)
                    ->orWhereNull('approval_status')
                    ->orWhere('approval_status', '');
            })
            ->count();

        $this->command?->info("Pending OS demo shops: {$created} upserted, list pending total {$pending}.");
    }
}
