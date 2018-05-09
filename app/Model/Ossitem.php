<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Ossitem extends Model
{
    protected $table = 'oss_item';
    protected $primaryKey = 'oss_item_id';
    public $timestamps = false;
}
