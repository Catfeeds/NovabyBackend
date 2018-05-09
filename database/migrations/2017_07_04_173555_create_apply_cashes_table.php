<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplyCashesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apply_cashes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('u_id');
            $table->integer('amount');
            $table->string('paypal_email');
            $table->string('paypal_name');
            $table->integer('status');
            $table->string('transaction_no');
            $table->timestamp('apply_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('apply_cashes');
    }
}
