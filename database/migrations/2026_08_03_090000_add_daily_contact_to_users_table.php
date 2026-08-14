<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'daily_contact_number')) {
                $table->string('daily_contact_number', 255)->nullable()->after('contact_number');
            }
            if (! Schema::hasColumn('users', 'daily_contact_date')) {
                $table->date('daily_contact_date')->nullable()->after('daily_contact_number');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'daily_contact_date')) {
                $table->dropColumn('daily_contact_date');
            }
            if (Schema::hasColumn('users', 'daily_contact_number')) {
                $table->dropColumn('daily_contact_number');
            }
        });
    }
};
