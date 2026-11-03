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
 * ငွေရှင်းတမ်း → Os ဆီသို့လွှဲရန် with settleable Os Pay (Bonus Points).
 */
class ZinMinOoOsPayDemoSeeder extends Seeder
{
    private const MARKER = 'ZMO Point System Os Pay demo';

    private const RECEIVE_MARKER = 'ZMO Point System receive demo';

    public function run(): void
    {
        $listDay = function_exists('yangonSettlementDefaultDate')
            ? yangonSettlementDefaultDate('Y-m-d')
            : Carbon::now('Asia/Yangon')->toDateString();

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

        // Keep OS on branch 6 (Admin default) so the row is visible.
        if ((int) ($client->branch_id ?? 0) !== 6) {
            $client->branch_id = 6;
            $client->save();
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

        $branchId = (int) ($client->branch_id ?: $rider->branch_id ?: 6);

        // Item Value > 0 + Os Pay → negative net (Os ဆီသို့လွှဲရန်) with unpaid Os Pay to settle.
        // Total Os Pay = 2,500 + 3,000 + 2,000 = 7,500 (fits under a 10,000 Bonus Request).
        $parcels = [
            ['item_value' => 57000, 'deli' => 2500, 'customer' => 'ZU ZU', 'phone' => '09956813810'],
            ['item_value' => 42000, 'deli' => 3000, 'customer' => 'Aye Aye', 'phone' => '09911112222'],
            ['item_value' => 18500, 'deli' => 2000, 'customer' => 'Hla Hla', 'phone' => '09933334444'],
        ];

        $created = [];

        foreach ($parcels as $i => $row) {
            $itemValue = (float) $row['item_value'];
            $deli = (float) $row['deli'];
            $calc = DispatchOrderItem::computeAmounts($itemValue, $deli, 0, 0, 'os');

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
                'total_amount' => $itemValue + $deli,
                'fixed_charges' => $deli,
                'description' => self::MARKER,
                'pickup_point' => [
                    'name' => (string) $client->name,
                    'address' => 'Demo OS pay',
                    'contact_number' => (string) ($client->contact_number ?: '09900000000'),
                ],
                'delivery_point' => [
                    'name' => (string) $row['customer'],
                    'address' => '11/'.(70 + $i),
                    'contact_number' => (string) $row['phone'],
                ],
            ]);

            $item = DispatchOrderItem::create([
                'order_id' => $order->id,
                'code' => DispatchOrderItem::generateCode(),
                'delivery_man_id' => (int) $rider->id,
                'from_branch_id' => $branchId,
                'to_branch_id' => $branchId,
                'item_name' => self::MARKER.' #'.($i + 1),
                'item_value' => $itemValue,
                'deli_amount' => $deli,
                'credit_to' => 'os',
                'pickup_pay_mode' => 'os_pay',
                'os_paid' => 0,
                'cust_get' => $calc['cust_get'],
                'os_to_pay' => $calc['os_to_pay'],
                'customer_name' => (string) $row['customer'],
                'customer_phone' => (string) $row['phone'],
                'customer_address' => '11/'.(70 + $i),
                'remark' => self::MARKER,
                'status' => 'completed',
                'admin_completed_at' => $completedAt->copy()->addMinutes($i * 5),
                'admin_finished_at' => null,
                'received_date' => $listDay,
            ]);

            $created[] = [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'item_value' => $itemValue,
                'deli' => $deli,
                'os_to_pay' => (float) $item->os_to_pay,
                'display' => (float) $item->displayOsToPay(),
                'pay_label' => dispatchDeliPayLabel($item),
            ];
        }

        $svc = app(OsSettlementService::class);
        $items = $svc->completedItemsForPeriod((int) $client->id, $listDay, $listDay, $branchId);
        $nettable = $svc->nettableSettlementItems($items);
        $net = $svc->netSettlementOsToPay($nettable);
        $unpaidOsPay = round((float) $nettable
            ->filter(static fn ($item) => dispatchDeliPayLabel($item) === 'Os Pay')
            ->sum(static function ($item) {
                return max(0, (float) ($item->deli_amount ?? 0) - (float) ($item->os_paid ?? 0));
            }), 2);

        $this->command?->info(sprintf(
            'Zin Min Oo (#%d) Os Pay demo ready for %s — %d parcels, net=%s, unpaid Os Pay=%s (branch %d)',
            (int) $client->id,
            $listDay,
            count($created),
            number_format($net),
            number_format($unpaidOsPay),
            $branchId
        ));
        $this->command?->info(json_encode($created, JSON_UNESCAPED_UNICODE));
    }

    protected function cleanup(): void
    {
        foreach ([self::MARKER, self::RECEIVE_MARKER] as $marker) {
            $orderIds = Order::withTrashed()
                ->where('description', $marker)
                ->pluck('id');

            if ($orderIds->isNotEmpty()) {
                DispatchOrderItem::query()->whereIn('order_id', $orderIds)->delete();
                Order::withTrashed()->whereIn('id', $orderIds)->forceDelete();
            }

            DispatchOrderItem::query()
                ->where('remark', $marker)
                ->orWhere('item_name', 'like', $marker.'%')
                ->delete();
        }
    }
}
