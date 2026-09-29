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
 * Demo orders for Admin Order List tabs (today, Yangon calendar):
 * Pick Up / Pick Up Rider / Rider Done / Admin Done / ကြိုတင်.
 */
class OrderListSampleSeeder extends Seeder
{
    private const MARKER = 'Order List sample';

    public function run(): void
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();
        $pickupAt = yangonTodayOrderReceivedDatetime();
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

        $mdyBranchId = (int) (Branch::query()->where('status', 1)->where('name', 'မန္တလေး')->value('id')
            ?? Branch::query()->where('status', 1)->orderBy('id')->value('id')
            ?? 0);
        $ygnBranchId = (int) (Branch::query()->where('status', 1)->where('name', 'like', '%Yangon%')->value('id') ?? 0);

        if ($mdyBranchId <= 0) {
            $this->command?->warn('No active branch found.');

            return;
        }

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        $kyoClient = $clients->first(fn (User $u) => (bool) ($u->is_kyo_shin ?? false))
            ?? $clients->first();
        $mdyClients = $clients->filter(fn (User $u) => (int) ($u->branch_id ?? 0) === $mdyBranchId)->values();
        $ygnClients = $ygnBranchId > 0
            ? $clients->filter(fn (User $u) => (int) ($u->branch_id ?? 0) === $ygnBranchId)->values()
            : collect();

        if ($mdyClients->isEmpty()) {
            $mdyClients = $clients->take(4)->values();
        }

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->excludeDispatchHubs()
            ->orderBy('name')
            ->get();

        $mdyRiders = $riders->filter(fn (User $u) => (int) ($u->branch_id ?? 0) === $mdyBranchId)->values();
        if ($mdyRiders->isEmpty()) {
            $mdyRiders = $riders->take(4)->values();
        }
        $ygnRiders = $ygnBranchId > 0
            ? $riders->filter(fn (User $u) => (int) ($u->branch_id ?? 0) === $ygnBranchId)->values()
            : collect();

        if ($mdyRiders->isEmpty()) {
            $this->command?->warn('Need at least 1 active rider.');

            return;
        }

        $this->cleanupPreviousSamples();

        $scenarios = [
            [
                'tab' => 'pickup',
                'client' => $mdyClients[0] ?? $clients->first(),
                'rider' => null,
                'branch' => $mdyBranchId,
                'order_status' => 'create',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_text_order' => 1],
                'type_label' => 'Text',
                'parcel' => 2,
                'deli' => 3000,
                'address' => 'ဗိုလ်တစ်ထောင်၊ ရန်ကုန်လမ်း၊ မန္တလေး',
            ],
            [
                'tab' => 'pickup',
                'client' => $mdyClients[1] ?? $mdyClients[0] ?? $clients->first(),
                'rider' => null,
                'branch' => $mdyBranchId,
                'order_status' => 'active',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_shop_order' => 1],
                'type_label' => 'Shop',
                'parcel' => 1,
                'deli' => 2500,
                'address' => 'ချမ်းမြသာစည်၊ ၈၄ လမ်း၊ မန္တလေး',
            ],
            [
                'tab' => 'pickup_rider',
                'client' => $mdyClients[2] ?? $mdyClients[0] ?? $clients->first(),
                'rider' => $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'courier_assigned',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_photo_order' => 1],
                'type_label' => 'Photo',
                'parcel' => 3,
                'deli' => 3500,
                'address' => 'အောင်မြေသာစံ၊ ၂၆ လမ်း၊ မန္တလေး',
            ],
            [
                'tab' => 'pickup_rider',
                'client' => $mdyClients[3] ?? $mdyClients[0] ?? $clients->first(),
                'rider' => $mdyRiders[1] ?? $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'courier_arrived',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_gate_order' => 1],
                'type_label' => 'Gate',
                'parcel' => 1,
                'deli' => 4000,
                'address' => 'မဟာအောင်မြေ၊ ၇၈ လမ်း၊ မန္တလေး',
            ],
            [
                'tab' => 'rider_done',
                'client' => $mdyClients[0] ?? $clients->first(),
                'rider' => $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'courier_picked_up',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_text_order' => 1],
                'type_label' => 'Text',
                'parcel' => 2,
                'deli' => 3200,
                'address' => 'ပြည်ကြီးတံခွန်၊ ရွှေတောင်ကြား၊ မန္တလေး',
            ],
            [
                'tab' => 'rider_done',
                'client' => $mdyClients[1] ?? $mdyClients[0] ?? $clients->first(),
                'rider' => $mdyRiders[1] ?? $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'courier_picked_up',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_shop_order' => 1],
                'type_label' => 'Shop',
                'parcel' => 1,
                'deli' => 2800,
                'address' => 'ချမ်းအေးသာစံ၊ ၃၅ လမ်း၊ မန္တလေး',
            ],
            [
                'tab' => 'admin_done',
                'client' => $mdyClients[2] ?? $mdyClients[0] ?? $clients->first(),
                'rider' => $mdyRiders[2] ?? $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'courier_assigned',
                'item_status' => 'collected',
                'admin_updated' => true,
                'flags' => ['is_text_order' => 1],
                'type_label' => 'Text',
                'parcel' => 2,
                'deli' => 3000,
                'address' => 'အမရပူရ၊ ရွှေစာရံ၊ မန္တလေး',
            ],
            [
                'tab' => 'admin_done',
                'client' => $mdyClients[3] ?? $mdyClients[0] ?? $clients->first(),
                'rider' => $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'active',
                'item_status' => 'collected',
                'admin_updated' => true,
                'flags' => ['is_photo_order' => 1],
                'type_label' => 'Photo',
                'parcel' => 1,
                'deli' => 4500,
                'address' => 'ပုသိမ်ကြီး၊ ရွှေဘိုလမ်း၊ မန္တလေး',
            ],
            [
                'tab' => 'kyo_shin',
                'client' => $kyoClient,
                'rider' => $mdyRiders[0],
                'branch' => $mdyBranchId,
                'order_status' => 'courier_picked_up',
                'item_status' => 'assigned',
                'admin_updated' => true,
                'flags' => ['is_shop_order' => 1],
                'type_label' => 'Shop',
                'parcel' => 2,
                'deli' => 5000,
                'address' => 'ကျောက်တံတား၊ ဗိုလ်ချုပ်လမ်း၊ မန္တလေး',
            ],
        ];

