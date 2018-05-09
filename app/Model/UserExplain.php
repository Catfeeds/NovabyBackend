<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserExplain extends Model
{
    public $timestamps = false;

    public function User()
    {
        return $this->belongsTo('App\Model\User','uid','user_id');
    }
}
