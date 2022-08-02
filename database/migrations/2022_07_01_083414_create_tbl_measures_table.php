<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblMeasuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_measures', function (Blueprint $table) {
            $table->id();
            $table->integer('supply_id')->references('id')->on('tbl_masterlistsupps');
            $table->integer('lead_time');
            $table->integer('order_frequency');
            $table->integer('minimum_order_quantity');
            $table->integer('month');
            $table->integer('year');
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
        Schema::dropIfExists('tbl_measures');
    }
}
