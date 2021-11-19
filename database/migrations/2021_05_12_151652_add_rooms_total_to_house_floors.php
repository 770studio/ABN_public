<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoomsTotalToHouseFloors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('house_floors', function (Blueprint $table) {
            $table->unsignedSmallInteger('rooms_total');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('house_floors', function (Blueprint $table) {
            $table->dropColumn('rooms_total');
        });
    }
}
