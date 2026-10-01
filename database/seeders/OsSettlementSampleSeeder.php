<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\OsSettlementService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Demo unfinished completed parcels for ငွေရှင်းတမ်း Pay / Receive tabs.
 * List day = Yangon yesterday (matches page default FROM/TO).
 */
class OsSettlementSampleSeeder extends Seeder
{
    private const MARKER = 'OS settlement sample';

    public function run(): void
    {
        $listDay = function_exists('yangonSettlementDefaultDate')
            ? yangonSettlementDefaultDate('Y-m-d')
            : Carbon::now('Asia/Yangon')->subDay()->toDateString();
        $completedAt = Carbon::parse($listDay, 'Asia/Yangon')->addDay()->setTime(10, 30, 0);
        $completedDay = $completedAt->toDateString();
        $pickupAt = function_exists('yangonTodayOrderReceivedDatetime')
            ? yangonTodayOrderReceivedDatetime()
            : Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s');

        $src = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->first();
        $templateOrder = $src?->order;

        $branches = Branch::query()->where('status', 1)->orderBy('id')->get(['id', 'name']);
        if ($branches->isEmpty()) {
            $this->command?->warn('No active branches found.');

            return;
        }

        $mdy = $branches->firstWhere('name', 'မန္တလေး') ?? $branches->first();
        $mdyId = (int) $mdy->id;

        $clients = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(8)
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
            $this->command?->warn('Need at least 1 active rider.');

            return;
        }

        $pickupRider = $riders->first(fn (User $u) => (int) ($u->branch_id ?? 0) === $mdyId)
            ?? $riders->first();

        $this->cleanupPreviousSamples();

        $payAmounts = [8000, 12000, 15000, 9000, 11000, 14000, 7500, 16000];
        $recvAmounts = [3000, 4500, 5000, 3500, 6000, 2800, 4200, 5500];
        $destCycle = $branches->values();

        $createdPay = 0;
        $createdRecv = 0;

        foreach ($clients as $i => $client) {
            $dest = $destCycle[$i % $destCycle->count()];
            $toBranchId = (int) $dest->id;
            // Keep most on မန္တလေး so default tab is populated.
            if ($i < 5) {
                $toBranchId = $mdyId;
            }

            $order = $this->makeOrder(
                $templateOrder,
                $client,
                $pickupRider,
                $pickupAt,
                $i + 1
            );

            $payCalc = DispatchOrderItem::computeAmounts($payAmounts[$i % count($payAmounts)], 3000, 0, 0, 'customer');
            $this->makeItem(
                $src,
                $order,
                $mdyId,
                $toBranchId,
                $completedAt->copy()->addMinutes($i),
                $completedDay,
                [
                    'item_name' => self::MARKER.' Pay '.($i + 1),
                    'item_value' => $payAmounts[$i % count($payAmounts)],
                    'deli_amount' => 3000,
                    'credit_to' => 'customer',
                    'pickup_pay_mode' => 'customer_pay',
                    'cust_get' => $payCalc['cust_get'],
                    'os_to_pay' => $payCalc['os_to_pay'],
                    'customer_name' => (string) $client->name,
                ]
            );
            $createdPay++;

            $recvCalc = DispatchOrderItem::computeAmounts(0, $recvAmounts[$i % count($recvAmounts)], 0, 0, 'os');
            $this->makeItem(
                $src,
                $order,
                $mdyId,
                $toBranchId,
                $completedAt->copy()->addMinutes(30 + $i),
                $completedDay,
                [
                    'item_name' => self::MARKER.' Receive '.($i + 1),
                    'item_value' => 0,
                    'deli_amount' => $recvAmounts[$i % count($recvAmounts)],
                    'credit_to' => 'os',
                    'pickup_pay_mode' => 'os_pay',
                    'cust_get' => $recvCalc['cust_get'],
                    'os_to_pay' => $recvCalc['os_to_pay'],
                    'customer_name' => (string) $client->name,
                ]
            );
            $createdRecv++;

            $this->command?->info(sprintf(
                '%s → branch #%d — pay & receive samples',
                $client->name,
                $toBranchId
            ));
        }

        $svc = app(OsSettlementService::class);
        $items = $svc->completedItemsForPeriod(null, $listDay, $listDay, $mdyId);
        $payOs = $items->filter(static fn ($item) => (float) $item->displayOsToPay() < 0)
            ->groupBy(static fn ($item) => (int) ($item->order?->client_id ?? 0))
            ->count();
        $recvOs = $items->filter(static fn ($item) => (float) $item->displayOsToPay() > 0)
            ->groupBy(static fn ($item) => (int) ($item->order?->client_id ?? 0))
            ->count();

