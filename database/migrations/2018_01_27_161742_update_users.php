<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('year_founded',10)->nullable()->default(NULL)->comment('成立年份')->change();
            $table->float('project_success')->nullable()->default(0.01)->comment('项目成功率')->change();
            $table->float('project_time')->nullable()->default(0.01)->comment('项目效率')->change();
            $table->float('project_quality')->nullable()->default(0.01)->comment('项目质量')->change();
            $table->float('project_commucation')->nullable()->default(0.01)->comment('项目沟通')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
