<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    protected $table = 'time_zone';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
