<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblPosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_pos', function (Blueprint $table) {
            $table->id();
            $table->integer('category')->references('id')->on('tbl_prodcat');
            $table->integer('sub_category')->references('id')->on('tbl_prodsubcat');
            $table->integer('product_name')->references('id')->on('tbl_masterlistprod');
            $table->float('quantity');
            $table->float('sub_total');
            $table->float('sub_total_discounted');
            $table->float('vat');
            $table->float('payment');
            $table->float('discount');
            $table->float('change');
            $table->string('mode');
            $table->string('reference_no');
            $table->integer('branch')->references('id')->on('tbl_branches');
            $table->integer('cashier')->references('id')->on('users');
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
        Schema::dropIfExists('tbl_pos');
    }
}
