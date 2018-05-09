<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TeamRelation extends Model
{
    protected $table = 'team_relation';
    public $timestamps = false;
    protected $primaryKey = 'user_id';

    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','user_id');
    }
}
