<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os_settlement_batches', function (Blueprint $table) {
            $table->string('delivery_format', 16)->default('table')->after('amount');
            $table->string('combined_pdf_path')->nullable()->after('kpay_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('os_settlement_batches', function (Blueprint $table) {
            $table->dropColumn(['delivery_format', 'combined_pdf_path']);
        });
    }
};
