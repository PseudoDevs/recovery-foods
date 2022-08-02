<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblMasterlistsuppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_masterlistsupps', function (Blueprint $table) {
            $table->id();
            $table->integer('category')->references('id')->on('tbl_suppcat');
            $table->string('supply_name');
            $table->string('description')->nullable();
            $table->string('unit');
            $table->float('net_price');
            $table->float('vat');
            $table->integer('vatable');
            $table->integer('supplier')->references('id')->on('tbl_supplist');
            $table->datetime('exp_date')->nullable();
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
        Schema::dropIfExists('tbl_masterlistsupps');
    }
}
