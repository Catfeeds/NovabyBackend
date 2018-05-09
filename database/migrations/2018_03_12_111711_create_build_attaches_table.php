<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuildAttachesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('build_attaches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('build_id')->comment('build任务的id');
            $table->integer('oss_item_id')->comment('附件id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('build_attaches');
    }
}
