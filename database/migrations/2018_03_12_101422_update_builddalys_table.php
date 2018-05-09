<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBuilddalysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('builddalys', function (Blueprint $table) {
            $table->tinyInteger('is_del')->default(0)->comment('是否被删除','1是,0否');
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
