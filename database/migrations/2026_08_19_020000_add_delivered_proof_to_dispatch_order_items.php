<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'delivered_photo_id')) {
                $table->unsignedBigInteger('delivered_photo_id')->default(0)->after('pending_photo_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'delivered_type')) {
                $table->string('delivered_type', 20)->nullable()->after('delivered_photo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'delivered_type')) {
                $table->dropColumn('delivered_type');
            }
            if (Schema::hasColumn('dispatch_order_items', 'delivered_photo_id')) {
                $table->dropColumn('delivered_photo_id');
            }
        });
    }
};
