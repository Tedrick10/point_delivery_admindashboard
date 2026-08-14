<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_man_id')->nullable()->after('status');
            $table->timestamp('assigned_at')->nullable()->after('delivery_man_id');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $table->dropColumn(['delivery_man_id', 'assigned_at']);
        });
    }
};
