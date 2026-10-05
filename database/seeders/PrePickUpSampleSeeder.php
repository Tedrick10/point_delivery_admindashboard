<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Demo orders for Admin Pre Pick Up list (next Yangon day and beyond).
 */
class PrePickUpSampleSeeder extends Seeder
{
    private const MARKER = 'Pre Pick Up sample';

    public function run(): void
    {
        $tz = 'Asia/Yangon';
        $now = now();
        $nextDay = Carbon::now($tz)->addDay()->startOfDay();
        $dayAfter = $nextDay->copy()->addDay();

        $src = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->first();

        if (! $src || ! $src->order) {
            $this->command?->warn('No dispatch item template found.');

            return;
        }

        $mdyBranchId = (int) (Branch::query()->where('status', 1)->where('name', 'မန္တလေး')->value('id')
            ?? Branch::query()->where('status', 1)->orderBy('id')->value('id')
            ?? 0);

        if ($mdyBranchId <= 0) {
            $this->command?->warn('No active branch found.');

            return;
        }

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        $mdyClients = $clients->filter(fn (User $u) => (int) ($u->branch_id ?? 0) === $mdyBranchId)->values();
        if ($mdyClients->isEmpty()) {
            $mdyClients = $clients->take(5)->values();
        }

        if ($mdyClients->isEmpty()) {
            $this->command?->warn('No active OS clients found.');

            return;
        }

        $this->cleanupPreviousSamples();

        $scenarios = [
            [
                'client' => $mdyClients[0],
                'pickup' => $nextDay->format('Y-m-d H:i:s'),
                'received' => $nextDay->toDateString(),
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 2,
                'deli' => 3000,
                'address' => 'ချမ်းမြသာစည်၊ ၆၂ လမ်း၊ မန္တလေး',
                'status' => 'create',
            ],
            [
                'client' => $mdyClients[1] ?? $mdyClients[0],
                'pickup' => $nextDay->copy()->setTime(8, 0)->format('Y-m-d H:i:s'),
                'received' => $nextDay->toDateString(),
                'flags' => ['is_shop_order' => 1],
                'type' => 'Shop',
                'parcel' => 1,
                'deli' => 3500,
                'address' => 'အောင်မြေသာစံ၊ ၂၈ လမ်း၊ မန္တလေး',
                'status' => 'active',
            ],
            [
                'client' => $mdyClients[2] ?? $mdyClients[0],
                'pickup' => $nextDay->copy()->setTime(10, 30)->format('Y-m-d H:i:s'),
                'received' => $nextDay->toDateString(),
                'flags' => ['is_photo_order' => 1],
                'type' => 'Photo',
                'parcel' => 3,
                'deli' => 2500,
                'address' => 'မဟာအောင်မြေ၊ ၈၄ လမ်း၊ မန္တလေး',
                'status' => 'create',
            ],
            [
                'client' => $mdyClients[3] ?? $mdyClients[0],
                'pickup' => $dayAfter->format('Y-m-d H:i:s'),
                'received' => $dayAfter->toDateString(),
                'flags' => ['is_gate_order' => 1],
                'type' => 'Gate',
                'parcel' => 1,
                'deli' => 4000,
                'address' => 'ပြည်ကြီးတံခွန်၊ ရွှေတောင်ကြား၊ မန္တလေး',
                'status' => 'create',
            ],
            [
                'client' => $mdyClients[4] ?? $mdyClients[0],
                'pickup' => $dayAfter->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'received' => $dayAfter->toDateString(),
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 2,
                'deli' => 3200,
                'address' => 'ချမ်းအေးသာစံ၊ ၃၅ လမ်း၊ မန္တလေး',
                'status' => 'active',
            ],
            [
                'client' => $mdyClients[0],
                'pickup' => $nextDay->copy()->addDays(2)->format('Y-m-d H:i:s'),
                'received' => $nextDay->copy()->addDays(2)->toDateString(),
                'flags' => ['is_shop_order' => 1],
                'type' => 'Shop',
                'parcel' => 1,
                'deli' => 4500,
                'address' => 'အမရပူရ၊ ရွှေစာရံ၊ မန္တလေး',
                'status' => 'create',
            ],
        ];

        $baseCount = count($scenarios);
        while (count($scenarios) < 30) {
            $n = count($scenarios);
            $row = $scenarios[$n % $baseCount];
            $row['address'] = rtrim((string) $row['address'], '၊ ').'၊ Demo #'.($n + 1);
            $scenarios[] = $row;
        }

        $created = 0;
        foreach ($scenarios as $i => $s) {
            /** @var User $client */
            $client = $s['client'];
            $order = $this->makeOrder(
                $src->order,
                $client,
                $s['pickup'],
                $s['status'],
                $s['flags'],
                $s['parcel'],
                $s['deli'],
                $s['address'],
                $i + 1
            );

            $itemCount = max(1, (int) $s['parcel']);
            for ($n = 1; $n <= $itemCount; $n++) {
                $this->makeItem(
                    $src,
                    $order,
                    $s['received'],
                    $now,
                    $mdyBranchId,
                    (int) $s['deli'],
                    $client,
                    $s['address'],
                    $s['type'].' #'.($i + 1).'-'.$n
                );
            }

            $created++;
            $this->command?->info(sprintf(
                'Pre Pick Up #%d — %s %s → %s (%s)',
                $order->id,
                $s['type'],
                $s['received'],
                $client->name,
                $s['pickup']
            ));
        }

        $this->command?->info(sprintf(
            'Pre Pick Up demo: %d orders (from %s).',
            $created,
            $nextDay->toDateString()
        ));
    }

