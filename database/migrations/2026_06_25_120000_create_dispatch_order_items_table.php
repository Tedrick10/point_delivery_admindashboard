<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('photo_id')->default(0);
            $table->date('received_date')->nullable();
            $table->string('code')->nullable();
            $table->string('status')->default('collected');
            $table->unsignedBigInteger('from_branch_id')->nullable();
            $table->unsignedBigInteger('to_branch_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('township')->nullable();
            $table->text('item_name')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('weight', 12, 2)->default(0);
            $table->decimal('advance_paid', 12, 2)->default(0);
            $table->decimal('os_paid', 12, 2)->default(0);
            $table->decimal('item_value', 12, 2)->default(0);
            $table->decimal('deli_amount', 12, 2)->default(0);
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('credit_to')->default('customer');
            $table->decimal('cust_get', 12, 2)->default(0);
            $table->decimal('os_to_pay', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_order_items');
    }
};
