<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BuildDaily extends Model
{
    protected $table = 'builddalys';
    protected $primaryKey = 'bd_id';
    public $timestamps = false;

    public function Detail()
    {
        return $this->hasOne('App\Model\WorkDetail','id','bd_attachment_trans');
    }
    public function Project()
    {
        return $this->belongsTo('App\Model\Project','bd_pid','prj_id');
    }
    /**
     * 上传文件
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Upload()
    {
        return $this->hasOne('App\Model\WorkUpload','daily_id','bd_id');
    }

    /**
     * 2D图
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Images()
    {
        return $this->hasMany('App\Model\BuildImage','build_id','bd_id');
    }
    /**
     * 下载附件
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Attach()
    {
        return $this->hasMany('App\Model\BuildAttach','build_id','bd_id');
    }
}
