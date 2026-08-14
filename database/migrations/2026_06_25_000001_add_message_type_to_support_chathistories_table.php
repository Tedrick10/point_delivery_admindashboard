<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chathistories', function (Blueprint $table) {
            if (!Schema::hasColumn('support_chathistories', 'message_type')) {
                $table->string('message_type', 20)->default('text')->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_chathistories', function (Blueprint $table) {
            if (Schema::hasColumn('support_chathistories', 'message_type')) {
                $table->dropColumn('message_type');
            }
        });
    }
};
