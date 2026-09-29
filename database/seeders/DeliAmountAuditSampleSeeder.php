<?php

namespace Database\Seeders;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\DispatchOrderAuditService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo DeliAmount change history for Order List → DeliAmount Audit Log (today).
 */
class DeliAmountAuditSampleSeeder extends Seeder
{
    private const MARKER = 'DeliAmount Audit sample';

    public function run(): void
    {
        $audit = app(DispatchOrderAuditService::class);
        $admin = User::query()
            ->whereIn('user_type', ['admin', 'super_admin'])
            ->orderBy('id')
            ->first();

        $items = DispatchOrderItem::query()
            ->with('order')
            ->where('remark', 'Order List sample')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        if ($items->isEmpty()) {
            $items = DispatchOrderItem::query()
                ->with('order')
                ->whereNotNull('order_id')
                ->orderByDesc('id')
                ->limit(8)
                ->get();
        }

        if ($items->isEmpty()) {
            $this->command?->warn('No dispatch items found for DeliAmount audit demo.');

            return;
        }

        // Clear previous demo audit rows.
        OrderHistory::query()
            ->where('history_type', DispatchOrderAuditService::TYPE_DELI_AMOUNT)
            ->where(function ($q) {
                $q->where('history_data', 'like', '%"demo":true%')
                    ->orWhere('history_data', 'like', '%DeliAmount Audit sample%');
            })
            ->forceDelete();

        $scenarios = [
            [2500, 3000],
            [3000, 3500],
            [4000, 3200],
            [3500, 4500],
            [2800, 3000],
            [5000, 4200],
        ];

        $created = 0;
        $baseTime = Carbon::now('Asia/Yangon')->setTime(9, 15, 0);

        foreach ($items->take(count($scenarios)) as $i => $item) {
            /** @var DispatchOrderItem $item */
            $order = $item->order;
            if (! $order instanceof Order) {
                continue;
            }

            [$from, $to] = $scenarios[$i];
            $at = $baseTime->copy()->addMinutes($i * 37)->utc();

            // Simulate a real change so Audit Log accepts it (from > 0).
            $before = [
                'deli_amount' => $from,
                'weight' => 1,
            ];
            $after = [
                'deli_amount' => $to,
                'weight' => 1,
            ];

            auth()->loginUsingId($admin?->id ?? 1);
            $audit->maybeLogDeliAmountChange($order, $item, $before, $after, $admin, 'admin');

            $history = OrderHistory::query()
                ->where('order_id', $order->id)
                ->where('history_type', DispatchOrderAuditService::TYPE_DELI_AMOUNT)
                ->orderByDesc('id')
                ->first();

            if ($history) {
                $data = is_array($history->history_data) ? $history->history_data : (json_decode((string) $history->history_data, true) ?: []);
                $data['demo'] = true;
                $data['demo_marker'] = self::MARKER;
                $history->history_data = $data;
                $history->datetime = $at;
                $history->created_at = $at;
                $history->updated_at = $at;
                $history->save();
                $created++;
            }
        }

        if (auth()->check()) {
            auth()->logout();
        }

        $this->command?->info(sprintf('DeliAmount Audit demo: %d entries for today.', $created));
    }
}
