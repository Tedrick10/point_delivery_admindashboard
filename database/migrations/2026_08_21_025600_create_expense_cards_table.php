<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_cards', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date')->unique();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('expense_date');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_card_id');
            $table->string('subject', 255);
            $table->decimal('amount', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('expense_card_id')
                ->references('id')
                ->on('expense_cards')
                ->cascadeOnDelete();
            $table->index(['expense_card_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('expense_cards');
    }
};
