<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PrjApply extends Model
{
    protected $table = 'prj_apply';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function project()
    {
        return $this->hasOne('App\Model\Project','prj_id','prj_id');
    }

    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','user_id');
    }
    public function Role()
    {
        return $this->hasOne('App\Model\Role','id','user_role');
    }
}
