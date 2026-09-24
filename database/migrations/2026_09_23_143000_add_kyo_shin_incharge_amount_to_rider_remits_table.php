<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_remits')) {
            return;
        }

        Schema::table('rider_remits', function (Blueprint $table) {
            if (! Schema::hasColumn('rider_remits', 'kyo_shin_incharge_amount')) {
                $table->double('kyo_shin_incharge_amount')->default(0)->after('kpay_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_remits')) {
            return;
        }

        Schema::table('rider_remits', function (Blueprint $table) {
            if (Schema::hasColumn('rider_remits', 'kyo_shin_incharge_amount')) {
                $table->dropColumn('kyo_shin_incharge_amount');
            }
        });
    }
};
