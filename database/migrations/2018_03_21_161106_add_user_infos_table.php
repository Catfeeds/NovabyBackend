<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_info', function (Blueprint $table) {
            $table->float('project_success')->default(0.01)->comment('项目成功率');
            $table->float('project_time')->default(0.01)->comment('平均项目效率');
            $table->float('project_quality')->default(0.01)->comment('平均项目质量');
            $table->float('project_commucation')->default(0.01)->comment('平均项目沟通');
            $table->integer('project_amount')->default(0)->comment('项目总利润');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_info', function (Blueprint $table) {
            //
        });
    }
}
