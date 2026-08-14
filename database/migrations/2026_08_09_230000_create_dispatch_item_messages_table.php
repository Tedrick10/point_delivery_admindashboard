<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_item_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispatch_order_item_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->string('sender_type', 40)->nullable(); // admin | client | delivery_man
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('dispatch_order_item_id')
                ->references('id')
                ->on('dispatch_order_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_item_messages');
    }
};
