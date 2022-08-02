<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProdsubcatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_prodsubcats', function (Blueprint $table) {
            $table->id();
            $table->string('prod_sub_cat_name');
            $table->integer('status')->default(1); //0 = inactive, else active
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
        Schema::dropIfExists('tbl_prodsubcats');
    }
}
