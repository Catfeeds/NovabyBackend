<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;


class Work extends Model
{
    protected $table = 'works';
    protected $primaryKey = 'work_id';
    public $incrementing = true;

    public static function getIndex()
    {
        return self::select('work_id', 'work_uid', 'work_cover', 'work_title')->limit(5)->get();
    }

    public static function picksWork()
    {
        return self::select('work_id', 'work_cover', 'work_title')->limit(6)->orderBy('work_id', 'DESC')->get();
    }

    public function User()
    {
        return $this->belongsTo('App\Model\User', 'work_uid', 'user_id');
    }

    public function Cate()
    {
        return $this->hasOne('App\Model\Cate', 'cate_id', 'work_cate');
    }

    public function Tag()
    {
        return $this->belongsTo('App\Model\Tag', 'work_tags', 'tag_id');
    }

    public function Review()
    {
        return $this->hasOne('App\Model\WorkReview', 'wid', 'work_id');
    }

    /**
     * 模型详情
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Detail()
    {
        return $this->hasOne('App\Model\WorkDetail','id','work_detail');
    }


    /**
     * 上传文件
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Upload()
    {
        return $this->hasOne('App\Model\WorkUpload','work_id','work_id');
    }

    /**
     * 模型压缩包
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Zip()
    {
        return $this->hasOne('App\Model\Ossitem','oss_item_id','work_model');
    }

    /**
     * 模型分类
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function Category()
    {
        return $this->hasManyThrough('App\Model\Cate','App\Model\ModelCategory','work_id','cate_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function Images()
    {
        return $this->hasManyThrough('App\Model\Ossitem','App\Model\ModelImage','work_id','oss_item_id');
    }
}
