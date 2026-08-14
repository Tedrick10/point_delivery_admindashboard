<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_man_id')->after('client_id')->nullable();
            $table->tinyInteger('is_settled')->default(0)->comment('0 = false, 1 = true')->after('updated_at');
            $table->integer('payout_report_id')->nullable()->after('is_settled');
            $table->foreign('delivery_man_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //  Schema::table('payments', function (Blueprint $table) {
        //     $table->dropColumn([  'delivery_man_id', 'is_settled','payout_report_id' ]);
        // });
    }
};
