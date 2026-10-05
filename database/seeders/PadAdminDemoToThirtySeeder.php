<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ExpenseCard;
use App\Models\ExpenseItem;
use App\Models\HrOfficeSalaryRow;
use App\Models\HrStaff;
use App\Models\OsCashPayout;
use App\Models\OsReceiveSettlement;
use App\Models\OsSettlementBatch;
use App\Models\RiderRemit;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ensure Admin list screens have at least 30 demo rows after sample seeders.
 */
class PadAdminDemoToThirtySeeder extends Seeder
{
    private const NEED = 30;

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
            $this->command?->warn('No branch for demo pad seeder.');

            return;
        }

        $branchId = (int) $branch->id;
        $adminId = (int) (User::query()->whereIn('user_type', ['admin', 'super_admin', 'demo_admin'])->value('id') ?? 1);
        $today = Carbon::now('Asia/Yangon')->toDateString();
        $listDay = function_exists('yangonSettlementDefaultDate')
            ? yangonSettlementDefaultDate('Y-m-d')
            : Carbon::now('Asia/Yangon')->subDay()->toDateString();

        $this->padClients($branchId);
        $this->padPendingClients($branchId);
        $this->padRiders($branchId);
        $this->padExpenses($branchId, $adminId, $today);
        $this->padShopProducts();
        $this->padHrStaff($branchId, $today);
        $this->padRiderRemits($branchId, $adminId, $today);
        $this->padCashPayouts($branchId, $adminId, $listDay);
        $this->padOsReceive($branchId, $adminId, $listDay);

        $this->command?->info('Padded Admin demo lists to at least '.self::NEED.' rows.');
    }

    protected function padClients(int $branchId): void
    {
        $clients = User::query()->where('user_type', 'client')->where('status', 1)->get();
        $i = 1;
        while ($clients->count() < self::NEED) {
            $email = 'demo.os.'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'@point.demo';
            $existing = User::withTrashed()->where('email', $email)->first();
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->fill([
                    'name' => 'Demo OS '.$i,
                    'user_type' => 'client',
                    'status' => 1,
                    'approval_status' => 'approved',
                    'branch_id' => $branchId,
                    'contact_number' => '09'.str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT),
                ])->save();
                $clients->push($existing);
                $i++;
                continue;
            }

            $user = User::query()->create([
                'name' => 'Demo OS '.$i,
                'email' => $email,
                'username' => 'demo_os_'.$i.'_'.Str::lower(Str::random(4)),
                'password' => Hash::make('password'),
                'user_type' => 'client',
                'status' => 1,
                'approval_status' => 'approved',
                'branch_id' => $branchId,
                'contact_number' => '09'.str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT),
                'created_by_admin' => 1,
            ]);
            try {
                $user->assignRole('client');
            } catch (\Throwable $e) {
            }
            $clients->push($user);
            $i++;
        }

        $this->command?->info('OS clients: '.$clients->count());
    }

    protected function padPendingClients(int $branchId): void
    {
        $pending = User::query()
            ->where('user_type', 'client')
            ->where(function ($q) {
                $q->where('approval_status', User::APPROVAL_PENDING)
                    ->orWhereNull('approval_status')
                    ->orWhere('approval_status', '');
            })
            ->count();

        $i = 1;
        while ($pending < self::NEED) {
            $email = 'pending.os.'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'@point.demo';
            $username = 'pending_os_'.$i;
            $existing = User::withTrashed()
                ->where(function ($q) use ($email, $username) {
                    $q->where('email', $email)->orWhere('username', $username);
                })
                ->first();

            $payload = [
                'name' => 'Pending OS '.$i,
                'email' => $email,
                'username' => $username,
                'user_type' => 'client',
                'status' => 0,
                'approval_status' => User::APPROVAL_PENDING,
                'branch_id' => $i <= 15 ? $branchId : null,
                'contact_number' => '09'.str_pad((string) (400000000 + $i), 9, '0', STR_PAD_LEFT),
                'created_by_admin' => 0,
                'is_kyo_shin' => 0,
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                if (empty($existing->password)) {
                    $payload['password'] = Hash::make('password');
                }
                $existing->fill($payload)->save();
            } else {
                $payload['password'] = Hash::make('password');
                $user = User::query()->create($payload);
                try {
                    $user->assignRole('client');
                } catch (\Throwable $e) {
                }
            }

            $pending = User::query()
                ->where('user_type', 'client')
                ->where(function ($q) {
                    $q->where('approval_status', User::APPROVAL_PENDING)
                        ->orWhereNull('approval_status')
                        ->orWhere('approval_status', '');
                })
                ->count();
            $i++;
            if ($i > 80) {
                break;
            }
        }

        $this->command?->info('Pending OS clients: '.$pending);
    }

    protected function padRiders(int $branchId): void
    {
        $template = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('id')
            ->first();

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
            })
            ->where(function ($q) {
                $q->whereNull('is_mdy_return')->orWhere('is_mdy_return', 0);
            })
            ->where(function ($q) {
                $q->whereNull('hub_parent_id')->orWhere('hub_parent_id', 0);
            })
            ->get();

        $i = 1;
        while ($riders->count() < self::NEED) {
            $email = 'demo.rider.'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'@point.demo';
            $existing = User::withTrashed()->where('email', $email)->first();
            $payload = [
                'name' => 'Demo Rider '.$i,
                'email' => $email,
                'username' => 'demo_rider_'.$i,
                'password' => Hash::make('12345678'),
                'user_type' => 'delivery_man',
                'status' => 1,
                'approval_status' => 'approved',
                'branch_id' => $branchId,
                'contact_number' => '09'.str_pad((string) (300000000 + $i), 9, '0', STR_PAD_LEFT),
                'is_dispatch_hub' => 0,
                'is_mdy_return' => 0,
                'hub_parent_id' => null,
                'created_by_admin' => 1,
                'country_id' => $template?->country_id,
                'city_id' => $template?->city_id,
            ];
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->fill($payload)->save();
                $riders->push($existing);
            } else {
                $user = User::query()->create($payload);
                try {
                    $user->assignRole('delivery_man');
                } catch (\Throwable $e) {
                }
                $riders->push($user);
            }
            $i++;
        }

        $this->command?->info('Delivery men: '.$riders->count());
    }

    protected function padExpenses(int $branchId, int $adminId, string $today): void
    {
        $card = ExpenseCard::query()
            ->whereDate('expense_date', $today)
            ->where('branch_id', $branchId)
            ->first();

        if (! $card) {
            $card = ExpenseCard::query()->create([
                'expense_date' => $today,
                'branch_id' => $branchId,
                'total_amount' => 0,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        $subjects = [
            'Rider ဆီဖိုး', 'Office ကြေး', 'ရေသန့်', 'Internet', 'Printer မှင်',
            'ကားပြင်', 'စားစရိတ်', 'Phone Bill', 'ဘောက်ချာ', 'အထုပ်အပိုး',
        ];

        $existing = $card->items()->count();
        for ($n = $existing + 1; $n <= self::NEED; $n++) {
            ExpenseItem::query()->create([
                'expense_card_id' => $card->id,
                'subject' => $subjects[($n - 1) % count($subjects)].' Demo #'.$n,
                'amount' => 1500 + ($n * 250),
                'sort_order' => $n,
            ]);
        }
        $card->recalculateTotal();

        $this->command?->info('Expense items today: '.$card->items()->count());
    }

    protected function padShopProducts(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        $count = ShopProduct::query()->count();
        if ($count >= self::NEED) {
            $this->command?->info('Shop products: '.$count);

            return;
        }

        $template = ShopProduct::query()->orderBy('id')->first();
        $categoryId = (int) ($template?->category_id ?: ShopCategory::query()->value('id'));
        if ($categoryId <= 0) {
            return;
        }

        $n = 1;
        while (ShopProduct::query()->count() < self::NEED) {
            ShopProduct::query()->create([
                'category_id' => $categoryId,
                'name' => 'Demo Product '.$n,
                'description' => 'Admin demo product '.$n,
                'sku' => 'DEMO-PAD-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                'price' => 10000 + ($n * 500),
                'sale_price' => null,
                'stock_status' => 'in_stock',
                'home_section' => 'new_arrival',
                'sort_order' => 100 + $n,
                'status' => 1,
            ]);
            $n++;
        }

        $this->command?->info('Shop products: '.ShopProduct::query()->count());
    }

    protected function padHrStaff(int $branchId, string $today): void
    {
        if (! Schema::hasTable('hr_staff')) {
            return;
        }

        $period = Carbon::parse($today, 'Asia/Yangon')->startOfMonth()->toDateString();
        $i = 1;
        while (HrStaff::query()->count() < self::NEED) {
            $code = 'DEMO-HR-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $staff = HrStaff::withTrashed()->where('code', $code)->first();
            if ($staff) {
                if ($staff->trashed()) {
                    $staff->restore();
                }
                $staff->fill([
                    'name' => 'Demo HR '.$i,
                    'staff_group' => 'office',
                    'branch_id' => $branchId,
                    'monthly_salary' => 300000 + ($i * 5000),
                    'status' => 1,
                    'sort_order' => $i,
                ])->save();
            } else {
                $staff = HrStaff::query()->create([
                    'code' => $code,
                    'name' => 'Demo HR '.$i,
                    'staff_group' => 'office',
                    'branch_id' => $branchId,
                    'monthly_salary' => 300000 + ($i * 5000),
                    'allowance_minutes' => 15,
                    'way_rate' => 0,
                    'sort_order' => $i,
                    'status' => 1,
                ]);
            }
            $i++;
        }

        if (Schema::hasTable('hr_office_salary_rows')) {
            $staffRows = HrStaff::query()->orderBy('id')->limit(self::NEED)->get();
            foreach ($staffRows as $staff) {
                HrOfficeSalaryRow::query()->updateOrCreate(
                    [
                        'period_month' => $period,
                        'staff_id' => $staff->id,
                    ],
                    [
                        'monthly_salary' => (float) $staff->monthly_salary,
                        'salary_day_base' => 27,
                        'rest_days' => 4,
                        'way_count' => 0,
                        'way_rate' => 0,
                    ]
                );
            }
        }

        $this->command?->info('HR staff: '.HrStaff::query()->count());
    }

    protected function padRiderRemits(int $branchId, int $adminId, string $today): void
    {
        if (! Schema::hasTable('rider_remits')) {
            return;
        }

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
            })
            ->orderBy('id')
            ->limit(self::NEED)
            ->get();

        foreach ($riders as $i => $rider) {
            RiderRemit::query()->updateOrCreate(
                [
                    'remit_date' => $today,
                    'branch_id' => $branchId,
                    'delivery_man_id' => (int) $rider->id,
                ],
                [
                    'due_amount' => 25000 + ($i * 500),
                    'prepaid_amount' => 0,
                    'fuel_amount' => 3000,
                    'fee_amount' => 0,
                    'denominations' => ['10000' => 2, '5000' => 1],
                    'kpay_amount' => 0,
                    'kyo_shin_incharge_amount' => 0,
                    'is_off' => false,
                    'updated_by' => $adminId,
                ]
            );
        }

        $this->command?->info('Rider remits today: '.RiderRemit::query()->whereDate('remit_date', $today)->count());
    }

    protected function padCashPayouts(int $branchId, int $adminId, string $listDay): void
    {
        if (! Schema::hasTable('os_cash_payouts')) {
            return;
        }

        if (OsCashPayout::query()->count() >= self::NEED) {
            $this->command?->info('Cash payouts: '.OsCashPayout::query()->count());

            return;
        }

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(self::NEED)
            ->get();
        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(8)
            ->get();

        $statuses = [
            OsCashPayout::STATUS_UNASSIGNED,
            OsCashPayout::STATUS_ASSIGNED,
            OsCashPayout::STATUS_PENDING,
            OsCashPayout::STATUS_DONE,
        ];

        $have = OsCashPayout::query()->count();
        $created = 0;
        foreach ($clients as $i => $client) {
            if ($have >= self::NEED) {
                break;
            }
            $status = $statuses[$i % count($statuses)];
            $rider = $riders->isNotEmpty() ? $riders[$i % $riders->count()] : null;
            $payload = [
                'os_user_id' => (int) $client->id,
                'branch_id' => $branchId,
                'period_from' => $listDay,
                'period_to' => $listDay,
                'amount' => 15000 + ($i * 250),
                'status' => $status,
                'created_by' => $adminId,
            ];
            if ($status !== OsCashPayout::STATUS_UNASSIGNED && $rider) {
                $payload['delivery_man_id'] = (int) $rider->id;
                $payload['assigned_at'] = now();
            }
            if ($status === OsCashPayout::STATUS_PENDING) {
                $payload['pending_at'] = now();
            }
            if ($status === OsCashPayout::STATUS_DONE) {
                $payload['done_at'] = now();
            }
            try {
                OsCashPayout::query()->create($payload);
                $created++;
                $have++;
            } catch (\Throwable $e) {
                $this->command?->warn('Cash payout skip: '.$e->getMessage());
                continue;
            }
        }

        $this->command?->info('Cash payouts padded: '.$created);
    }

    protected function padOsReceive(int $branchId, int $adminId, string $listDay): void
    {
        if (! Schema::hasTable('os_receive_settlements')) {
            return;
        }

        if (OsReceiveSettlement::query()->count() >= self::NEED) {
            $this->command?->info('OS receive: '.OsReceiveSettlement::query()->count());

            return;
        }

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(self::NEED)
            ->get();

        $created = 0;
        $have = OsReceiveSettlement::query()->count();
        foreach ($clients as $i => $client) {
            if ($have >= self::NEED) {
                break;
            }
            $batch = OsSettlementBatch::query()->create([
                'os_user_id' => (int) $client->id,
                'from_date' => $listDay,
                'to_date' => $listDay,
                'amount' => 8000 + ($i * 150),
                'payment_method' => $i % 2 === 0 ? 'kpay' : 'cash',
                'settlement_side' => 'receive',
                'delivery_format' => 'table',
                'kpay_name' => (string) $client->name,
                'kpay_no' => (string) ($client->contact_number ?? ''),
                'item_ids' => [],
                'finished_by' => $adminId,
                'finished_at' => now(),
            ]);

            try {
                OsReceiveSettlement::query()->create([
                    'settlement_batch_id' => (int) $batch->id,
                    'os_user_id' => (int) $client->id,
                    'from_date' => $listDay,
                    'to_date' => $listDay,
                    'amount' => (float) $batch->amount,
                    'status' => $i % 3 === 0
                        ? OsReceiveSettlement::STATUS_RECEIVED
                        : OsReceiveSettlement::STATUS_PENDING,
                    'finished_by' => $adminId,
                ]);
                $created++;
                $have++;
            } catch (\Throwable $e) {
                $this->command?->warn('OS receive skip: '.$e->getMessage());
                continue;
            }
        }

        $this->command?->info('OS receive padded: '.$created);
    }
}
