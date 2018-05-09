<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddModelCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('work_id')->commeent('模型id');
            $table->integer('category_id')->comment('分类id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('model_categories');
    }
}
