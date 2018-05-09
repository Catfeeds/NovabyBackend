<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    protected $hidden = ['user_password'];

    public static function indexArtist(){

        $artists_id = [10000,10001];
        $artists = self::select('user_id','user_name','user_lastname','user_icon','user_country',
            'user_job')->
        whereIn('user_id',$artists_id)->
        get();
        return $artists;
    }

    /**
     * 国家
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Country()   //国家
    {
        return $this->hasOne('App\Model\Country','id','user_country');
    }

    /**
     * 城市
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function City()    //城市
    {
        return $this->hasOne('App\Model\Country','id','user_city');
    }

    /**
     * 工作
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Job()
    {
        return $this->hasOne('App\Model\Job','id','user_job');
    }

    /**
     * 发布的项目
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Project()  //发布的项目
    {
        return $this->hasMany('App\Model\Project','prj_uid','user_id');
    }

    /**
     * 接的项目
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Build()  //接的项目
    {
        return $this->hasMany('App\Model\Project','prj_modeler','user_id');
    }

    /**
     * 发布的模型
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Work()    //发布的模型
    {
        return $this->hasMany('App\Model\Work','work_uid','user_id');
    }

    /**
     * 消息
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Message()  //消息
    {
        return $this->hasMany('App\Model\Message','msg_from_uid','user_id');
    }

    /**
     * 被关注的人
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Follower()   //关注者
    {
        return $this->hasMany('App\Model\Following','to_uid','user_id');
    }

    /**
     * 关注的人
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Follow()   //关注的人
    {
        return $this->hasMany('App\Model\Following','from_uid','user_id');
    }

    /**
     * 申请认证的模型
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function authModel()  //申请认证的模型
    {
        return $this->hasOne('App\Model\Work','work_id','auth_model');
    }

    /**
     * 钱包
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Wallet()   //钱包
    {
        return $this->hasOne('App\Model\Wallet','uid','user_id');
    }

    /**
     * 技能
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Skill()     //技能
    {
        return $this->hasOne('App\Model\Field','id','user_fileds');
    }

    /**
     * 其他信息
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Info()      //其他信息
    {
        return $this->hasOne('App\Model\UserInfo','user_id','user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function TeamRelation()
    {
        return $this->hasOne('App\Model\TeamRealtion','user_id','user_id');
    }

}
