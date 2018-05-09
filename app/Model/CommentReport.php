<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    protected $table = 'comment_report';
    public $timestamps = false;

    public function User()
    {
        return $this->belongsTo('App\Model\User','from_uid','user_id');
    }

    public function Comment()
    {
        return $this->belongsTo('App\Model\Comment','comm_id','comment_id');
    }

    public function Reason()
    {
        return $this->hasOne('App\Model\Reason','id','reason_id');
    }
}
