<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_remit_logs')) {
            return;
        }

        Schema::create('rider_remit_logs', function (Blueprint $table) {
            $table->id();
            $table->date('remit_date')->index();
            $table->unsignedBigInteger('branch_id')->default(0)->index();
            $table->unsignedBigInteger('delivery_man_id')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('rider_name')->nullable();
            $table->string('action', 32)->index();
            $table->string('field')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['remit_date', 'branch_id', 'id'], 'rider_remit_logs_day_branch_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_remit_logs');
    }
};
