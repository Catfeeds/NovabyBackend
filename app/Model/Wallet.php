<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'wallet';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
