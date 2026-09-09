<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
                $table->timestamp('mdy_inbox_at')->nullable()->after('hub_accepted_at');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'mdy_accepted_at')) {
                $table->timestamp('mdy_accepted_at')->nullable()->after('mdy_inbox_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'mdy_accepted_at')) {
                $table->dropColumn('mdy_accepted_at');
            }
            if (Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
                $table->dropColumn('mdy_inbox_at');
            }
        });
    }
};
