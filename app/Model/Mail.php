<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Mail extends Model
{
    protected $fillable = ['title','content','type'];
}
