<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-image proof stores a JSON path list; varchar(255) truncates after ~3 slips.
     */
    public function up(): void
    {
        Schema::table('os_settlement_drafts', function (Blueprint $table) {
            $table->text('kpay_slip_path')->nullable()->change();
        });

        Schema::table('os_settlement_batches', function (Blueprint $table) {
            $table->text('kpay_slip_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('os_settlement_drafts', function (Blueprint $table) {
            $table->string('kpay_slip_path')->nullable()->change();
        });

        Schema::table('os_settlement_batches', function (Blueprint $table) {
            $table->string('kpay_slip_path')->nullable()->change();
        });
    }
};
