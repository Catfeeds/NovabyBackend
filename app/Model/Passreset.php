<?php

namespace App\model;

use Illuminate\Database\Eloquent\Model;

class Passreset extends Model
{
    protected $table = 'pass_reset';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
