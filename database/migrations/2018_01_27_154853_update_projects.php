<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            //
            $table->string('prj_category',50)->comment('项目分类，多选')->change();
            $table->string('prj_tags',100)->comment('项目标签，多填')->change();
//            $table->tinyInteger('prj_budget')->comment('模型均价');
            $table->integer('prj_modeler')->nullable()->comment('接项目的乙方')->change();
            $table->integer('prj_attachment')->nullable()->comment('项目需求文件')->change();
//            $table->tinyInteger('prj_success')->nullable()->default(NULL)->comment('项目是否成功,1:pass,0:fail');
//            $table->tinyInteger('prj_expect')->nullable()->default(NULL)->comment('项目预算周期');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            //
        });
    }
}
