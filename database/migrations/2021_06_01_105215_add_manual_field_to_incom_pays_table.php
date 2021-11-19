<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManualFieldToIncomPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('IncomPays', function (Blueprint $table) {
            $table->tinyInteger('manual_payment')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('IncomPays', function (Blueprint $table) {
           $table->dropColumn('manual_payment');
        });
    }
}
