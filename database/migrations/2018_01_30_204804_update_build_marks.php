<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBuildMarks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('build_marks', function (Blueprint $table) {
            $table->string('number',12)->after('bid')->comment('标注编号');
            $table->tinyInteger('mark')->after('status')->comment('标注通过与否,1=通过,2=拒绝');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('build_marks', function (Blueprint $table) {
            //
        });
    }
}
