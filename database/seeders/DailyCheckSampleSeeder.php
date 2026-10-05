<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\OsMoneyTransfer;
use App\Models\OsSettlementBatch;
use App\Models\User;
use App\Services\DailyCheckListService;
use App\Services\MoneyTransferService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo finished dispatch items for Daily Check List + Money Transfer
 * on the Yangon settlement default date (today − 1), မန္တလေး branch.
 */
class DailyCheckSampleSeeder extends Seeder
{
    public const TARGET_OS_COUNT = 30;

    public function run(): void
    {
        $listDay = Carbon::parse(yangonSettlementDefaultDate('Y-m-d'), 'Asia/Yangon')->toDateString();
        // First Completed on Yangon day C lands on list day C−1.
        $completedAt = Carbon::parse($listDay, 'Asia/Yangon')->addDay()->setTime(10, 30, 0);

        $branch = Branch::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('name', 'မန္တလေး')
                    ->orWhere('name', 'like', '%MDY Branch%')
                    ->orWhere('name', 'MDY Branch');
            })
            ->orderBy('id')
            ->first()
            ?? Branch::query()->where('status', 1)->orderBy('id')->first();

        if (! $branch) {
            $this->command?->warn('No active branch found.');

            return;
        }

        $branchId = (int) $branch->id;

        $src = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->first();

        if (! $src || ! $src->order) {
            $this->command?->warn('No dispatch item template found.');

            return;
        }

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('name')
            ->limit(8)
            ->get();

        if ($riders->count() < 1) {
            $this->command?->warn('Need at least 1 active rider.');

            return;
        }

        $clients = $this->ensureDemoClients(self::TARGET_OS_COUNT, $branchId);
        if ($clients->count() < self::TARGET_OS_COUNT) {
            $this->command?->warn('Could not ensure '.self::TARGET_OS_COUNT.' OS clients.');

            return;
        }

        $slipPath = $this->ensureSampleSlipPath();

        $this->cleanupPreviousSamples($listDay);

        $itemValues = [12000, 8500, 15000, 6000, 9500, 11000, 14000, 7500, 16000, 5000];
        $deliValues = [3500, 2500, 4000, 2000, 3000, 2800, 3200, 2200, 3800, 1800];

        $osRowCount = 0;
        foreach ($clients as $i => $client) {
            $order = $this->orderForClient($src, (int) $client->id);
            $rider = $riders[$i % $riders->count()];
            $payAmount = $itemValues[$i % count($itemValues)];
            $deliAmount = $deliValues[$i % count($deliValues)];
            $payCalc = DispatchOrderItem::computeAmounts($payAmount, $deliAmount, 0, 0, 'customer');

            $pay = $this->makeItem($src, $listDay, $completedAt, $branchId);
            $pay->order_id = $order->id;
            $pay->delivery_man_id = (int) $rider->id;
            $pay->item_name = 'Daily Check sample OS-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $pay->remark = 'Daily Check sample';
            $pay->item_value = $payAmount;
            $pay->deli_amount = $deliAmount;
            $pay->credit_to = 'customer';
            $pay->pickup_pay_mode = 'customer_pay';
            $pay->cust_get = $payCalc['cust_get'];
            $pay->os_to_pay = $payCalc['os_to_pay'];
            $pay->customer_name = (string) $client->name;
            $pay->save();

            $amount = (float) $pay->displayOsToPay();
            $method = $i % 2 === 0 ? 'kpay' : 'cash';

            $batch = OsSettlementBatch::query()->create([
                'os_user_id' => (int) $client->id,
                'from_date' => $listDay,
                'to_date' => $listDay,
                'amount' => $amount,
                'payment_method' => $method,
                'settlement_side' => null,
                'delivery_format' => 'table',
                'kpay_name' => (string) $client->name,
                'kpay_no' => (string) ($client->contact_number ?? '09'.str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT)),
                'kpay_slip_path' => $slipPath,
                'item_ids' => [(int) $pay->id],
                'finished_by' => 1,
                'finished_at' => $completedAt,
            ]);

            OsMoneyTransfer::query()->updateOrCreate(
                [
                    'os_user_id' => (int) $client->id,
                    'period_from' => $listDay,
                    'period_to' => $listDay,
                    'payment_method' => $method,
                ],
                [
                    'branch_id' => $branchId,
                    'settlement_batch_id' => (int) $batch->id,
                    'cash_amount' => $method === 'cash' ? $amount : 0,
                    'kpay_amount' => $method === 'kpay' ? $amount : 0,
                    'freight_amount' => 0,
                    'remark' => 'Daily Check sample',
                    'updated_by' => 1,
                ]
            );

            $osRowCount++;
        }

        // Extra rider-side Daily Check rows (mode=rider / all).
        foreach ($riders->take(5) as $i => $rider) {
            $calc = DispatchOrderItem::computeAmounts($itemValues[$i % 10], $deliValues[$i % 10], 0, 0, 'customer');
            for ($n = 1; $n <= 2; $n++) {
                $item = $this->makeItem($src, $listDay, $completedAt, $branchId);
                $item->order_id = $this->orderForClient($src, (int) $clients[$i % $clients->count()]->id)->id;
                $item->delivery_man_id = (int) $rider->id;
                $item->item_name = 'Daily Check sample R'.($i + 1).'-'.$n;
                $item->remark = 'Daily Check sample';
                $item->item_value = $itemValues[$i % 10];
                $item->deli_amount = $deliValues[$i % 10];
                $item->credit_to = 'customer';
                $item->pickup_pay_mode = 'customer_pay';
                $item->cust_get = $calc['cust_get'];
                $item->os_to_pay = $calc['os_to_pay'];
                $item->customer_name = (string) $rider->name;
                // Rider list does not require Finish; leave admin_finished_at for OS-only items.
                $item->admin_finished_at = null;
                $item->save();
            }
        }

        $svc = app(DailyCheckListService::class);
        $mt = app(MoneyTransferService::class);
        $osRows = $svc->listRows($listDay, $listDay, 'os', $branchId, null, null);
        $riderRows = $svc->listRows($listDay, $listDay, 'rider', $branchId, null, null);
        $mtSheet = $mt->listSheet($listDay, $listDay, $branchId, null, 'all');

        $this->command?->info(sprintf(
            'Done %s (%s): %d OS seeded, Daily Check OS=%d / Rider=%d, Money Transfer=%d',
            $listDay,
            $branch->name,
            $osRowCount,
            $osRows->count(),
            $riderRows->count(),
            $mtSheet['rows']->count()
        ));
    }

    protected function ensureDemoClients(int $need, int $branchId)
    {
        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        $i = 1;
        while ($clients->unique('id')->count() < $need) {
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
                if (! $clients->contains(fn (User $u) => (int) $u->id === (int) $existing->id)) {
                    $clients->push($existing);
                }
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
            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('client');
                } catch (\Throwable $e) {
                    // Role may already exist / package optional.
                }
            }
            $clients->push($user);
            $i++;
        }

        return $clients->unique('id')->take($need)->values();
    }

    protected function cleanupPreviousSamples(string $listDay): void
    {
        $sampleItemIds = DispatchOrderItem::query()
            ->where(function ($q) {
                $q->where('item_name', 'like', 'Daily Check sample %')
                    ->orWhere('item_name', 'like', 'Daily Check gate sample %')
                    ->orWhere('remark', 'Daily Check sample');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($sampleItemIds !== []) {
            OsSettlementBatch::query()
                ->where(function ($q) use ($sampleItemIds) {
                    foreach ($sampleItemIds as $id) {
                        $q->orWhereJsonContains('item_ids', $id);
                    }
                })
                ->delete();
        }

        OsMoneyTransfer::query()
            ->where('remark', 'Daily Check sample')
            ->orWhere(function ($q) use ($listDay) {
                $q->whereDate('period_from', $listDay)
                    ->whereDate('period_to', $listDay)
                    ->whereHas('osUser', fn ($u) => $u->where('email', 'like', 'demo.os.%@point.demo'));
            })
            ->delete();

        DispatchOrderItem::query()
            ->where(function ($q) {
                $q->where('item_name', 'like', 'Daily Check sample %')
                    ->orWhere('item_name', 'like', 'Daily Check gate sample %')
                    ->orWhere('remark', 'Daily Check sample');
            })
            ->delete();
    }

    protected function makeItem(
        DispatchOrderItem $src,
        string $listDay,
        Carbon $completedAt,
        int $branchId
    ): DispatchOrderItem {
        $item = $src->replicate([
            'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
            'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
        ]);
        $item->code = DispatchOrderItem::generateCode();
        $item->from_branch_id = $branchId;
        $item->to_branch_id = $branchId;
        $item->os_paid = 0;
        $item->advance_paid = 0;
        $item->gate_amount = 0;
        $item->gate_os_paid = 0;
        $item->status = 'completed';
        $item->admin_completed_at = $completedAt;
        $item->admin_finished_at = $completedAt;
        $item->admin_updated_at = $completedAt;
        $item->received_date = $listDay;
        $item->assigned_at = $listDay.' 09:00:00';

        return $item;
    }

    protected function orderForClient(DispatchOrderItem $src, int $clientId): Order
    {
        $order = Order::query()
            ->where('client_id', $clientId)
            ->orderByDesc('id')
            ->first();

        if ($order) {
            return $order;
        }

        $order = $src->order->replicate();
        $order->client_id = $clientId;
        $order->save();

        return $order;
    }

    protected function ensureSampleSlipPath(): string
    {
        $path = 'daily-check/sample-kpay-slip.svg';
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="420" viewBox="0 0 320 420"><rect width="320" height="420" fill="#ecfdf5"/><text x="160" y="210" text-anchor="middle" font-family="sans-serif" font-size="18" fill="#047857">Sample KBZ Slip</text></svg>
SVG);
        }

        return $path;
    }
}