        if ($ygnClients->isNotEmpty() && $ygnRiders->isNotEmpty() && $ygnBranchId > 0) {
            $scenarios[] = [
                'tab' => 'pickup_rider',
                'client' => $ygnClients[0],
                'rider' => $ygnRiders[0],
                'branch' => $ygnBranchId,
                'order_status' => 'courier_assigned',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_text_order' => 1],
                'type_label' => 'Text',
                'parcel' => 1,
                'deli' => 3500,
                'address' => 'ဗိုလ်တထောင်၊ အင်းစိန်လမ်း၊ ရန်ကုန်',
            ];
            $scenarios[] = [
                'tab' => 'rider_done',
                'client' => $ygnClients[1] ?? $ygnClients[0],
                'rider' => $ygnRiders[1] ?? $ygnRiders[0],
                'branch' => $ygnBranchId,
                'order_status' => 'courier_picked_up',
                'item_status' => 'collected',
                'admin_updated' => false,
                'flags' => ['is_shop_order' => 1],
                'type_label' => 'Shop',
                'parcel' => 2,
                'deli' => 4000,
                'address' => 'လသာ၊ အနော်ရထာလမ်း၊ ရန်ကုန်',
            ];
        }

        $created = 0;
        foreach ($scenarios as $i => $s) {
            /** @var User $client */
            $client = $s['client'];
            if (! $client) {
                continue;
            }

            $order = $this->makeOrder(
                $src->order,
                $client,
                $s['rider'] ?? null,
                $pickupAt,
                $s['order_status'],
                $s['flags'],
                $s['parcel'],
                $s['deli'],
                $s['address'],
                $s['type_label'],
                $i + 1
            );

            $itemCount = max(1, (int) $s['parcel']);
            for ($n = 1; $n <= $itemCount; $n++) {
                $this->makeItem(
                    $src,
                    $order,
                    $s['rider'] ?? null,
                    $today,
                    $now,
                    (int) $s['branch'],
                    (int) $s['deli'],
                    $s['item_status'],
                    (bool) $s['admin_updated'],
                    $client,
                    $s['address'],
                    $s['type_label'].' #'.($i + 1).'-'.$n
                );
            }

            $created++;
            $this->command?->info(sprintf(
                'Order #%d — %s (%s) → %s',
                $order->id,
                $s['tab'],
                $s['type_label'],
                $client->name
            ));
        }

        $this->command?->info(sprintf(
            'Order List demo: %d orders for %s (marker: %s).',
            $created,
            $today,
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
        ?User $rider,
        string $pickupAt,
        string $status,
        array $flags,
        int $parcel,
        int $deli,
        string $address,
        string $typeLabel,
        int $seq
    ): Order {
        $order = $template->replicate();
        $order->client_id = (int) $client->id;
        $order->delivery_man_id = $rider ? (int) $rider->id : null;
        $order->status = $status;
        $order->pickup_datetime = $pickupAt;
        $order->delivery_datetime = null;
        $order->assign_datetime = $rider ? Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s') : null;
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

        $phone = (string) ($client->contact_number ?: '09'.str_pad((string) (100000000 + $seq), 9, '0', STR_PAD_LEFT));
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
        $order->milisecond = (string) ((int) (microtime(true) * 1000) + $seq);
        $order->save();

        return $order;
    }

    protected function makeItem(
        DispatchOrderItem $src,
        Order $order,
        ?User $rider,
        string $today,
        Carbon $now,
        int $branchId,
        int $deliAmount,
        string $itemStatus,
        bool $adminUpdated,
        User $client,
        string $address,
        string $itemName
    ): void {
        $itemValue = 8000 + (crc32($itemName) % 12000);
        $calc = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, 0, 'customer');

        $item = $src->replicate([
            'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
            'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            'rider_remit_at',
        ]);
        $item->order_id = $order->id;
        $item->code = DispatchOrderItem::generateCode();
        $item->delivery_man_id = $rider ? (int) $rider->id : null;
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
        $item->status = $itemStatus;
        $item->delivery_locked = false;
        $item->delivered_photo_id = 0;
        $item->pending_photo_id = 0;
        $item->photo_id = 0;
        $item->cust_photo_id = 0;
        $item->cust_sign_id = 0;
        $item->admin_completed_at = $adminUpdated ? $now : null;
        $item->admin_finished_at = $adminUpdated ? $now : null;
        $item->admin_updated_at = $adminUpdated ? $now : null;
        $item->received_date = $today;
        $item->assigned_at = $today.' 09:00:00';
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
