<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\KyoShinBatch;
use App\Models\KyoShinItem;
use App\Models\Order;
use App\Models\User;
use App\Services\OsSettlementService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo unfinished ကြိုရှင်း parcels for ငွေရှင်းတမ်း → ကြိုရှင်း တာဝန်ခံ ကိုပေးရန်.
 */
class KyoShinPaySampleSeeder extends Seeder
{
    public const TARGET_OS_COUNT = 30;

    public function run(): void
    {
        $listDay = Carbon::parse(yangonSettlementDefaultDate('Y-m-d'), 'Asia/Yangon')->toDateString();
        $completedAt = Carbon::parse($listDay, 'Asia/Yangon')->addDay()->setTime(11, 15, 0);
        $dueDay = Carbon::parse($listDay, 'Asia/Yangon')->addDays(7)->toDateString();

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
        $scopeKey = 'branch_'.$branchId;

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

        if ($riders->isEmpty()) {
            $this->command?->warn('Need at least 1 active rider.');

            return;
        }

        $clients = $this->ensureDemoClients(self::TARGET_OS_COUNT, $branchId);
        $this->cleanupPreviousSamples();

        $amounts = [15000, 22000, 18500, 9000, 12500, 30000, 7500, 16800, 21000, 11200];
        $adminId = (int) (User::query()
            ->whereIn('user_type', ['admin', 'demo_admin'])
            ->orderBy('id')
            ->value('id') ?: 1);

        $seeded = 0;
        foreach ($clients as $i => $client) {
            $order = $this->orderForClient($src, (int) $client->id);
            $amount = (float) $amounts[$i % count($amounts)];
            $calc = DispatchOrderItem::computeAmounts($amount, 0, 0, 0, 'customer');
            $rider = $riders[$i % $riders->count()];

            $item = $src->replicate([
                'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
                'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            ]);
            $item->code = DispatchOrderItem::generateCode();
            $item->order_id = $order->id;
            $item->from_branch_id = $branchId;
            $item->to_branch_id = $branchId;
            $item->delivery_man_id = (int) $rider->id;
            $item->item_name = 'Kyo Shin pay sample '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $item->remark = 'Kyo Shin pay sample';
            $item->item_value = $amount;
            $item->deli_amount = 0;
            $item->os_paid = 0;
            $item->advance_paid = 0;
            $item->gate_amount = 0;
            $item->gate_os_paid = 0;
            $item->credit_to = 'customer';
            $item->pickup_pay_mode = 'customer_pay';
            $item->cust_get = $calc['cust_get'];
            $item->os_to_pay = $calc['os_to_pay'];
            $item->customer_name = (string) $client->name;
            $item->status = 'completed';
            $item->admin_completed_at = $completedAt;
            $item->admin_finished_at = null;
            $item->admin_updated_at = $completedAt;
            $item->received_date = $listDay;
            $item->assigned_at = $listDay.' 09:00:00';
            $item->save();

            $method = $i % 2 === 0 ? 'kpay' : 'cash';
            $batch = KyoShinBatch::query()->create([
                'os_user_id' => (int) $client->id,
                'order_id' => (int) $order->id,
                'payment_method' => $method,
                'kpay_name' => (string) $client->name,
                'kpay_no' => (string) ($client->contact_number ?? ''),
                'due_finished_at' => $dueDay,
                'amount' => $amount,
                'item_ids' => [(int) $item->id],
                'created_by' => $adminId,
            ]);

            KyoShinItem::query()->create([
                'batch_id' => (int) $batch->id,
                'dispatch_order_item_id' => (int) $item->id,
                'scope_key' => $scopeKey,
                'branch_id' => $branchId,
                'os_user_id' => (int) $client->id,
                'amount' => $amount,
                'payment_method' => $method,
                'status' => KyoShinItem::STATUS_ADVANCED_PAID,
                'advanced_paid_at' => $completedAt->copy()->subDays(3),
                'advanced_paid_by' => $adminId,
                'due_finished_at' => $dueDay,
                'finished_at' => null,
                'finished_by' => null,
            ]);

            if (! (bool) $client->is_kyo_shin) {
                $client->forceFill(['is_kyo_shin' => 1])->save();
            }

            $seeded++;
        }

        $rows = app(OsSettlementService::class)
            ->finishedKyoShinItemsForPeriod($listDay, $listDay, $branchId)
            ->groupBy(fn ($item) => (int) ($item->order?->client_id ?? 0));

        $this->command?->info(sprintf(
            'Done %s (%s): seeded %d OS kyo-shin pay parcels, list shows %d OS rows',
            $listDay,
            $branch->name,
            $seeded,
            $rows->count()
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
                    'is_kyo_shin' => 1,
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
                'is_kyo_shin' => 1,
                'contact_number' => '09'.str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT),
                'created_by_admin' => 1,
            ]);
            try {
                $user->assignRole('client');
            } catch (\Throwable $e) {
                // ignore
            }
            $clients->push($user);
            $i++;
        }

        return $clients->unique('id')->take($need)->values();
    }

    protected function cleanupPreviousSamples(): void
    {
        $itemIds = DispatchOrderItem::query()
            ->where(function ($q) {
                $q->where('item_name', 'like', 'Kyo Shin pay sample %')
                    ->orWhere('remark', 'Kyo Shin pay sample');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($itemIds !== []) {
            KyoShinItem::query()->whereIn('dispatch_order_item_id', $itemIds)->delete();
            KyoShinBatch::query()
                ->where(function ($q) use ($itemIds) {
                    foreach ($itemIds as $id) {
                        $q->orWhereJsonContains('item_ids', $id);
                    }
                })
                ->delete();
            DispatchOrderItem::query()->whereIn('id', $itemIds)->delete();
        }
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
}
