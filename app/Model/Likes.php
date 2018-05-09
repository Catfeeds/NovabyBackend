<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Likes extends Model
{
    protected $table = 'likes';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
