<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('point_wallets')) {
            Schema::create('point_wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->decimal('balance', 14, 2)->default(0);
                $table->timestamps();

                $table->index('balance');
            });
        }

        if (! Schema::hasTable('point_histories')) {
            Schema::create('point_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('type', 16); // credit|debit
                $table->string('transaction_type', 64)->index();
                // admin_credit, order_payment, order_payment_refund,
                // salary_request, salary_approved, correction
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->string('reference_type', 64)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->text('description')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('point_order_payments')) {
            Schema::create('point_order_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->decimal('points', 14, 2)->default(0);
                $table->string('status', 32)->default('pending_admin')->index();
                // pending_admin → pending_super_admin → confirmed | rejected | cancelled
                $table->unsignedBigInteger('admin_approved_by')->nullable()->index();
                $table->timestamp('admin_approved_at')->nullable();
                $table->unsignedBigInteger('super_admin_confirmed_by')->nullable()->index();
                $table->timestamp('super_admin_confirmed_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable()->index();
                $table->timestamp('rejected_at')->nullable();
                $table->text('remark')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('point_salary_requests')) {
            Schema::create('point_salary_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('hr_staff_id')->nullable()->index();
                $table->decimal('points', 14, 2)->default(0);
                $table->decimal('amount', 14, 2)->default(0); // money value after conversion
                $table->string('status', 32)->default('pending')->index();
                // pending → approved | rejected | cancelled
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable()->index();
                $table->timestamp('rejected_at')->nullable();
                $table->unsignedBigInteger('salary_row_id')->nullable()->index();
                $table->string('salary_period', 32)->nullable();
                $table->text('remark')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('point_salary_requests');
        Schema::dropIfExists('point_order_payments');
        Schema::dropIfExists('point_histories');
        Schema::dropIfExists('point_wallets');
    }
};
