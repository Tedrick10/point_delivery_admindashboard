<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'rider_remit_date')) {
                $table->date('rider_remit_date')->nullable()->after('rider_remit_at');
                $table->index('rider_remit_date', 'dispatch_order_items_rider_remit_date_idx');
            }
        });

        // Backfill from delivered_at / updated_at using Yangon 9AM business day (no Completed lock).
        DB::table('dispatch_order_items')
            ->where('status', 'completed')
            ->whereNull('rider_remit_date')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $raw = $row->delivered_at ?: $row->updated_at;
                    if (! $raw) {
                        continue;
                    }
                    $at = \Carbon\Carbon::parse($raw)->timezone('Asia/Yangon');
                    if ($at->lte($at->copy()->startOfDay()->setTime(9, 0, 0))) {
                        $day = $at->copy()->subDay()->toDateString();
                    } else {
                        $day = $at->toDateString();
                    }
                    DB::table('dispatch_order_items')
                        ->where('id', $row->id)
                        ->update(['rider_remit_date' => $day]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'rider_remit_date')) {
                $table->dropIndex('dispatch_order_items_rider_remit_date_idx');
                $table->dropColumn('rider_remit_date');
            }
        });
    }
};
