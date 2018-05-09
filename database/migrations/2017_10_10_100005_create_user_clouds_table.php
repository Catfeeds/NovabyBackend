<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCloudsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_clouds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('use_cloud')->nullable();       //已使用
            $table->integer('have_cloud');                  //总大小
            $table->integer('surplus_cloud')->nullable();   //剩余
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_clouds');
    }
}
