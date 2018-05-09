<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TeamInvite extends Model
{
    protected $table = 'team_invite';
    public $timestamps = false;

    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','inviter_id');
    }

    public function Message()
    {
        return $this->hasOne('App\Model\Message','msg_rid','id');
    }
}
