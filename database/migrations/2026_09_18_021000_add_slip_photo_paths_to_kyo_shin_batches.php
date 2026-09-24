<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kyo_shin_batches') && ! Schema::hasColumn('kyo_shin_batches', 'slip_photo_paths')) {
            Schema::table('kyo_shin_batches', function (Blueprint $table) {
                $table->json('slip_photo_paths')->nullable()->after('slip_photo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kyo_shin_batches') && Schema::hasColumn('kyo_shin_batches', 'slip_photo_paths')) {
            Schema::table('kyo_shin_batches', function (Blueprint $table) {
                $table->dropColumn('slip_photo_paths');
            });
        }
    }
};
