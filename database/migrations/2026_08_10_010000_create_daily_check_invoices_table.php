<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_check_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 32)->unique();
            $table->string('party_type', 16); // os | rider
            $table->unsignedBigInteger('party_user_id')->default(0);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('received_date');
            $table->date('remitted_date')->nullable();
            $table->string('remitted_photo_path')->nullable();
            $table->foreignId('remitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('item_ids')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['party_type', 'party_user_id', 'received_date'],
                'daily_check_invoices_party_date_unique'
            );
            $table->index(['received_date', 'party_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_check_invoices');
    }
};
