<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblSuppliesinventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_suppliesinventories', function (Blueprint $table) {
            $table->id();
            $table->integer('ref')->reference('id')->on("tbl_outgoingsupp");
            $table->integer('category')->references('id')->on('tbl_suppcat');
            $table->integer('supply_name')->references('id')->on('tbl_masterlistsupp');
            $table->float('quantity');
            $table->datetime('outgoing_date');
            $table->integer('branch')->references('id')->on('tbl_branches');
            $table->integer('user')->references('id')->on('users');
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
        Schema::dropIfExists('tbl_suppliesinventories');
    }
}
