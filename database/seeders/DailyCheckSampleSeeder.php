<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\OsSettlementBatch;
use App\Models\User;
use App\Services\DailyCheckListService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Real finished dispatch items for Daily Check List (today, MDY Branch).
 */
class DailyCheckSampleSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();
        $now = now();

        $branch = Branch::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('name', 'like', '%MDY Branch%')
                    ->orWhere('name', 'MDY Branch');
            })
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
            ->get();

        $zin = $riders->first(fn (User $u) => stripos((string) $u->name, 'Zin Min') !== false);
        $others = $riders->filter(fn (User $u) => ! $zin || (int) $u->id !== (int) $zin->id)->take(4);
        $riders = collect($zin ? [$zin] : [])->merge($others)->take(5)->values();

        if ($riders->count() < 3) {
            $this->command?->warn('Need at least 3 active riders.');

            return;
        }

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($clients->count() < 3) {
            $this->command?->warn('Need at least 3 active OS clients.');

            return;
        }

        $slipPath = $this->ensureSampleSlipPath();

        DispatchOrderItem::query()
            ->whereDate('received_date', $today)
            ->where(function ($q) {
                $q->where('item_name', 'like', 'Daily Check sample %')
                    ->orWhere('item_name', 'like', 'Daily Check gate sample %')
                    ->orWhere('remark', 'Daily Check sample');
            })
            ->delete();

        $itemValues = [12000, 8500, 15000, 6000, 9500];
        $deliValues = [3500, 2500, 4000, 2000, 3000];

        foreach ($riders as $i => $rider) {
            $calc = DispatchOrderItem::computeAmounts($itemValues[$i % 5], $deliValues[$i % 5], 0, 0, 'customer');

            for ($n = 1; $n <= 2; $n++) {
                $item = $this->makeItem($src, $today, $now, $branchId);
                $item->order_id = $this->orderForClient($src, $clients[$i % $clients->count()]->id)->id;
                $item->delivery_man_id = (int) $rider->id;
                $item->item_name = 'Daily Check sample R'.($i + 1).'-'.$n;
                $item->remark = 'Daily Check sample';
                $item->item_value = $itemValues[$i % 5];
                $item->deli_amount = $deliValues[$i % 5];
                $item->credit_to = 'customer';
                $item->pickup_pay_mode = 'customer_pay';
                $item->cust_get = $calc['cust_get'];
                $item->os_to_pay = $calc['os_to_pay'];
                $item->customer_name = (string) $rider->name;
                $item->save();
            }

            $this->command?->info('Rider '.$rider->name.' — 2 finished items');
        }

        $gateRider = $riders->first(
            fn (User $u) => stripos((string) $u->name, 'Zin Min') === false
                && stripos((string) $u->name, 'Kyaw') !== false
        ) ?? $riders->first(fn (User $u) => stripos((string) $u->name, 'Zin Min') === false);

        if ($gateRider) {
            $this->seedGateRiderItems($src, $today, $now, $branchId, $gateRider, $clients->first());
        }

        foreach ($clients as $i => $client) {
            $order = $this->orderForClient($src, (int) $client->id);
            $osItemIds = [];

            $payCalc = DispatchOrderItem::computeAmounts($itemValues[$i % 5], $deliValues[$i % 5], 0, 0, 'customer');
            $pay = $this->makeItem($src, $today, $now, $branchId);
            $pay->order_id = $order->id;
            $pay->delivery_man_id = $this->deliveryManForOsItem($riders, $gateRider, $i);
            $pay->item_name = 'Daily Check sample OS-P'.($i + 1);
            $pay->remark = 'Daily Check sample';
            $pay->item_value = $itemValues[$i % 5];
            $pay->deli_amount = $deliValues[$i % 5];
            $pay->credit_to = 'customer';
            $pay->pickup_pay_mode = 'customer_pay';
            $pay->cust_get = $payCalc['cust_get'];
            $pay->os_to_pay = $payCalc['os_to_pay'];
            $pay->customer_name = (string) $client->name;
            $pay->save();
            $osItemIds[] = (int) $pay->id;

            $recvCalc = DispatchOrderItem::computeAmounts(0, $deliValues[$i % 5], 0, 0, 'os');
            $recv = $this->makeItem($src, $today, $now, $branchId);
            $recv->order_id = $order->id;
            $recv->delivery_man_id = $this->deliveryManForOsItem($riders, $gateRider, $i + 1);
            $recv->item_name = 'Daily Check sample OS-R'.($i + 1);
            $recv->remark = 'Daily Check sample';
            $recv->item_value = 0;
            $recv->deli_amount = $deliValues[$i % 5];
            $recv->credit_to = 'os';
            $recv->pickup_pay_mode = 'os_pay';
            $recv->cust_get = $recvCalc['cust_get'];
            $recv->os_to_pay = $recvCalc['os_to_pay'];
            $recv->customer_name = (string) $client->name;
            $recv->save();
            $osItemIds[] = (int) $recv->id;

            OsSettlementBatch::query()->create([
                'os_user_id' => (int) $client->id,
                'from_date' => $today,
                'to_date' => $today,
                'amount' => (float) $pay->displayOsToPay() + (float) $recv->displayOsToPay(),
                'payment_method' => $i % 2 === 0 ? 'kpay' : 'cash',
                'settlement_side' => null,
                'delivery_format' => 'table',
                'kpay_name' => (string) $client->name,
                'kpay_no' => (string) ($client->contact_number ?? ''),
                'kpay_slip_path' => $slipPath,
                'item_ids' => $osItemIds,
                'finished_by' => 1,
                'finished_at' => $now,
            ]);

            $this->command?->info('OS '.$client->name.' — pay + receive finished');
        }

        $svc = app(DailyCheckListService::class);
        $riderRows = $svc->listRows($today, $today, 'rider', $branchId, null, null);
        $osRows = $svc->listRows($today, $today, 'os', $branchId, null, null);
        $zin = User::query()->where('user_type', 'delivery_man')->where('name', 'like', '%Zin Min%')->value('id');
        $zinRows = $zin
            ? $svc->listRows($today, $today, 'rider', $branchId, null, (int) $zin)->count()
            : 0;
        $gateRiderRows = $gateRider
            ? $svc->listRows($today, $today, 'rider', $branchId, null, (int) $gateRider->id)->count()
            : 0;

        $this->command?->info(sprintf(
            'Done %s (%s): %d rider invoices, %d OS invoices, Zin Min Oo=%d, Gate rider %s=%d',
            $today,
            $branch->name,
            $riderRows->count(),
            $osRows->count(),
            $zinRows,
            $gateRider?->name ?? '-',
            $gateRiderRows
        ));
    }

    /**
     * Gate delivery samples for a rider other than Zin Min Oo (Daily Check Gate column).
     */
    protected function seedGateRiderItems(
        DispatchOrderItem $src,
        string $today,
        Carbon $now,
        int $branchId,
        User $rider,
        ?User $client
    ): void {
        if (! $client) {
            return;
        }

        $order = $this->orderForClient($src, (int) $client->id);
        $scenarios = [
            ['item' => 10000, 'deli' => 3000, 'gate' => 1000, 'gate_os_paid' => 0],
            ['item' => 12000, 'deli' => 3500, 'gate' => 1500, 'gate_os_paid' => 1500],
            ['item' => 8000, 'deli' => 2500, 'gate' => 2000, 'gate_os_paid' => 500],
        ];

        foreach ($scenarios as $i => $s) {
            $calc = DispatchOrderItem::computeAmounts($s['item'], $s['deli'], 0, 0, 'customer');
            $item = $this->makeItem($src, $today, $now, $branchId);
            $item->order_id = $order->id;
            $item->delivery_man_id = (int) $rider->id;
            $item->item_name = 'Daily Check gate sample G'.($i + 1);
            $item->remark = 'Daily Check sample';
            $item->item_value = $s['item'];
            $item->deli_amount = $s['deli'];
            $item->gate_amount = $s['gate'];
            $item->gate_os_paid = $s['gate_os_paid'];
            $item->delivered_type = 'gate';
            $item->credit_to = 'customer';
            $item->pickup_pay_mode = 'customer_pay';
            $item->cust_get = $calc['cust_get'];
            $item->os_to_pay = $calc['os_to_pay'];
            $item->customer_name = (string) $rider->name;
            $item->save();
        }

        $this->command?->info(sprintf(
            'Rider %s — 3 gate items (Gate %s / %s / %s)',
            $rider->name,
            number_format($scenarios[0]['gate']),
            number_format($scenarios[1]['gate']),
            number_format($scenarios[2]['gate'])
        ));
    }

    protected function makeItem(
        DispatchOrderItem $src,
        string $today,
        Carbon $now,
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
        $item->admin_completed_at = $now;
        $item->admin_finished_at = $now;
        $item->admin_updated_at = $now;
        $item->received_date = $today;
        $item->assigned_at = $today.' 09:00:00';

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

    protected function deliveryManForOsItem($riders, ?User $gateRider, int $offset): ?int
    {
        $gateId = $gateRider ? (int) $gateRider->id : 0;
        $pool = $riders->filter(fn (User $u) => (int) $u->id !== $gateId)->values();
        if ($pool->isEmpty()) {
            return null;
        }

        return (int) $pool[$offset % $pool->count()]->id;
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
