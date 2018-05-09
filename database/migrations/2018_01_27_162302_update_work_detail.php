<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWorkDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_detail', function (Blueprint $table) {
            //
            $table->string('w_objs',20)->nullable()->default(NULL)->change();
            $table->string('w_format',20)->nullable()->default(NULL)->change();
            $table->string('w_mets',200)->change();
            $table->string('work_model_edit',200)->comment('模型编辑数据')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_detail', function (Blueprint $table) {
            //
        });
    }
}
