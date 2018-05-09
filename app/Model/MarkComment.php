<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MarkComment extends Model
{
    protected $table = 'mark_comments';
    public $timestamps = false;

    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','user_id');
    }
}
