<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';
    protected $primaryKey = 'prj_id';
    public $timestamps = false;




    public function cate()
    {
        return $this->hasOne('App\Model\Cate','cate_id','prj_category');
    }
    public function industry()
    {
        return $this->hasOne('App\Model\Cate','cate_id','prj_industry');
    }
    public function format()
    {
        return $this->hasOne('App\Model\Cate','cate_id','prj_format');
    }

    /**甲方*/
    public function User()
    {
        return $this->hasOne('App\Model\User','user_id','prj_uid');
    }
    /**乙方*/
    public function Modeler()
    {
        return $this->hasOne('App\Model\User','user_id','prj_modeler');
    }
    /**订单*/
    public function Pay()
    {
        return $this->hasOne('App\Model\BuildPay','pid','prj_id');
    }
    /**报价*/
    public function Apply()
    {
        return $this->hasMany('App\Model\PrjApply','prj_id','prj_id');
    }
    /**问题*/
    public function Mark()
    {
        return $this->hasMany('App\Model\BuildMark','pid','prj_id');
    }
    /**结果*/
    public function Rate()
    {
        return $this->hasOne('App\Model\ProjectRate','r_pid','prj_id');
    }

    /**
     * 模型
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Models()
    {
        return $this->hasMany('App\Model\BuildDaily','bd_pid','prj_id');
    }
}
