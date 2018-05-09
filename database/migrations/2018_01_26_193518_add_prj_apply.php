<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrjApply extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prj_apply', function (Blueprint $table) {
            //
            $table->integer('user_role')->comment('报价用户的角色id');
            $table->index('prj_id');
            $table->dropColumn('apply_cost_hour');
            $table->decimal('apply_price',10,2)->comment('报价金额')->change();
//            $table->tinyInteger('is_apply')->comment('是否自主申请,1=申请，2=甲方发送邀请报价')->change();
//            $table->tinyInteger('is_invite')->comment('是否被邀请,1=被邀请，2=甲方发送邀请报价');
//            $table->tinyInteger('is_recommend')->comment('是否被推荐,1=被推荐');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pri_apply', function (Blueprint $table) {
            //
        });
    }
}
