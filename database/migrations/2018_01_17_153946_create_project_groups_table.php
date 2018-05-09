<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('project_id')->comment('项目id');
            $table->integer('creator_id')->comment('创建者id');
            $table->string('group_name')->comment('group名称');
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
        Schema::drop('project_groups');
    }
}
