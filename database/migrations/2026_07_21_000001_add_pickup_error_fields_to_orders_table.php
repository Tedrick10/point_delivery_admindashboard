<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'pickup_error_at')) {
                $table->timestamp('pickup_error_at')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('orders', 'pickup_error_choice')) {
                $table->string('pickup_error_choice', 50)->nullable()->after('pickup_error_at');
            }
            if (! Schema::hasColumn('orders', 'pickup_error_choice_at')) {
                $table->timestamp('pickup_error_choice_at')->nullable()->after('pickup_error_choice');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pickup_error_choice_at')) {
                $table->dropColumn('pickup_error_choice_at');
            }
            if (Schema::hasColumn('orders', 'pickup_error_choice')) {
                $table->dropColumn('pickup_error_choice');
            }
            if (Schema::hasColumn('orders', 'pickup_error_at')) {
                $table->dropColumn('pickup_error_at');
            }
        });
    }
};
