<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PenaltyAddScheduleIdField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
/*        Schema::table('penalty', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_id');
        });*/

        Schema::table('penalty_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penalty_daily', function (Blueprint $table) {
            $table->dropColumn('schedule_id');
        });
    }
}
