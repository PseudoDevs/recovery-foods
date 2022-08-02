<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblIncomingsuppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_incomingsupps', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier')->references('id')->on('tbl_supplist');
            $table->integer('category')->references('id')->on('tbl_suppcat');
            $table->integer('supply_name')->references('id')->on('tbl_masterlistsupp');
            $table->float('quantity');
            $table->float('amount');
            $table->datetime('incoming_date');
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
        Schema::dropIfExists('tbl_incomingsupps');
    }
}
