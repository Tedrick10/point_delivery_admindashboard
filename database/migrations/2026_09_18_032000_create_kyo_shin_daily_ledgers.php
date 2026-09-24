<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kyo_shin_daily_ledgers')) {
            return;
        }

        Schema::create('kyo_shin_daily_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 32);
            $table->date('ledger_date');
            $table->decimal('sa_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('cash_held', 14, 2)->default(0);
            $table->decimal('os_receivable', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['scope_key', 'ledger_date']);
            $table->index('ledger_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyo_shin_daily_ledgers');
    }
};
