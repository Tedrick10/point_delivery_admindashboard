<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Carbon\Carbon;

class OrderAutoCancelCommand extends Command
{
    protected $signature = 'order:autocancel';

    protected $description = 'Auto-cancel expired create orders and pickup_error orders after 2 days';

    public function handle()
    {
        $now = Carbon::now()->addMinutes(30);

        // Only true draft/create slots — never cancel once pickup/dispatch has progressed.
        $orders = Order::query()
            ->where('status', 'create')
            ->whereRaw(
                "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(pickup_point, '$.end_time')), '%Y-%m-%d %H:%i') < ?",
                [$now]
            )
            ->whereDoesntHave('dispatchItems', function ($q) {
                $q->whereIn('status', [
                    'assigned',
                    'courier_assigned',
                    'courier_departed',
                    'completed',
                    'pending',
                    'accepted',
                    'arrived',
                ]);
            })
            ->get();

        $createCount = 0;
        foreach ($orders as $order) {
            $previous = (string) ($order->status ?? 'create');
            $order->status = 'cancelled';
            $order->reason = 'Auto cancelled — create pickup window expired';
            $order->delivery_man_id = null;
            $order->assign_datetime = null;
            $order->save();

            saveOrderHistory([
                'history_type' => 'cancelled',
                'order_id' => $order->id,
                'order' => $order,
                'auto_cancel' => true,
                'actor_name' => 'System (Auto Cancel)',
                'actor_type' => 'system',
                'previous_status' => $previous,
            ]);
            $createCount++;
        }

        $pickupErrors = Order::where('status', 'pickup_error')
            ->where(function ($query) {
                $query->where('pickup_error_at', '<', Carbon::now()->subDays(2))
                    ->orWhere(function ($inner) {
                        $inner->whereNull('pickup_error_at')
                            ->where('updated_at', '<', Carbon::now()->subDays(2));
                    });
            })
            ->get();

        $pickupCount = 0;
        foreach ($pickupErrors as $order) {
            $previous = (string) ($order->status ?? 'pickup_error');
            $order->status = 'cancelled';
            $order->reason = $order->reason
                ? ($order->reason . ' | Auto cancelled after 2 days (pickup error)')
                : 'Auto cancelled after 2 days (pickup error)';
            $order->delivery_man_id = null;
            $order->assign_datetime = null;
            $order->save();

            saveOrderHistory([
                'history_type' => 'cancelled',
                'order_id' => $order->id,
                'order' => $order,
                'auto_cancel' => true,
                'actor_name' => 'System (Auto Cancel)',
                'actor_type' => 'system',
                'previous_status' => $previous,
            ]);
            $pickupCount++;
        }

        $this->info('Cancelled create: ' . $createCount . ', pickup_error: ' . $pickupCount);

        return Command::SUCCESS;
    }
}
