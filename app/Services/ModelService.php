<?php
namespace App\Services;

use App\Model\Work;

/**
 * Created by PhpStorm.
 * User: wunan
 * Date: 2018/3/13
 * Time: 下午5:09
 */
class ModelService
{
    public function __construct()
    {
        $this->model = new Work();
    }

    public function save($data)
    {
        
    }

}