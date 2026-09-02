<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_remits')) {
            return;
        }

        Schema::table('rider_remits', function (Blueprint $table) {
            if (! Schema::hasColumn('rider_remits', 'is_off')) {
                $table->boolean('is_off')->default(false)->after('kpay_amount');
            }
            if (! Schema::hasColumn('rider_remits', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('is_off');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_remits')) {
            return;
        }

        Schema::table('rider_remits', function (Blueprint $table) {
            if (Schema::hasColumn('rider_remits', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('rider_remits', 'is_off')) {
                $table->dropColumn('is_off');
            }
        });
    }
};
