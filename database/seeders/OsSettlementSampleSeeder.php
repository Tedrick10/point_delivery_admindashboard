<?php

namespace Database\Seeders;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\OsSettlementService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Inserts real completed dispatch items for OS settlement preview (today).
 * Use when Pay / Receive tabs are empty — not fake UI rows.
 */
class OsSettlementSampleSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();
        // List day D shows items Completed on Yangon day D+1 (C−1 lag).
        $listDay = $today;
        $completedAt = Carbon::parse($listDay, 'Asia/Yangon')->addDay()->setTime(10, 30, 0);
        $completedDay = $completedAt->toDateString();

        $src = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->first();

        if (! $src || ! $src->order) {
            $this->command?->warn('No dispatch item template found — create at least one dispatch order first.');

            return;
        }

        $clientIds = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(5)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (count($clientIds) < 5) {
            $this->command?->warn('Need at least 5 active OS clients. Found '.count($clientIds).'.');

            return;
        }

        $clients = User::query()->whereIn('id', $clientIds)->get()->keyBy('id');
        $payAmounts = [8000, 12000, 15000, 9000, 11000];
        $recvAmounts = [3000, 4500, 5000, 3500, 6000];

        foreach ($clientIds as $i => $clientId) {
            $order = Order::query()
                ->where('client_id', $clientId)
                ->orderByDesc('id')
                ->first();

            if (! $order) {
                $order = $src->order->replicate();
                $order->client_id = $clientId;
                $order->save();
            }

            $name = (string) ($clients->get($clientId)?->name ?? ('OS '.$clientId));

            DispatchOrderItem::query()
                ->whereHas('order', fn ($q) => $q->where('client_id', $clientId))
                ->where('status', 'completed')
                ->whereNull('admin_finished_at')
                ->where(function ($q) {
                    $q->where('item_name', 'like', 'Pay sample %')
                        ->orWhere('item_name', 'like', 'Receive sample %')
                        ->orWhere('item_name', 'like', 'Receive demo %');
                })
                ->delete();

            $payCalc = DispatchOrderItem::computeAmounts($payAmounts[$i], 3000, 0, 0, 'customer');
            $pay = $src->replicate([
                'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
                'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            ]);
            $pay->order_id = $order->id;
            $pay->code = DispatchOrderItem::generateCode();
            $pay->item_name = 'Pay sample '.($i + 1);
            $pay->item_value = $payAmounts[$i];
            $pay->deli_amount = 3000;
            $pay->os_paid = 0;
            $pay->advance_paid = 0;
            $pay->credit_to = 'customer';
            $pay->pickup_pay_mode = 'customer_pay';
            $pay->cust_get = $payCalc['cust_get'];
            $pay->os_to_pay = $payCalc['os_to_pay'];
            $pay->gate_amount = 0;
            $pay->gate_os_paid = 0;
            $pay->status = 'completed';
            $pay->admin_completed_at = $completedAt->copy();
            $pay->admin_finished_at = null;
            $pay->received_date = $completedDay;
            $pay->assigned_at = $completedAt->copy()->subHours(2);
            $pay->customer_name = $name;
            $pay->save();

            $recvCalc = DispatchOrderItem::computeAmounts(0, $recvAmounts[$i], 0, 0, 'os');
            $recv = $src->replicate([
                'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
                'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            ]);
            $recv->order_id = $order->id;
            $recv->code = DispatchOrderItem::generateCode();
            $recv->item_name = 'Receive sample '.($i + 1);
            $recv->item_value = 0;
            $recv->deli_amount = $recvAmounts[$i];
            $recv->os_paid = 0;
            $recv->advance_paid = 0;
            $recv->credit_to = 'os';
            $recv->pickup_pay_mode = 'os_pay';
            $recv->cust_get = $recvCalc['cust_get'];
            $recv->os_to_pay = $recvCalc['os_to_pay'];
            $recv->gate_amount = 0;
            $recv->gate_os_paid = 0;
            $recv->status = 'completed';
            $recv->admin_completed_at = $completedAt->copy()->addMinutes($i + 1);
            $recv->admin_finished_at = null;
            $recv->received_date = $completedDay;
            $recv->assigned_at = $completedAt->copy()->subHour();
            $recv->customer_name = $name;
            $recv->save();

            $this->command?->info(sprintf(
                '%s — pay %s | receive +%s',
                $name,
                number_format($pay->displayOsToPay()),
                number_format($recv->displayOsToPay())
            ));
        }

        $svc = app(OsSettlementService::class);
        $items = $svc->completedItemsQuery(null, $listDay, $listDay)->get();
        $payOs = $items->filter(static fn ($item) => (float) $item->displayOsToPay() < 0)
            ->groupBy(static fn ($item) => (int) ($item->order?->client_id ?? 0))
            ->count();
        $recvOs = $items->filter(static fn ($item) => (float) $item->displayOsToPay() > 0)
            ->groupBy(static fn ($item) => (int) ($item->order?->client_id ?? 0))
            ->count();

        $this->command?->info("Done for list day {$listDay} (Completed {$completedDay}): Pay tab {$payOs} OS · Receive tab {$recvOs} OS");
    }
}
