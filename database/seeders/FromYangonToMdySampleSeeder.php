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
 * Demo parcels waiting in MDY inbound from Yangon hubs (Ngwe Latt Saung / M2M).
 * Status: assigned + hub_user_id + mdy_inbox_at set + mdy_accepted_at null.
 */
class FromYangonToMdySampleSeeder extends Seeder
{
    private const MARKER = 'From Yangon To MDY sample';

    public function run(): void
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();
        $pickupAt = function_exists('yangonTodayOrderReceivedDatetime')
            ? yangonTodayOrderReceivedDatetime()
            : Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s');
        $now = now();

        $src = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->first();

        if (! $src || ! $src->order) {
            $this->command?->warn('No dispatch item template found.');

            return;
        }

        $hubService = app(DispatchHubService::class);
        $hubs = $hubService->accounts();
        if ($hubs->isEmpty()) {
            $this->command?->warn('No Yangon hub accounts found (is_dispatch_hub).');

            return;
        }

        $mdyBranchId = (int) ($hubService->mandalayBranchId()
            ?? Branch::query()->where('status', 1)->where('name', 'မန္တလေး')->value('id')
            ?? 0);
        $ygnBranchIds = $hubService->yangonBranchIds();
        $ygnBranchId = (int) ($ygnBranchIds[0] ?? 0);

        if ($mdyBranchId <= 0 || $ygnBranchId <= 0) {
            $this->command?->warn('Need Yangon and Mandalay branches.');

            return;
        }

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        if ($clients->isEmpty()) {
            $this->command?->warn('No active OS clients found.');

            return;
        }

        $ygnClients = $clients->filter(fn (User $u) => in_array((int) ($u->branch_id ?? 0), $ygnBranchIds, true))->values();
        if ($ygnClients->isEmpty()) {
            $ygnClients = $clients->take(6)->values();
        }

        $this->cleanupPreviousSamples();

        $scenarios = [
            [
                'hub_email' => 'rider.ygn1@demo.local',
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 2,
                'deli' => 3500,
                'address' => 'ချမ်းမြသာစည်၊ ၆၂ လမ်း၊ မန္တလေး',
                'customer' => 'Aye Chan',
                'phone' => '09987654321',
            ],
            [
                'hub_email' => 'rider.ygn1@demo.local',
                'flags' => ['is_shop_order' => 1],
                'type' => 'Shop',
                'parcel' => 1,
                'deli' => 3000,
                'address' => 'မဟာအောင်မြေ၊ ၂၆ လမ်း၊ မန္တလေး',
                'customer' => 'Su Su Hlaing',
                'phone' => '09791234567',
            ],
            [
                'hub_email' => 'rider.ygn1@demo.local',
                'flags' => ['is_gate_order' => 1],
                'type' => 'Gate',
                'parcel' => 1,
                'deli' => 4000,
                'address' => 'အမရပူရ၊ ရတနာပုံလမ်း၊ မန္တလေး',
                'customer' => 'Min Ko',
                'phone' => '09420111222',
            ],
            [
                'hub_email' => 'rider.ygn1@demo.local',
                'flags' => ['is_photo_order' => 1],
                'type' => 'Photo',
                'parcel' => 2,
                'deli' => 4500,
                'address' => 'ချမ်းအေးသာစံ၊ ၈၄ လမ်း၊ မန္တလေး',
                'customer' => 'Hnin Wai',
                'phone' => '09970001122',
            ],
            [
                'hub_email' => 'rider.ygn2@demo.local',
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 1,
                'deli' => 3200,
                'address' => 'ပုသိမ်ကြီး၊ ရွှေဘိုလမ်း၊ မန္တလေး',
                'customer' => 'Kyaw Zay',
                'phone' => '09778889900',
            ],
            [
                'hub_email' => 'rider.ygn2@demo.local',
                'flags' => ['is_shop_order' => 1],
                'type' => 'Shop',
                'parcel' => 2,
                'deli' => 3800,
                'address' => 'ပြည်ကြီးတံခွန်၊ မန္တလေး',
                'customer' => 'Thiri Aung',
                'phone' => '09445556677',
            ],
        ];

        $baseCount = count($scenarios);
        while (count($scenarios) < 30) {
            $n = count($scenarios);
            $row = $scenarios[$n % $baseCount];
            $row['address'] = rtrim((string) $row['address'], '၊ ').'၊ Demo #'.($n + 1);
            $row['customer'] = $row['customer'].' '.($n + 1);
            $scenarios[] = $row;
        }

