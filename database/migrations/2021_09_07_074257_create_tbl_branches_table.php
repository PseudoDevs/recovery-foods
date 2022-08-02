<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_branches', function (Blueprint $table) {
            $table->id();
            $table->integer('status')->default(1); //0 = inactive, else active
            $table->string('branch_name');
            $table->string('location');
            $table->string('phone_number');
            $table->integer('type')->default(0);
            $table->string('email_add');
            $table->string('branch_image')->nullable();
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
        Schema::dropIfExists('tbl_branches');
    }
}
