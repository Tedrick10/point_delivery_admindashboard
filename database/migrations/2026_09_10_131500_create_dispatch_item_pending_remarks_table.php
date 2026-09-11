<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dispatch_item_pending_remarks')) {
            Schema::create('dispatch_item_pending_remarks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dispatch_order_item_id')->index();
                $table->unsignedBigInteger('delivery_man_id')->nullable()->index();
                $table->text('remark')->nullable();
                $table->unsignedBigInteger('photo_id')->default(0);
                $table->timestamp('pending_at')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        $items = DB::table('dispatch_order_items')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('pending_photo_id', '>', 0)
                    ->orWhere(function ($inner) {
                        $inner->where('status', 'pending')
                            ->whereNotNull('remark')
                            ->where('remark', '!=', '');
                    });
            })
            ->get(['id', 'delivery_man_id', 'remark', 'pending_photo_id', 'admin_updated_at', 'updated_at']);

        $now = now();
        foreach ($items as $item) {
            $exists = DB::table('dispatch_item_pending_remarks')
                ->where('dispatch_order_item_id', $item->id)
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('dispatch_item_pending_remarks')->insert([
                'dispatch_order_item_id' => $item->id,
                'delivery_man_id' => $item->delivery_man_id ?: null,
                'remark' => $item->remark,
                'photo_id' => (int) ($item->pending_photo_id ?? 0),
                'pending_at' => $item->admin_updated_at ?: ($item->updated_at ?: $now),
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_item_pending_remarks');
    }
};
