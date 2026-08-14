<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_money_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedBigInteger('branch_id')->default(0);
            $table->unsignedBigInteger('os_user_id');
            $table->double('cash_amount')->default(0);
            $table->double('kpay_amount')->default(0);
            $table->double('freight_amount')->nullable();
            $table->string('remark', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['period_from', 'period_to', 'branch_id', 'os_user_id'],
                'os_money_transfers_period_branch_os_unique'
            );
            $table->index(['period_from', 'period_to', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_money_transfers');
    }
};
