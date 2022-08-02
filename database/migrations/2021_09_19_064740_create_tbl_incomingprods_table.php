<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblIncomingprodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_incomingprods', function (Blueprint $table) {
            $table->id();
            $table->integer('category')->references('id')->on('tbl_prodcat');
            $table->integer('sub_category')->references('id')->on('tbl_prodsubcat');
            $table->integer('product_name')->references('id')->on('tbl_masterlistprod');
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
        Schema::dropIfExists('tbl_incomingprods');
    }
}