    protected function cleanupPreviousSamples(): void
    {
        $orderIds = Order::withTrashed()
            ->where(function ($q) {
                $q->where('description', self::MARKER)
                    ->orWhere('description', 'နောက်နေ့ Pre Pick Up')
                    ->orWhereHas('dispatchItems', function ($item) {
                        $item->where('remark', self::MARKER);
                    });
            })
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            DispatchOrderItem::query()->whereIn('order_id', $orderIds)->delete();
            Order::withTrashed()->whereIn('id', $orderIds)->forceDelete();
        }

        DispatchOrderItem::query()
            ->where('remark', self::MARKER)
            ->orWhere('item_name', 'like', self::MARKER.' %')
            ->delete();
    }

    protected function makeOrder(
        Order $template,
        User $client,
        string $pickupAt,
        string $status,
        array $flags,
        int $parcel,
        int $deli,
        string $address,
        int $seq
    ): Order {
        $order = $template->replicate();
        $order->client_id = (int) $client->id;
        $order->delivery_man_id = null;
        $order->status = $status;
        $order->pickup_datetime = $pickupAt;
        $order->date = $pickupAt;
        $order->delivery_datetime = null;
        $order->assign_datetime = null;
        $order->total_parcel = $parcel;
        $order->total_amount = $deli * $parcel;
        $order->fixed_charges = $deli;
        $order->description = 'နောက်နေ့ Pre Pick Up';
        $order->reason = null;
        $order->pickup_error_at = null;
        $order->pickup_error_choice = null;
        $order->pickup_error_choice_at = null;
        $order->is_text_order = 0;
        $order->is_photo_order = 0;
        $order->is_shop_order = 0;
        $order->is_gate_order = 0;
        $order->is_self_order = 0;
        foreach ($flags as $key => $value) {
            $order->{$key} = $value;
        }

        $phone = (string) ($client->contact_number ?: '09'.str_pad((string) (200000000 + $seq), 9, '0', STR_PAD_LEFT));
        $order->pickup_point = [
            'name' => (string) $client->name,
            'address' => $address,
            'contact_number' => $phone,
            'latitude' => null,
            'longitude' => null,
        ];
        $order->delivery_point = [
            'name' => '',
            'address' => '',
            'contact_number' => '',
        ];
        $order->milisecond = (string) ((int) (microtime(true) * 1000) + $seq + 500);
        $order->save();

        return $order;
    }

    protected function makeItem(
        DispatchOrderItem $src,
        Order $order,
        string $receivedDate,
        Carbon $now,
        int $branchId,
        int $deliAmount,
        User $client,
        string $address,
        string $itemName
    ): void {
        $itemValue = 7000 + (crc32($itemName) % 10000);
        $calc = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, 0, 'customer');

        $item = $src->replicate([
            'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
            'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            'rider_remit_at',
        ]);
        $item->order_id = $order->id;
        $item->code = DispatchOrderItem::generateCode();
        $item->delivery_man_id = null;
        $item->from_branch_id = $branchId;
        $item->to_branch_id = $branchId;
        $item->item_name = self::MARKER.' '.$itemName;
        $item->remark = self::MARKER;
        $item->item_value = $itemValue;
        $item->deli_amount = $deliAmount;
        $item->os_paid = 0;
        $item->advance_paid = 0;
        $item->gate_amount = 0;
        $item->gate_os_paid = 0;
        $item->delivered_type = null;
        $item->credit_to = 'customer';
        $item->pickup_pay_mode = 'customer_pay';
        $item->cust_get = $calc['cust_get'];
        $item->os_to_pay = $calc['os_to_pay'];
        $item->customer_name = (string) $client->name;
        $item->customer_phone = (string) ($client->contact_number ?: '');
        $item->customer_address = $address;
        $item->status = 'collected';
        $item->delivery_locked = false;
        $item->delivered_photo_id = 0;
        $item->pending_photo_id = 0;
        $item->photo_id = 0;
        $item->cust_photo_id = 0;
        $item->cust_sign_id = 0;
        $item->admin_completed_at = null;
        $item->admin_finished_at = null;
        $item->admin_updated_at = null;
        $item->received_date = $receivedDate;
        $item->assigned_at = null;
        $item->rider_remit_at = null;
        $item->delivered_at = null;
        $item->rider_remit_date = null;

        if (Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            $item->hub_user_id = null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'hub_inbox_at')) {
            $item->hub_inbox_at = null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'hub_accepted_at')) {
            $item->hub_accepted_at = null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            $item->mdy_inbox_at = null;
        }
        if (Schema::hasColumn('dispatch_order_items', 'mdy_accepted_at')) {
            $item->mdy_accepted_at = null;
        }

        $item->save();
    }
}
