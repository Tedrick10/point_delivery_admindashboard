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
 * Demo parcels for Admin Assign 100 (MDY pool).
 * status=assigned, hub_user_id null, mdy_inbox_at null, unassigned rider.
 */
class Assign100SampleSeeder extends Seeder
{
    private const MARKER = 'Assign 100 sample';

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

        $templateOrder = $src?->order;

        $branches = Branch::query()
            ->where('status', 1)
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($branches->isEmpty()) {
            $this->command?->warn('No active branches found.');

            return;
        }

        $mdyBranch = $branches->firstWhere('name', 'မန္တလေး') ?? $branches->first();
        $mdyBranchId = (int) $mdyBranch->id;

        $preferredNames = [
            'မန္တလေး',
            'လားရှိုး',
            'တောင်ကြီး',
            'ပြင်ဦးလွင်',
            'Yangon Ngwe Latt Saung',
            'Yangon M2M',
            'Food',
        ];

        $destBranches = collect();
        foreach ($preferredNames as $name) {
            $match = $branches->first(fn (Branch $b) => strcasecmp((string) $b->name, $name) === 0
                || str_contains((string) $b->name, $name));
            if ($match && ! $destBranches->contains(fn ($b) => (int) $b->id === (int) $match->id)) {
                $destBranches->push($match);
            }
        }
        foreach ($branches as $branch) {
            if ($destBranches->count() >= 7) {
                break;
            }
            if (! $destBranches->contains(fn ($b) => (int) $b->id === (int) $branch->id)) {
                $destBranches->push($branch);
            }
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

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->excludeDispatchHubs()
            ->orderBy('id')
            ->get();

        if ($riders->isEmpty()) {
            $this->command?->warn('Need at least 1 active pickup rider.');

            return;
        }

        $pickupRider = $riders->first(fn (User $u) => (int) ($u->branch_id ?? 0) === $mdyBranchId)
            ?? $riders->first();

        $this->cleanupPreviousSamples();

        $scenarios = [
            [
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 2,
                'deli' => 3000,
                'customer' => 'Aye Myat',
                'phone' => '09970010001',
                'address' => 'ချမ်းမြသာစည်၊ ၆၂ လမ်း',
            ],
            [
                'flags' => ['is_shop_order' => 1],
                'type' => 'Shop',
                'parcel' => 1,
                'deli' => 2500,
                'customer' => 'Ko Min Thu',
                'phone' => '09791234001',
                'address' => 'မဟာအောင်မြေ၊ ၂၆ လမ်း',
            ],
            [
                'flags' => ['is_gate_order' => 1],
                'type' => 'Gate',
                'parcel' => 1,
                'deli' => 4000,
                'customer' => 'Su Hlaing',
                'phone' => '09420111001',
                'address' => 'အမရပူရ၊ ရတနာပုံလမ်း',
            ],
            [
                'flags' => ['is_photo_order' => 1],
                'type' => 'Photo',
                'parcel' => 2,
                'deli' => 3500,
                'customer' => 'Hnin Ei',
                'phone' => '09970011002',
                'address' => 'ချမ်းအေးသာစံ၊ ၈၄ လမ်း',
            ],
            [
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 1,
                'deli' => 2800,
                'customer' => 'Zaw Htet',
                'phone' => '09778880001',
                'address' => 'ပုသိမ်ကြီး၊ ရွှေဘိုလမ်း',
            ],
            [
                'flags' => ['is_shop_order' => 1],
                'type' => 'Shop',
                'parcel' => 1,
                'deli' => 3200,
                'customer' => 'Thiri Oo',
                'phone' => '09445550001',
                'address' => 'ပြည်ကြီးတံခွန်',
            ],
            [
                'flags' => ['is_text_order' => 1],
                'type' => 'Text',
                'parcel' => 2,
                'deli' => 4500,
                'customer' => 'Nay Lin',
                'phone' => '09991110001',
                'address' => 'စစ်ကိုင်းလမ်း',
            ],
            [
                'flags' => ['is_photo_order' => 1],
                'type' => 'Photo',
                'parcel' => 1,
                'deli' => 3000,
                'customer' => 'May Thu',
                'phone' => '09667770001',
                'address' => '၃၅ လမ်း၊ ကြားပိုင်း',
            ],
        ];

        $createdItems = 0;
        foreach ($scenarios as $i => $s) {
            $dest = $destBranches[$i % $destBranches->count()];
            $client = $clients[$i % $clients->count()];

            $order = $this->makeOrder(
                $templateOrder,
                $client,
                $pickupRider,
                $pickupAt,
                $s['flags'],
                (int) $s['parcel'],
                (int) $s['deli'],
                (string) $s['address'],
                (string) $s['type'],
                $i + 1
            );

            $itemCount = max(1, (int) $s['parcel']);
            for ($n = 1; $n <= $itemCount; $n++) {
                $this->makeItem(
                    $src,
                    $order,
                    $today,
                    $now,
                    $mdyBranchId,
                    (int) $dest->id,
                    (int) $s['deli'],
                    $client,
                    (string) $s['address'].'၊ '.$dest->name,
                    (string) $s['customer'],
                    (string) $s['phone'],
                    $s['type'].' #'.($i + 1).'-'.$n
                );
                $createdItems++;
            }

            $this->command?->info(sprintf(
                'Order #%d — %s → %s (%s)',
                $order->id,
                $s['type'],
                $dest->name,
                $s['customer']
            ));
        }

        $this->command?->info(sprintf(
            'Assign 100 demo: %d items across %d destinations (marker: %s).',
            $createdItems,
            $destBranches->count(),
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
        ?Order $template,
        User $client,
        User $pickupRider,
        string $pickupAt,
        array $flags,
        int $parcel,
        int $deli,
        string $address,
        string $typeLabel,
        int $seq
    ): Order {
        $order = $template ? $template->replicate() : new Order();
        $order->client_id = (int) $client->id;
        $order->delivery_man_id = (int) $pickupRider->id;
        $order->country_id = (int) ($template?->country_id ?: $client->country_id ?: (\App\Models\Country::query()->value('id') ?? 1));
        $order->city_id = (int) ($template?->city_id ?: $client->city_id ?: (\App\Models\City::query()->value('id') ?? 1));
        $order->status = 'courier_picked_up';
        $order->date = Carbon::now('Asia/Yangon')->toDateString();
        $order->pickup_datetime = $pickupAt;
        $order->delivery_datetime = null;
        $order->assign_datetime = Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s');
        $order->total_parcel = $parcel;
        $order->total_weight = $template?->total_weight ?: 1;
        $order->total_distance = $template?->total_distance ?: 0;
        $order->total_amount = $deli * $parcel;
        $order->fixed_charges = $deli;
        $order->weight_charge = $template?->weight_charge ?: 0;
        $order->distance_charge = $template?->distance_charge ?: 0;
        $order->insurance_charge = $template?->insurance_charge ?: 0;
        $order->vehicle_charge = $template?->vehicle_charge ?: 0;
        $order->extra_charges = $template?->extra_charges;
        $order->payment_collect_from = $template?->payment_collect_from ?: 'on_delivery';
        $order->currency = $template?->currency ?: 'MMK';
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

        $phone = (string) ($client->contact_number ?: '09'.str_pad((string) (300000000 + $seq), 9, '0', STR_PAD_LEFT));
        $order->pickup_point = [
            'name' => (string) $client->name,
            'address' => 'မန္တလေး — '.$typeLabel,
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
        ?DispatchOrderItem $src,
        Order $order,
        string $today,
        Carbon $now,
        int $fromBranchId,
        int $toBranchId,
        int $deliAmount,
        User $client,
        string $address,
        string $customerName,
        string $customerPhone,
        string $itemName
    ): void {
        $itemValue = 8000 + (crc32($itemName) % 18000);
        $calc = DispatchOrderItem::computeAmounts($itemValue, $deliAmount, 0, 0, 'customer');

        $item = $src
            ? $src->replicate([
                'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
                'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
                'rider_remit_at',
            ])
            : new DispatchOrderItem();

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
        $item->assigned_at = $today.' 11:15:00';
        $item->rider_remit_at = null;
        $item->delivered_at = null;
        $item->rider_remit_date = null;
        $item->weight = 1;
        $item->township = null;
        $item->delivery_city = null;
        $item->city_id = $order->city_id;

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
