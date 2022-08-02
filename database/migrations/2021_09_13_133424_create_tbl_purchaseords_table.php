<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblPurchaseordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_purchaseords', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_number');
            $table->integer('supplier_name')->references('id')->on('tbl_supplist');
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
        Schema::dropIfExists('tbl_purchaseords');
    }
}
