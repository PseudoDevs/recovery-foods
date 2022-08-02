<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblRequestprodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_requestprods', function (Blueprint $table) {
            $table->id();
            $table->text('ref');
            $table->integer('product_name')->references('id')->on('tbl_masterlistprod');
            $table->float('quantity');
            $table->datetime('request_date');
            $table->integer('branch')->references('id')->on('tbl_branches');
            $table->integer('user')->references('id')->on('users');
            $table->integer('status')->default(1);
            $table->integer('deleted')->default(0);
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
        Schema::dropIfExists('tbl_requestprods');
    }
}
