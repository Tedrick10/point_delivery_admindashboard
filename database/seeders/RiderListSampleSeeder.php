<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Real dispatch items for Rider List status columns (today, MDY Branch).
 */
class RiderListSampleSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();

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

        if ($riders->count() < 5) {
            $this->command?->warn('Need at least 5 active riders.');

            return;
        }

        $client = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->first();

        if (! $client) {
            $this->command?->warn('No active OS client found.');

            return;
        }

        DispatchOrderItem::query()
            ->whereDate('received_date', $today)
            ->where('item_name', 'like', 'Rider List sample %')
            ->delete();

        // Today only — On Way (+ Assigned/Pending). No Delivered / Completed / Finished.
        $profiles = [
            ['assigned' => 1, 'onway' => 2, 'pending' => 1],
            ['assigned' => 1, 'onway' => 3, 'pending' => 0],
            ['assigned' => 2, 'onway' => 2, 'pending' => 1],
            ['assigned' => 1, 'onway' => 3, 'pending' => 1],
            ['assigned' => 1, 'onway' => 4, 'pending' => 0],
        ];

        $itemValues = [10000, 12000, 8500, 15000, 9500];
        $deliValues = [3000, 3500, 2500, 4000, 3200];
        $order = $this->orderForClient($src, (int) $client->id);

        foreach ($riders as $i => $rider) {
            $profile = $profiles[$i] ?? ['assigned' => 1, 'onway' => 2, 'pending' => 0];

            $seq = 1;
            foreach (['assigned' => 'courier_assigned', 'pending' => 'pending'] as $key => $status) {
                for ($n = 0; $n < (int) ($profile[$key] ?? 0); $n++) {
                    $this->createSampleItem(
                        $src,
                        $order,
                        $rider,
                        $today,
                        $branchId,
                        $itemValues[($i + $n) % 5],
                        $deliValues[($i + $n) % 5],
                        'Rider List sample '.$rider->name.' '.$key.'-'.$seq,
                        $status,
                        null,
                        null
                    );
                    $seq++;
                }
            }

            for ($n = 0; $n < (int) ($profile['onway'] ?? 0); $n++) {
                $this->createSampleItem(
                    $src,
                    $order,
                    $rider,
                    $today,
                    $branchId,
                    $itemValues[($i + $n + 1) % 5],
                    $deliValues[($i + $n + 1) % 5],
                    'Rider List sample '.$rider->name.' onway-'.($n + 1),
                    'courier_departed',
                    null,
                    null,
                    0,
                    0,
                    null
                );
            }

            $this->command?->info(sprintf(
                'Rider %s — A:%d W:%d P:%d (On Way sample, no Gate)',
                $rider->name,
                $profile['assigned'] ?? 0,
                $profile['onway'] ?? 0,
                $profile['pending'] ?? 0
            ));
        }

        $this->command?->info(sprintf('Done %s (%s): Rider List On Way sample seeded (no Gate).', $today, $branch->name));
    }

    protected function createSampleItem(
        DispatchOrderItem $src,
        Order $order,
        User $rider,
        string $today,
        int $branchId,
        int $itemValue,
        int $deliAmount,
        string $name,
        string $status,
        ?Carbon $adminCompletedAt,
        ?Carbon $adminFinishedAt,
        int $gateAmount = 0,
        int $gateOsPaid = 0,
        ?string $deliveredType = null
    ): void {
        $calc = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, 0, 'customer');

        $item = $src->replicate([
            'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
            'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            'rider_remit_at',
        ]);
        $item->order_id = $order->id;
        $item->code = DispatchOrderItem::generateCode();
        $item->delivery_man_id = (int) $rider->id;
        $item->from_branch_id = $branchId;
        $item->to_branch_id = $branchId;
        $item->item_name = $name;
        $item->remark = 'Rider List sample';
        $item->item_value = $itemValue;
        $item->deli_amount = $deliAmount;
        $item->os_paid = 0;
        $item->advance_paid = 0;
        $item->gate_amount = $gateAmount;
        $item->gate_os_paid = $gateOsPaid;
        $item->delivered_type = $deliveredType;
        $item->credit_to = 'customer';
        $item->pickup_pay_mode = 'customer_pay';
        $item->cust_get = $calc['cust_get'];
        $item->os_to_pay = $calc['os_to_pay'];
        $item->customer_name = (string) $rider->name;
        $item->status = $status;
        $item->delivery_locked = false;
        $item->delivered_photo_id = 0;
        $item->pending_photo_id = 0;
        $item->photo_id = 0;
        $item->cust_photo_id = 0;
        $item->cust_sign_id = 0;
        $item->admin_completed_at = $adminCompletedAt;
        $item->admin_finished_at = $adminFinishedAt;
        $item->admin_updated_at = $adminCompletedAt ?? $adminFinishedAt;
        $item->received_date = $today;
        $item->assigned_at = $today.' 09:00:00';
        $item->rider_remit_at = null;
        $item->save();
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