        $created = 0;
        foreach ($scenarios as $i => $s) {
            $hub = $hubs->first(fn (User $u) => strcasecmp((string) $u->email, $s['hub_email']) === 0)
                ?? $hubs[$i % $hubs->count()];

            $client = $ygnClients[$i % $ygnClients->count()];
            $order = $this->makeOrder(
                $src->order,
                $client,
                $pickupAt,
                $s['flags'],
                (int) $s['parcel'],
                (int) $s['deli'],
                $s['address'],
                $s['type'],
                $i + 1
            );

            $itemCount = max(1, (int) $s['parcel']);
            for ($n = 1; $n <= $itemCount; $n++) {
                $this->makeItem(
                    $src,
                    $order,
                    $today,
                    $now,
                    $ygnBranchId,
                    $mdyBranchId,
                    (int) $hub->id,
                    (int) $s['deli'],
                    $client,
                    $s['address'],
                    $s['customer'],
                    $s['phone'],
                    $s['type'].' #'.($i + 1).'-'.$n
                );
            }

            $created++;
            $this->command?->info(sprintf(
                'Order #%d — %s via %s → %s',
                $order->id,
                $s['type'],
                $hub->name,
                $s['customer']
            ));
        }

        $this->command?->info(sprintf(
            'From Yangon To MDY demo: %d orders (marker: %s).',
            $created,
            self::MARKER
        ));
    }

    protected function cleanupPreviousSamples(): void
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
            ->orWhere('item_name', 'like', self::MARKER.' %')
            ->delete();
    }

    protected function makeOrder(
        Order $template,
        User $client,
        string $pickupAt,
        array $flags,
        int $parcel,
        int $deli,
        string $address,
        string $typeLabel,
        int $seq
    ): Order {
        $order = $template->replicate();
        $order->client_id = (int) $client->id;
        $order->delivery_man_id = null;
        $order->status = 'courier_picked_up';
        $order->pickup_datetime = $pickupAt;
        $order->delivery_datetime = null;
        $order->assign_datetime = Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s');
        $order->total_parcel = $parcel;
        $order->total_amount = $deli * $parcel;
        $order->fixed_charges = $deli;
        $order->description = self::MARKER;
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
            'address' => 'ရန်ကုန် — '.$typeLabel,
            'contact_number' => $phone,
            'latitude' => null,
            'longitude' => null,
        ];
        $order->delivery_point = [
            'name' => '',
            'address' => $address,
            'contact_number' => '',
        ];
        $order->milisecond = (string) ((int) (microtime(true) * 1000) + $seq);
        $order->save();

        return $order;
    }

    protected function makeItem(
        DispatchOrderItem $src,
        Order $order,
        string $today,
        Carbon $now,
        int $fromBranchId,
        int $toBranchId,
        int $hubUserId,
        int $deliAmount,
        User $client,
        string $address,
        string $customerName,
        string $customerPhone,
        string $itemName
    ): void {
        $itemValue = 9000 + (crc32($itemName) % 15000);
        $calc = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, 0, 'customer');

        $item = $src->replicate([
            'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
            'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            'rider_remit_at',
        ]);
        $item->order_id = $order->id;
        $item->code = DispatchOrderItem::generateCode();
        $item->delivery_man_id = null;
        $item->from_branch_id = $fromBranchId;
        $item->to_branch_id = $toBranchId;
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
        $item->customer_name = $customerName !== '' ? $customerName : (string) $client->name;
        $item->customer_phone = $customerPhone !== '' ? $customerPhone : (string) ($client->contact_number ?: '');
        $item->customer_address = $address;
        $item->status = 'assigned';
        $item->delivery_locked = false;
        $item->delivered_photo_id = 0;
        $item->pending_photo_id = 0;
        $item->photo_id = 0;
        $item->cust_photo_id = 0;
        $item->cust_sign_id = 0;
        $item->admin_completed_at = null;
        $item->admin_finished_at = null;
        $item->admin_updated_at = $now;
        $item->received_date = $today;
        $item->assigned_at = $today.' 10:30:00';
        $item->rider_remit_at = null;
        $item->delivered_at = null;
        $item->rider_remit_date = null;

        if (Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            $item->hub_user_id = $hubUserId;
        }
        if (Schema::hasColumn('dispatch_order_items', 'hub_inbox_at')) {
            $item->hub_inbox_at = $now->copy()->subHours(6);
        }
        if (Schema::hasColumn('dispatch_order_items', 'hub_accepted_at')) {
            $item->hub_accepted_at = $now->copy()->subHours(4);
        }
        if (Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            $item->mdy_inbox_at = $now->copy()->subHour();
        }
        if (Schema::hasColumn('dispatch_order_items', 'mdy_accepted_at')) {
            $item->mdy_accepted_at = null;
        }

        $item->save();
    }
}
