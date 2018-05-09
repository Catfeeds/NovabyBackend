<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameBuilddalysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('builddalys', function (Blueprint $table) {
            //
            $table->renameColumn('bd_status','status')->default(0)->comment('模型审核状态，0=甲方未操作;1=甲方通过;2=甲方拒绝');
            $table->string('bd_name', 50)->comment('模型名字')->change();
            $table->string('bd_photos', 100)->comment('模型原画对应oss-item的id')->change();
            $table->dropColumn('bd_precent');
            $table->string('bd_document',100)->comment('模型需求文档')->change();
            $table->text('bd_description')->comment('模型描述')->change();
            $table->dropColumn('bd_download');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('builddalys', function (Blueprint $table) {
            //
        });
    }
}
