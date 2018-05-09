<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BuildMark extends Model
{
    //
    protected $table = 'build_marks';
    public $timestamps = false;

    public function Project(){
        return $this->belongsTo('App\Model\Project','pid','prj_id');
    }
    public function Build(){
        return $this->belongsTo('App\Model\BuildDaily','bid','bd_id');
    }
    public function Marker()
    {
        return $this->belongsTo('App\Model\User','uid','user_id');
    }
    public function markResponse()
    {
        return $this->hasOne('App\Model\MarkResponse','mid','id');
    }
    public function Comment()
    {
        return $this->hasMany('App\Model\MarkComment','','');
    }

}
