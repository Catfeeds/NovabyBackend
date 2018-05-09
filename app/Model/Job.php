<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'dict_job';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
