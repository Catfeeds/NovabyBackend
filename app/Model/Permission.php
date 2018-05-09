<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $hidden = ['id','pid','role_id','type','display'];
    public $timestamps = false;

    public function Child()
    {
        return $this->hasMany('App\Model\Permission','pid','id');
    }
}
