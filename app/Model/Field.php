<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'dict_field';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
