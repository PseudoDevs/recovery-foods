<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblOutgoingsuppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_outgoingsupps', function (Blueprint $table) {
            $table->id();
            $table->integer('category')->references('id')->on('tbl_suppcat');
            $table->integer('supply_name')->references('id')->on('tbl_masterlistsupp');
            $table->float('quantity');
            $table->float('amount');
            $table->integer('requesting_branch')->references('id')->on('tbl_branches');
            $table->datetime('outgoing_date');
            // $table->text('request_ref');
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
        Schema::dropIfExists('tbl_outgoingsupps');
    }
}
