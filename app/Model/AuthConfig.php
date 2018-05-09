<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AuthConfig extends Model
{
    protected $table = 'auth_configs';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
