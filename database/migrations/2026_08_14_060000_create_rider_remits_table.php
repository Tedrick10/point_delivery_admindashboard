<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_remits')) {
            return;
        }

        Schema::create('rider_remits', function (Blueprint $table) {
            $table->id();
            $table->date('remit_date')->index();
            $table->unsignedBigInteger('branch_id')->default(0)->index();
            $table->unsignedBigInteger('delivery_man_id')->index();
            $table->double('due_amount')->default(0);
            $table->double('prepaid_amount')->default(0);
            $table->double('fuel_amount')->default(0);
            $table->double('fee_amount')->default(0);
            $table->json('denominations')->nullable();
            $table->double('kpay_amount')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['remit_date', 'branch_id', 'delivery_man_id'], 'rider_remits_day_branch_rider_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_remits');
    }
};
