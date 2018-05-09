<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    protected $table = 'user_plans';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function Plan()
    {
        return $this->belongsTo('App\Model\Plan','plan_id','id');
    }

    public function User()
    {
        return $this->belongsTo('App\Model\User','user_id','user_id');
    }
}
