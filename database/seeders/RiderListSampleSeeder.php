<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\DispatchHubService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Demo dispatch items so Rider List shows counts for:
 * - MDY panel → ရန်ကုန် hubs
 * - Yangon hubs → မန္တလေး (MDY) return riders
 * - Local MDY riders (optional sample)
 */
class RiderListSampleSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();

        $src = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->first();

        if (! $src || ! $src->order) {
            $this->command?->warn('No dispatch item template found.');

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

        $mdyBranchId = (int) (Branch::query()->where('status', 1)->where('name', 'မန္တလေး')->value('id') ?? 0);
        $ygnBranchId = (int) (Branch::query()->where('status', 1)->where('name', 'ရန်ကုန်')->value('id') ?? 0);
        if ($mdyBranchId <= 0 || $ygnBranchId <= 0) {
            $this->command?->warn('မန္တလေး / ရန်ကုန် branches missing.');

            return;
        }

        $order = $this->orderForClient($src, (int) $client->id);

        DispatchOrderItem::query()
            ->where(function ($q) {
                $q->where('item_name', 'like', 'Rider List sample %')
                    ->orWhere('remark', 'Rider List cross-hub sample');
            })
            ->delete();

        $this->seedYangonHubSamples($src, $order, $today, $mdyBranchId, $ygnBranchId);
        $this->seedMdyReturnSamples($src, $order, $today, $mdyBranchId, $ygnBranchId);
        $this->seedLocalMdySamples($src, $order, $today, $mdyBranchId);
        $this->seedLashioSamples($src, $today, $mdyBranchId);

        $this->command?->info(sprintf('Rider List demo seeded for %s (Yangon hubs + MDY return + local MDY + Lashio).', $today));
    }

    protected function seedYangonHubSamples(
        DispatchOrderItem $src,
        Order $order,
        string $today,
        int $mdyBranchId,
        int $ygnBranchId
    ): void {
        $hubs = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->where('is_dispatch_hub', 1)
            ->orderBy('id')
            ->get();

        if ($hubs->isEmpty()) {
            $this->command?->warn('No Yangon hub accounts found.');

            return;
        }

        foreach ($hubs as $i => $hub) {
            // MDY panel Yangon tab: Assign 100 pool counted on the hub.
            for ($n = 1; $n <= 2; $n++) {
                $this->createSampleItem(
                    $src,
                    $order,
                    $hub,
                    $today,
                    $ygnBranchId,
                    $mdyBranchId,
                    10000 + ($i * 500) + ($n * 100),
                    3000,
                    'Rider List sample '.$hub->name.' hub-assigned-'.$n,
                    'assigned',
                    null,
                    null,
                    hubUserId: (int) $hub->id,
                    clearDeliveryMan: true
                );
            }

            // Last-mile activity on the hub account itself.
            $this->createSampleItem(
                $src,
                $order,
                $hub,
                $today,
                $ygnBranchId,
                $ygnBranchId,
                12000,
                3500,
                'Rider List sample '.$hub->name.' assigned-1',
                'courier_assigned'
            );
            $this->createSampleItem(
                $src,
                $order,
                $hub,
                $today,
                $ygnBranchId,
                $ygnBranchId,
                8500,
                2500,
                'Rider List sample '.$hub->name.' onway-1',
                'courier_departed'
            );
            $this->createSampleItem(
                $src,
                $order,
                $hub,
                $today,
                $ygnBranchId,
                $ygnBranchId,
                15000,
                4000,
                'Rider List sample '.$hub->name.' pending-1',
                'pending'
            );

            $this->command?->info('Hub '.$hub->name.' — Assign100 + Assigned/OnWay/Pending seeded');
        }
    }

    protected function seedMdyReturnSamples(
        DispatchOrderItem $src,
        Order $order,
        string $today,
        int $mdyBranchId,
        int $ygnBranchId
    ): void {
        $hubService = app(DispatchHubService::class);
        $hubs = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->where('is_dispatch_hub', 1)
            ->orderBy('id')
            ->get();

        foreach ($hubs as $hub) {
            $rider = $hubService->ensureMdyReturnDeliveryMan($hub);
            if (! $rider) {
                continue;
            }

            $profiles = [
                ['status' => 'courier_assigned', 'label' => 'assigned', 'count' => 2],
                ['status' => 'courier_departed', 'label' => 'onway', 'count' => 2],
                ['status' => 'pending', 'label' => 'pending', 'count' => 1],
            ];

            foreach ($profiles as $profile) {
                for ($n = 1; $n <= (int) $profile['count']; $n++) {
                    $this->createSampleItem(
                        $src,
                        $order,
                        $rider,
                        $today,
                        $ygnBranchId,
                        $mdyBranchId,
                        11000 + ($n * 250),
                        3200,
                        'Rider List sample '.$rider->name.' hub'.$hub->id.' '.$profile['label'].'-'.$n,
                        $profile['status'],
                        hubUserId: (int) $hub->id
                    );
                }
            }

            $this->command?->info(sprintf(
                'MDY return under %s — A:2 W:2 P:1 seeded (rider #%d)',
                $hub->name,
                (int) $rider->id
            ));
        }
    }

    protected function seedLocalMdySamples(
        DispatchOrderItem $src,
        Order $order,
        string $today,
        int $mdyBranchId
    ): void {
        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->where('branch_id', $mdyBranchId)
            ->where(function ($q) {
                $q->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
            })
            ->where(function ($q) {
                if (Schema::hasColumn('users', 'is_mdy_return')) {
                    $q->whereNull('is_mdy_return')->orWhere('is_mdy_return', 0);
                }
            })
            ->where(function ($q) {
                if (Schema::hasColumn('users', 'hub_parent_id')) {
                    $q->whereNull('hub_parent_id')->orWhere('hub_parent_id', 0);
                }
            })
            ->orderBy('name')
            ->limit(3)
            ->get();

        foreach ($riders as $i => $rider) {
            $this->createSampleItem(
                $src,
                $order,
                $rider,
                $today,
                $mdyBranchId,
                $mdyBranchId,
                9000 + ($i * 500),
                3000,
                'Rider List sample '.$rider->name.' assigned-1',
                'courier_assigned'
            );
            $this->createSampleItem(
                $src,
                $order,
                $rider,
                $today,
                $mdyBranchId,
                $mdyBranchId,
                10000 + ($i * 500),
                3500,
                'Rider List sample '.$rider->name.' onway-1',
                'courier_departed'
            );
        }

        if ($riders->isNotEmpty()) {
            $this->command?->info('Local MDY riders: '.$riders->count().' seeded');
        }
    }

    protected function seedLashioSamples(DispatchOrderItem $src, string $today, int $mdyBranchId): void
    {
        $lsoBranchId = (int) (Branch::query()->where('status', 1)->where('name', 'လားရှိုး')->value('id') ?? 0);
        if ($lsoBranchId <= 0) {
            $this->command?->warn('လားရှိုး branch missing.');

            return;
        }

        $client = User::query()
            ->where('email', 'os.lso.nanghom@demo.local')
            ->where('user_type', 'client')
            ->where('status', 1)
            ->first()
            ?: User::query()
                ->where('user_type', 'client')
                ->where('branch_id', $lsoBranchId)
                ->where('status', 1)
                ->orderBy('id')
                ->first();

        if (! $client) {
            $this->command?->warn('No Lashio OS client found.');

            return;
        }

        $order = $this->orderForClient($src, (int) $client->id);
        $order->city_id = (int) ($client->city_id ?: $order->city_id);
        $order->save();

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->where('branch_id', $lsoBranchId)
            ->where(function ($q) {
                $q->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
            })
            ->where(function ($q) {
                if (Schema::hasColumn('users', 'hub_parent_id')) {
                    $q->whereNull('hub_parent_id')->orWhere('hub_parent_id', 0);
                }
            })
            ->where('email', 'like', 'rider.lso%@demo.local')
            ->where('email', 'not like', 'rider.lso.pickup%')
            ->orderBy('name')
            ->get();

        if ($riders->isEmpty()) {
            $this->command?->warn('No Lashio last-mile riders found.');

            return;
        }

        $completedAt = Carbon::parse($today.' 10:30:00', 'Asia/Yangon');

        foreach ($riders as $i => $rider) {
            $this->createSampleItem(
                $src,
                $order,
                $rider,
                $today,
                $mdyBranchId,
                $lsoBranchId,
                18000 + ($i * 500),
                4500,
                'Rider List sample '.$rider->name.' assigned-1',
                'courier_assigned',
                deliveryCity: 'Lashio',
                township: 'လားရှိုး'
            );
            $this->createSampleItem(
                $src,
                $order,
                $rider,
                $today,
                $mdyBranchId,
                $lsoBranchId,
                16000 + ($i * 500),
                4500,
                'Rider List sample '.$rider->name.' onway-1',
                'courier_departed',
                deliveryCity: 'Lashio',
                township: 'လားရှိုး'
            );
            $this->createSampleItem(
                $src,
                $order,
                $rider,
                $today,
                $mdyBranchId,
                $lsoBranchId,
                14000 + ($i * 400),
                4500,
                'Rider List sample '.$rider->name.' pending-1',
                'pending',
                deliveryCity: 'Lashio',
                township: 'လားရှိုး'
            );
            $this->createSampleItem(
                $src,
                $order,
                $rider,
                $today,
                $mdyBranchId,
                $lsoBranchId,
                22000 + ($i * 600),
                4500,
                'Rider List sample '.$rider->name.' completed-1',
                'completed',
                adminCompletedAt: $completedAt->copy()->subHours(2),
                deliveryCity: 'Lashio',
                township: 'လားရှိုး'
            );
        }

        $this->command?->info('Lashio riders: '.$riders->count().' seeded (MDY → လားရှိုး).');
    }

    protected function createSampleItem(
        DispatchOrderItem $src,
        Order $order,
        User $rider,
        string $today,
        int $fromBranchId,
        int $toBranchId,
        int $itemValue,
        int $deliAmount,
        string $name,
        string $status,
        ?Carbon $adminCompletedAt = null,
        ?Carbon $adminFinishedAt = null,
        int $gateAmount = 0,
        int $gateOsPaid = 0,
        ?string $deliveredType = null,
        ?int $hubUserId = null,
        bool $clearDeliveryMan = false,
        ?string $deliveryCity = null,
        ?string $township = null
    ): void {
        $calc = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, 0, 'customer');

        $item = $src->replicate([
            'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
            'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            'rider_remit_at',
        ]);
        $item->order_id = $order->id;
        $item->code = DispatchOrderItem::generateCode();
        $item->delivery_man_id = $clearDeliveryMan ? null : (int) $rider->id;
        $item->from_branch_id = $fromBranchId;
        $item->to_branch_id = $toBranchId;
        $item->item_name = $name;
        $item->remark = str_contains($name, 'hub-assigned') || str_contains($name, 'hub')
            ? 'Rider List cross-hub sample'
            : 'Rider List sample';
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
        if ($deliveryCity || $township) {
            $item->customer_address = trim(($township ?: $deliveryCity).', Lashio');
        }
        $item->status = $status;
        if ($deliveryCity) {
            $item->delivery_city = $deliveryCity;
        }
        if ($township) {
            $item->township = $township;
        }
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
        $item->delivered_at = null;
        $item->rider_remit_date = null;

        if (Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            $item->hub_user_id = $hubUserId;
        }
        if (Schema::hasColumn('dispatch_order_items', 'hub_inbox_at')) {
            $item->hub_inbox_at = $hubUserId ? ($today.' 08:30:00') : null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'hub_accepted_at')) {
            $item->hub_accepted_at = $hubUserId && ! $clearDeliveryMan ? ($today.' 09:00:00') : null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            // Keep null so MDY panel still counts Assign 100 on Yangon hubs.
            $item->mdy_inbox_at = null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'mdy_accepted_at')) {
            $item->mdy_accepted_at = null;
        }

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
