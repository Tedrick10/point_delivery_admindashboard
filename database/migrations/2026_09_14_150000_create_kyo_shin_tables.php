<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyo_shin_caps', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 32)->unique();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kyo_shin_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_order_item_id')->unique()->constrained('dispatch_order_items')->cascadeOnDelete();
            $table->string('scope_key', 32);
            $table->unsignedBigInteger('os_user_id')->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 24);
            $table->timestamp('advanced_paid_at')->nullable();
            $table->foreignId('advanced_paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope_key', 'status']);
            $table->index(['os_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyo_shin_items');
        Schema::dropIfExists('kyo_shin_caps');
    }
};
