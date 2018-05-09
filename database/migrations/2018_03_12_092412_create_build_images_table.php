<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuildImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('build_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('build_id')->comment('build任务的id');
            $table->integer('oss_item_id')->comment('2D图');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('build_images');
    }
}
