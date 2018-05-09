<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BuildPay extends Model
{
    protected $table = 'build_pays';
    public $timestamps = false;

    public function Project()
    {
        return $this->hasOne('App\Model\Project','prj_id','pid');
    }

    /**甲方*/
    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','uid');
    }
}
