<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarkCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mark_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mid')->comment('标注id');
            $table->integer('user_id')->comment('留言的用户id');
            $table->string('content')->comment('内容');
            $table->integer('create_time')->comment('创建时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mark_comments');
    }
}
