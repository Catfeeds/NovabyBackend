<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MarkResponse extends Model
{
    //
    protected $table = 'mark_responses';
    public $timestamps = false;

    public function Project()
    {
        return $this->hasManyThrough('App\Model\Project','App\Model\BuildMark','id','prj_id');
    }

    public function Mark()
    {
        return $this->belongsTo('App\Model\BuildMark','mid','id');
    }
}