        $this->command?->info(sprintf(
            'OS settlement demo: %d pay + %d receive items. List day %s (Completed %s). မန္တလေး Pay OS=%d · Receive OS=%d',
            $createdPay,
            $createdRecv,
            $listDay,
            $completedDay,
            $payOs,
            $recvOs
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
            ->orWhere('item_name', 'like', 'Pay sample %')
            ->orWhere('item_name', 'like', 'Receive sample %')
            ->orWhere('item_name', 'like', 'Receive demo %')
            ->delete();
    }

    protected function makeOrder(
        ?Order $template,
        User $client,
        User $pickupRider,
        string $pickupAt,
        int $seq
    ): Order {
        $order = $template ? $template->replicate() : new Order();
        $order->client_id = (int) $client->id;
        $order->delivery_man_id = (int) $pickupRider->id;
        $order->country_id = (int) ($template?->country_id ?: $client->country_id ?: (\App\Models\Country::query()->value('id') ?? 1));
        $order->city_id = (int) ($template?->city_id ?: $client->city_id ?: (\App\Models\City::query()->value('id') ?? 1));
        $order->status = 'completed';
        $order->date = Carbon::now('Asia/Yangon')->toDateString();
        $order->pickup_datetime = $pickupAt;
        $order->delivery_datetime = Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s');
        $order->assign_datetime = Carbon::now('Asia/Yangon')->format('Y-m-d H:i:s');
        $order->total_parcel = 2;
        $order->total_weight = $template?->total_weight ?: 1;
        $order->total_distance = $template?->total_distance ?: 0;
        $order->total_amount = 6000;
        $order->fixed_charges = 3000;
        $order->weight_charge = $template?->weight_charge ?: 0;
        $order->distance_charge = $template?->distance_charge ?: 0;
        $order->insurance_charge = $template?->insurance_charge ?: 0;
        $order->vehicle_charge = $template?->vehicle_charge ?: 0;
        $order->extra_charges = $template?->extra_charges;
        $order->payment_collect_from = $template?->payment_collect_from ?: 'on_delivery';
        $order->currency = $template?->currency ?: 'MMK';
        $order->description = self::MARKER;
        $order->is_text_order = 1;
        $order->is_photo_order = 0;
        $order->is_shop_order = 0;
        $order->is_gate_order = 0;
        $order->is_self_order = 0;
        $order->reason = null;
        $order->pickup_error_at = null;
        $order->pickup_error_choice = null;
        $order->pickup_error_choice_at = null;

        $phone = (string) ($client->contact_number ?: '09'.str_pad((string) (400000000 + $seq), 9, '0', STR_PAD_LEFT));
        $order->pickup_point = [
            'name' => (string) $client->name,
            'address' => 'မန္တလေး — OS Settlement',
            'contact_number' => $phone,
            'latitude' => null,
            'longitude' => null,
        ];
        $order->delivery_point = [
            'name' => '',
            'address' => 'Demo delivery',
            'contact_number' => '',
        ];
        $order->milisecond = (string) ((int) (microtime(true) * 1000) + $seq);
        $order->save();

        return $order;
    }

    protected function makeItem(
        ?DispatchOrderItem $src,
        Order $order,
        int $fromBranchId,
        int $toBranchId,
        Carbon $completedAt,
        string $completedDay,
        array $fields
    ): void {
        $item = $src
            ? $src->replicate([
                'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
                'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
                'rider_remit_at',
            ])
            : new DispatchOrderItem();

        $item->order_id = $order->id;
        $item->code = DispatchOrderItem::generateCode();
        $item->delivery_man_id = $order->delivery_man_id;
        $item->from_branch_id = $fromBranchId;
        $item->to_branch_id = $toBranchId;
        $item->remark = self::MARKER;
        $item->item_name = $fields['item_name'];
        $item->item_value = $fields['item_value'];
        $item->deli_amount = $fields['deli_amount'];
        $item->os_paid = 0;
        $item->advance_paid = 0;
        $item->gate_amount = 0;
        $item->gate_os_paid = 0;
        $item->credit_to = $fields['credit_to'];
        $item->pickup_pay_mode = $fields['pickup_pay_mode'];
        $item->cust_get = $fields['cust_get'];
        $item->os_to_pay = $fields['os_to_pay'];
        $item->customer_name = $fields['customer_name'];
        $item->customer_phone = (string) ($order->client?->contact_number ?: '09900000000');
        $item->customer_address = 'Demo address';
        $item->status = 'completed';
        $item->delivery_locked = false;
        $item->delivered_photo_id = 0;
        $item->pending_photo_id = 0;
        $item->photo_id = 0;
        $item->cust_photo_id = 0;
        $item->cust_sign_id = 0;
        $item->admin_completed_at = $completedAt;
        $item->admin_finished_at = null;
        $item->admin_updated_at = $completedAt->copy()->subHour();
        $item->received_date = $completedDay;
        $item->assigned_at = $completedAt->copy()->subHours(2)->format('Y-m-d H:i:s');
        $item->delivered_at = $completedAt->copy()->subMinutes(30);
        $item->rider_remit_at = null;
        $item->rider_remit_date = null;
        $item->weight = 1;
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
