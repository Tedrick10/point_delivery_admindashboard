<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'approval_rejection_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('approval_rejection_reason')->nullable()->after('approval_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'approval_rejection_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('approval_rejection_reason');
            });
        }
    }
};
