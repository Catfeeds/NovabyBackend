<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',50)->comment('角色名称');
            $table->string('name_cn',50)->comment('角色英文名称');
            $table->integer('pid')->comment('上级角色id');
            $table->tinyInteger('type')->comment('0商业项目角色，1内部项目角色');
            $table->tinyInteger('active')->comment('0隐藏，1显示');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_roles');
    }
}
