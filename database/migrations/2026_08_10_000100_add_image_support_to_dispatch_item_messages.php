<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_item_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_item_messages', 'message_type')) {
                $table->string('message_type', 20)->default('text')->after('sender_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_item_messages', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_item_messages', 'message_type')) {
                $table->dropColumn('message_type');
            }
        });
    }
};
