<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWorks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('works', function (Blueprint $table) {
            //
//            $table->tinyInteger('work_privacy')->unsigned()->nullable()->default(NULL)->comment('0公开,1隐私');
//            $table->tinyInteger('work_del')->default(0)->comment('删除模型,0=未删除,1=删除');
            $table->dropColumn('work_vertices');
            $table->dropColumn('work_faces');
            $table->dropColumn('work_feature');
            $table->dropColumn('work_animation');
            $table->dropColumn('work_texture');
            $table->dropColumn('work_lowpoly');
            $table->dropColumn('work_uvmap');
            $table->dropColumn('work_material');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('works', function (Blueprint $table) {
            //
        });
    }
}
