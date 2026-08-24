<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_summaries')) {
            return;
        }

        Schema::create('expense_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date')->unique();
            $table->foreignId('expense_card_id')->nullable()->constrained('expense_cards')->nullOnDelete();
            $table->decimal('income', 14, 2)->default(0);
            $table->decimal('expense', 14, 2)->default(0);
            $table->decimal('ako_given', 14, 2)->default(0);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('summary_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_summaries');
    }
};
