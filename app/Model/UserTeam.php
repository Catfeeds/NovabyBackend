<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserTeam extends Model
{
    protected $table = 'user_teams';
    public $timestamps = false;

    public function Users()
    {
        return $this->hasManyThrough('App\Model\User','App\Model\TeamRelation','team_id','user_id');
    }
}
