<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'msg_id';
    public $timestamps = false;

    public function Notify()
    {
        return $this->hasOne('App\Model\Notify','id','msg_rid');
    }
}
