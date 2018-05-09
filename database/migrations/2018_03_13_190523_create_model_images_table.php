<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModelImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('work_id')->comment('模型id');
            $table->integer('oss_item_id')->comment('图片id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('model_images');
    }
}
