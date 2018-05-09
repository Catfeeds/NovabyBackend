<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamInvite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_invite', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->comment('团队id');
            $table->integer('inviter_id')->comment('邀请人的id');
            $table->integer('user_id')->comment('用户id');
            $table->tinyInteger('status')->default(0)->comment('邀请状态 0=未操作;1=同意;2=拒绝');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('team_invite');
    }
}
