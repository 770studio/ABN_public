<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeinIndexToPenaltyCorrections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penalty_corrections', function (Blueprint $table) {
            $table->foreign('penalty_id')
                ->references('id')->on('penalty')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penalty_corrections', function (Blueprint $table) {
            $table->dropForeign('penalty_corrections_penalty_id_foreign');

        });
    }
}
