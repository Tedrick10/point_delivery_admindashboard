<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'pickup_vehicle_type')) {
                $table->string('pickup_vehicle_type', 20)->default('motorcycle')->after('vehicle_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pickup_vehicle_type')) {
                $table->dropColumn('pickup_vehicle_type');
            }
        });
    }
};
