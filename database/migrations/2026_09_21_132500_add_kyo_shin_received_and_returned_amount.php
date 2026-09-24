<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kyo_shin_items') && ! Schema::hasColumn('kyo_shin_items', 'received_at')) {
            Schema::table('kyo_shin_items', function (Blueprint $table) {
                $table->timestamp('received_at')->nullable()->after('checked_by');
                $table->foreignId('received_by')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('kyo_shin_daily_ledgers') && ! Schema::hasColumn('kyo_shin_daily_ledgers', 'returned_amount')) {
            Schema::table('kyo_shin_daily_ledgers', function (Blueprint $table) {
                $table->decimal('returned_amount', 14, 2)->default(0)->after('cash_held');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kyo_shin_items') && Schema::hasColumn('kyo_shin_items', 'received_at')) {
            Schema::table('kyo_shin_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('received_by');
                $table->dropColumn('received_at');
            });
        }

        if (Schema::hasTable('kyo_shin_daily_ledgers') && Schema::hasColumn('kyo_shin_daily_ledgers', 'returned_amount')) {
            Schema::table('kyo_shin_daily_ledgers', function (Blueprint $table) {
                $table->dropColumn('returned_amount');
            });
        }
    }
};
