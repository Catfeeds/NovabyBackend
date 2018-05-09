<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTalkKeyToPrjChats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prj_chats', function (Blueprint $table) {
            //
            $table->string('talk_key')->after('chat_to_uid')->comment('相对于两个沟通用户的key，key= 小user_id _ 大user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prj_chats', function (Blueprint $table) {
            //
        });
    }
}
