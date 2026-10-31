<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_auto_replies', function (Blueprint $table) {
            $table->id();
            $table->string('label')->comment('Chip text shown to user (MM/EN ok)');
            $table->text('reply_message')->comment('Automation message sent when user taps chip');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_auto_replies');
    }
};
