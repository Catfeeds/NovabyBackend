<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectInvitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_invites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('project_id')->comment('项目id');
            $table->integer('user_id')->comment('被邀请用户的id');
            $table->tinyInteger('status')->comment('邀请状态，0：未操作；1：同意；2拒绝；');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('project_invites');
    }
}
