<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WorkReport extends Model
{
    protected $table = 'work_report';
    public $timestamps = false;

    public function User()
    {
        return $this->belongsTo('App\Model\User','from_uid','user_id');
    }

    public function Work()
    {
        return $this->belongsTo('App\Model\Work','work_id','work_id');
    }

    public function Reason()
    {
        return $this->hasOne('App\Model\Reason','id','reason_id');
    }
}
