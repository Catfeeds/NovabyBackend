<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProjectUser extends Model
{
    protected $table = 'project_users';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function Role()
    {
        return $this->hasOne('App\Model\Role','id','user_role');
    }
    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','user_id');
    }
}
