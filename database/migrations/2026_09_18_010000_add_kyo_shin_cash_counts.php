<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyo_shin_caps', function (Blueprint $table) {
            if (! Schema::hasColumn('kyo_shin_caps', 'cash_on_hand')) {
                $table->decimal('cash_on_hand', 14, 2)->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('kyo_shin_caps', 'returned_amount')) {
                $table->decimal('returned_amount', 14, 2)->nullable()->after('cash_on_hand');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kyo_shin_caps', function (Blueprint $table) {
            if (Schema::hasColumn('kyo_shin_caps', 'returned_amount')) {
                $table->dropColumn('returned_amount');
            }
            if (Schema::hasColumn('kyo_shin_caps', 'cash_on_hand')) {
                $table->dropColumn('cash_on_hand');
            }
        });
    }
};
