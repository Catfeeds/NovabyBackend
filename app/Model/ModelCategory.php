<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ModelCategory extends Model
{
    protected $table = 'model_categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;
}
