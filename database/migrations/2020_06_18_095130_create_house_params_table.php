<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHouseParamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('house_params', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('projectId')->nullable();
            $table->string('projectName')->nullable();
            $table->string('title')->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('facing')->nullable();
            $table->string('material')->nullable();
            $table->string('buildingState')->nullable();
            $table->integer('developmentStartQuarterYear')->nullable();
            $table->integer('developmentStartQuarterQuarter')->nullable();
            $table->integer('developmentEndQuarterYear')->nullable();
            $table->integer('developmentEndQuarterQuarter')->nullable();
            $table->string('salesStartMonth')->nullable();
            $table->string('salesStartYear')->nullable();
            $table->string('salesEndMonth')->nullable();
            $table->string('salesEndYear')->nullable();
            $table->integer('pb_id')->nullable();
            $table->string('type')->nullable();
            $table->string('image')->nullable();
            $table->string('minFloor')->nullable();
            $table->string('maxFloor')->nullable();
            $table->integer('minPrice')->nullable();
            $table->decimal('minPriceArea')->nullable();
            $table->integer('propertyCount')->nullable();
            $table->string('countFilteredProperty')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('address_full')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('house_params');
    }
}
