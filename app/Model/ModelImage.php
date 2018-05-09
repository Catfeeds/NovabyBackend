<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ModelImage extends Model
{
    protected $table = 'model_images';
    protected $primaryKey = 'oss_item_id';
    public $timestamps = false;
}
