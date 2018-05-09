<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateApplyCashes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apply_cashes', function (Blueprint $table) {
            //
            $table->integer('amount')->comment('提现金额')->change();
            $table->string('apply_time',20)->comment('提现时间')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apply_cashes', function (Blueprint $table) {
            //
        });
    }
}
