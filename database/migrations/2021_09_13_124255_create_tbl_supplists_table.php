<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblSupplistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_supplists', function (Blueprint $table) {
            $table->id();
            $table->integer('status')->default(1);
            $table->string('supplier_name');
            $table->string('description');
            $table->string('phone_number');
            $table->string('contact_person');
            $table->string('address');
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
        Schema::dropIfExists('tbl_supplists');
    }
}
