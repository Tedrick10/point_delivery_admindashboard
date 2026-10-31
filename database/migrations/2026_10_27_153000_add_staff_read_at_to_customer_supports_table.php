<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_supports', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_supports', 'staff_read_at')) {
                $table->timestamp('staff_read_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_supports', function (Blueprint $table) {
            if (Schema::hasColumn('customer_supports', 'staff_read_at')) {
                $table->dropColumn('staff_read_at');
            }
        });
    }
};
