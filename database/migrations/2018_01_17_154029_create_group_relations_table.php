<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id')->comment('group id');
            $table->integer('user_id')->comment('用户id');
            $table->tinyInteger('user_role')->comment('用户权限');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('group_relations');
    }
}
