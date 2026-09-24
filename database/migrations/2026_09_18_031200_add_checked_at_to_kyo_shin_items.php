<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kyo_shin_items') || Schema::hasColumn('kyo_shin_items', 'checked_at')) {
            return;
        }

        Schema::table('kyo_shin_items', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('finished_by');
            $table->foreignId('checked_by')->nullable()->after('checked_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kyo_shin_items') || ! Schema::hasColumn('kyo_shin_items', 'checked_at')) {
            return;
        }

        Schema::table('kyo_shin_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_by');
            $table->dropColumn('checked_at');
        });
    }
};
