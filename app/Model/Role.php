<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function RolePermission()
    {
        return $this->hasMany('App\Model\RolePermission','role_id','id');
    }
    public function Permission()
    {
        return $this->hasManyThrough('App\Model\Permission','App\Model\RolePermission','role_id','id');
    }
}
