<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $primaryKey = 'permission_id';
    public $timestamps = false;
    protected $hidden = ['id','role_id','permission_id'];
    protected $fillable = ['role_id','permission_id','read','operate'];
}
