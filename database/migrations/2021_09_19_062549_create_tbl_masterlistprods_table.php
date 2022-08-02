<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblMasterlistprodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_masterlistprods', function (Blueprint $table) {
            $table->id();
            $table->integer('category')->references('id')->on('tbl_prodcat');
            $table->integer('sub_category')->references('id')->on('tbl_prodsubcat');
            $table->string('product_name');
            $table->string('description')->nullable();
            $table->float('price');
            $table->float('vat');
            $table->integer('vatable');
            $table->float('critical_limit');
            $table->datetime('exp_date')->nullable();
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('tbl_masterlistprods');
    }
}
