<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;


class Following extends Model
{
    protected $table = 'following';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = false;

}
