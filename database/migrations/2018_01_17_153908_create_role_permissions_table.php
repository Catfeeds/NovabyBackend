<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('permission_id')->comment('权限id');
            $table->integer('role_id')->comment('角色id');
            $table->tinyInteger('read')->comment('是否可读(只针对功能权限),0=否，1=是');
            $table->tinyInteger('operate')->comment('是否可操作(只针对功能权限),0=否，1=是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('role_permissions');
    }
}
