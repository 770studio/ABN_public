<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetuniqueindexonPenalty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penalty', function (Blueprint $table) {
            $table->unique(['lead_id', 'schedule_id', 'overdue_sum']);
        });
        Schema::table('penalty_no_correction', function (Blueprint $table) {
            $table->unique(['lead_id', 'schedule_id', 'overdue_sum']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
