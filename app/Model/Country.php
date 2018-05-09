<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function __construct(array $attributes = [],$lang='en')
    {
        parent::__construct($attributes);
        switch ($lang)
        {
            case 'zh':
                $this->table='dict_country_cn';
                break;
            default:
                $this->table='dict_country';
                break;
        }
    }
}
