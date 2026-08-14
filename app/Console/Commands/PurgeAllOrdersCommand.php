<?php

namespace App\Console\Commands;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PurgeAllOrdersCommand extends Command
{
    protected $signature = 'orders:purge-all {--force : Skip confirmation prompt}';

    protected $description = 'Delete all orders and related records (Admin, User App, Delivery App data source)';

    public function handle(): int
    {
        $orderCount = Order::withTrashed()->count();

        if ($orderCount === 0) {
            $this->info('No orders to delete.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete ALL {$orderCount} order(s) and related data? This cannot be undone.")) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $this->info("Purging {$orderCount} order(s) and related data...");

        DB::transaction(function () {
            Schema::disableForeignKeyConstraints();

            $orderIds = Order::withTrashed()->pluck('id');
            $trackingNos = Order::withTrashed()->pluck('milisecond')->filter()->values();

            Media::query()
                ->where(function ($query) {
                    $query->where('model_type', Order::class)
                        ->orWhere('model_type', DispatchOrderItem::class);
                })
                ->delete();

            $relatedTables = [
                'order_chat_messages',
                'order_bids',
                'order_histories',
                'order_vehicle_histories',
                'payments',
                'profofpictures',
                'ratings',
                'rest_api_histories',
                'wallet_histories',
            ];

            foreach ($relatedTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                if (Schema::hasColumn($table, 'order_id') && $orderIds->isNotEmpty()) {
                    DB::table($table)->whereIn('order_id', $orderIds)->delete();
                } else {
                    DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('dispatch_order_items')) {
                DB::table('dispatch_order_items')->delete();
            }

            if (Schema::hasTable('customer_supports') && Schema::hasColumn('customer_supports', 'order_id')) {
                DB::table('customer_supports')->whereIn('order_id', $orderIds)->delete();
            }

            if (Schema::hasTable('reschedules') && Schema::hasColumn('reschedules', 'order_id')) {
                DB::table('reschedules')->whereIn('order_id', $orderIds)->delete();
            }

            if (Schema::hasTable('claims') && $trackingNos->isNotEmpty()) {
                DB::table('claims')->whereIn('traking_no', $trackingNos)->delete();
            }

            if (Schema::hasTable('claims_histories')) {
                DB::table('claims_histories')->delete();
            }

            Order::withTrashed()->forceDelete();

            Schema::enableForeignKeyConstraints();
        });

        $remaining = Order::withTrashed()->count();
        $itemsRemaining = Schema::hasTable('dispatch_order_items')
            ? DB::table('dispatch_order_items')->count()
            : 0;

        $this->info("Done. Orders remaining: {$remaining}, dispatch items remaining: {$itemsRemaining}");

        return self::SUCCESS;
    }
}
