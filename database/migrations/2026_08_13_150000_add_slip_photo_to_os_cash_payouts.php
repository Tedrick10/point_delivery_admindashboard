<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os_cash_payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('os_cash_payouts', 'slip_photo_path')) {
                $table->string('slip_photo_path')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('os_cash_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('os_cash_payouts', 'slip_photo_path')) {
                $table->dropColumn('slip_photo_path');
            }
        });
    }
};
