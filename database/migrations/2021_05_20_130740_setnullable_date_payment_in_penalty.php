<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetnullableDatePaymentInPenalty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penalty', function (Blueprint $table) {
            $table->date('date_payment')->nullable()->change();
        });
        Schema::table('penalty_no_correction', function (Blueprint $table) {
            $table->date('date_payment')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penalty', function (Blueprint $table) {
            //
        });
    }
}
