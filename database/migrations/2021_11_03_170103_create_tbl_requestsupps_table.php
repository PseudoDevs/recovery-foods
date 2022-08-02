<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblRequestsuppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_requestsupps', function (Blueprint $table) {
            $table->id();
            $table->text('ref');
            $table->integer('supply_name')->references('id')->on('tbl_masterlistsupp');
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
        Schema::dropIfExists('tbl_requestsupps');
    }
}
