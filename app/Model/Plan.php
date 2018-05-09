<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'dict_plan';
    protected $primaryKey = 'id';
    public $timestamps = false;

}
