<?php

namespace Database\Seeders;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\OsSettlementService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo unfinished Os Pay parcels so Zin Min Oo appears on
 * ငွေရှင်းတမ်း → Os ဆီမှရရန် for today's list day.
 */
class ZinMinOoOsReceiveDemoSeeder extends Seeder
{
    private const MARKER = 'ZMO Point System receive demo';

    public function run(): void
    {
        $listDay = function_exists('yangonSettlementDefaultDate')
            ? yangonSettlementDefaultDate('Y-m-d')
            : Carbon::now('Asia/Yangon')->toDateString();

        // List day uses Yangon 10:00 cutoff — complete after that on the list day.
        $completedAt = Carbon::parse($listDay.' 11:30:00', 'Asia/Yangon');

        $client = User::query()
            ->where('id', 5)
            ->where('user_type', 'client')
            ->first()
            ?? User::query()->where('name', 'Zin Min Oo')->where('user_type', 'client')->first();

        if (! $client) {
            $this->command?->error('Zin Min Oo (client) not found.');

            return;
        }

        $rider = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->when(
                (int) ($client->branch_id ?? 0) > 0,
                fn ($q) => $q->where('branch_id', (int) $client->branch_id)
            )
            ->orderBy('id')
            ->first()
            ?? User::query()->where('user_type', 'delivery_man')->where('status', 1)->orderBy('id')->first();

        if (! $rider) {
            $this->command?->error('No active rider found.');

            return;
        }

        $this->cleanup();

        $branchId = (int) ($client->branch_id ?: $rider->branch_id ?: 1);
        $amounts = [15000, 8500, 12000];
        $created = [];

        foreach ($amounts as $i => $deli) {
            $calc = DispatchOrderItem::computeAmounts(0, (float) $deli, 0, 0, 'os');

            $order = Order::create([
                'client_id' => (int) $client->id,
                'delivery_man_id' => (int) $rider->id,
                'country_id' => (int) ($client->country_id ?: 1),
                'city_id' => (int) ($client->city_id ?: 1),
                'status' => 'completed',
                'date' => $listDay,
                'pickup_datetime' => $completedAt->copy()->subHours(2)->format('Y-m-d H:i:s'),
                'delivery_datetime' => $completedAt->format('Y-m-d H:i:s'),
                'assign_datetime' => $completedAt->copy()->subHours(3)->format('Y-m-d H:i:s'),
                'total_parcel' => 1,
                'total_amount' => $deli,
                'fixed_charges' => $deli,
                'description' => self::MARKER,
                'pickup_point' => [
                    'name' => (string) $client->name,
                    'address' => 'Demo OS receive',
                    'contact_number' => (string) ($client->contact_number ?: '09900000000'),
                ],
                'delivery_point' => [
                    'name' => 'Demo Customer',
                    'address' => 'Demo delivery',
                    'contact_number' => '09111111111',
                ],
            ]);

            $item = DispatchOrderItem::create([
                'order_id' => $order->id,
                'code' => DispatchOrderItem::generateCode(),
                'delivery_man_id' => (int) $rider->id,
                'from_branch_id' => $branchId,
                'to_branch_id' => $branchId,
                'item_name' => self::MARKER.' #'.($i + 1),
                'item_value' => 0,
                'deli_amount' => $deli,
                'credit_to' => 'os',
                'pickup_pay_mode' => 'os_pay',
                'cust_get' => $calc['cust_get'],
                'os_to_pay' => $calc['os_to_pay'],
                'customer_name' => (string) $client->name,
                'remark' => self::MARKER,
                'status' => 'completed',
                'admin_completed_at' => $completedAt->copy()->addMinutes($i * 5),
                'admin_finished_at' => null,
                'received_date' => $listDay,
            ]);

            $created[] = [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'os_to_pay' => (float) $item->os_to_pay,
                'display' => (float) $item->displayOsToPay(),
            ];
        }

        $svc = app(OsSettlementService::class);
        $items = $svc->completedItemsForPeriod((int) $client->id, $listDay, $listDay, $branchId);
        $nettable = $svc->nettableSettlementItems($items);
        $net = $svc->netSettlementOsToPay($nettable);

        $this->command?->info(sprintf(
            'Zin Min Oo (#%d) receive demo ready for list day %s — %d parcels, net Os ဆီမှရရန် = %s',
            (int) $client->id,
            $listDay,
            count($created),
            number_format($net)
        ));
        $this->command?->info(json_encode($created, JSON_UNESCAPED_UNICODE));
    }

    protected function cleanup(): void
    {
        $orderIds = Order::withTrashed()
            ->where('description', self::MARKER)
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            DispatchOrderItem::query()->whereIn('order_id', $orderIds)->delete();
            Order::withTrashed()->whereIn('id', $orderIds)->forceDelete();
        }

        DispatchOrderItem::query()
            ->where('remark', self::MARKER)
            ->orWhere('item_name', 'like', self::MARKER.'%')
            ->delete();
    }
}
