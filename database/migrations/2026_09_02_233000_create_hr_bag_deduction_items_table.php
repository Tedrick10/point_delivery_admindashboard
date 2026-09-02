<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_bag_deduction_items', function (Blueprint $table) {
            $table->id();
            $table->date('period_month');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('staff_code', 50)->nullable();
            $table->date('item_date')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('hr_staff')->nullOnDelete();
            $table->index(['period_month', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_bag_deduction_items');
    }
};
