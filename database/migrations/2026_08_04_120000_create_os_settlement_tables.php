<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_settlement_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('os_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('kpay_name')->nullable();
            $table->string('kpay_no')->nullable();
            $table->string('kpay_slip_path')->nullable();
            $table->string('slip_table_path')->nullable();
            $table->string('slip_image_path')->nullable();
            $table->string('slip_pdf_path')->nullable();
            $table->string('kpay_pdf_path')->nullable();
            $table->json('item_ids')->nullable();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['os_user_id', 'from_date', 'to_date']);
        });

        Schema::create('os_settlement_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('os_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('kpay_slip_path')->nullable();
            $table->timestamps();

            $table->unique(['os_user_id', 'from_date', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_settlement_drafts');
        Schema::dropIfExists('os_settlement_batches');
    }
};
