<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrjChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prj_chats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('prj_id')->comment('项目id');
            $table->integer('chat_from_uid')->comment('发送消息的用户id');
            $table->integer('chat_to_uid')->comment('接收消息的用户id');
            $table->string('content',200)->comment('消息内容');
            $table->integer('created_at')->comment('消息时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('prj_chats');
    }
}
