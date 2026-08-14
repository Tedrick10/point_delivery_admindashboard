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
        $orders = Order::where('status', 'create')
            ->whereRaw("STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(pickup_point, '$.end_time')), '%Y-%m-%d %H:%i') < ?", [$now])
            ->get();

        foreach ($orders as $order) {
            $order->status = 'cancelled';
            $order->save();
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

        foreach ($pickupErrors as $order) {
            $order->status = 'cancelled';
            $order->reason = $order->reason
                ? ($order->reason . ' | Auto cancelled after 2 days (pickup error)')
                : 'Auto cancelled after 2 days (pickup error)';
            $order->save();

            saveOrderHistory([
                'history_type' => 'cancelled',
                'order_id' => $order->id,
                'order' => $order,
            ]);
        }

        $this->info('Cancelled create: ' . $orders->count() . ', pickup_error: ' . $pickupErrors->count());

        return Command::SUCCESS;
    }
}
