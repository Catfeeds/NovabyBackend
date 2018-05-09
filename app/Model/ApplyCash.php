<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ApplyCash extends Model
{
    protected $table = 'apply_cashes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function User()
    {
        return $this->belongsTo('App\Model\User','u_id','user_id');
    }
}
