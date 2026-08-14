<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'cust_photo_id')) {
                $table->unsignedBigInteger('cust_photo_id')->nullable()->after('pending_photo_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'cust_sign_id')) {
                $table->unsignedBigInteger('cust_sign_id')->nullable()->after('cust_photo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'cust_sign_id')) {
                $table->dropColumn('cust_sign_id');
            }
            if (Schema::hasColumn('dispatch_order_items', 'cust_photo_id')) {
                $table->dropColumn('cust_photo_id');
            }
        });
    }
};
