<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('英文名称');
            $table->string('name_cn')->comment('中文名称');
            $table->integer('pid')->comment('上一级权限');
            $table->string('display')->comment('返回前端的渲染名字');
            $table->tinyInteger('type')->comment('权限分级,1：一级菜单;2:二级菜单;3:三级功能');
            $table->string('url',100)->comment('一级菜单为图标链接，二级菜单为跳转链接');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('permissions');
    }
}
