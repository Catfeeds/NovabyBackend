<?php

namespace App\Http\Controllers\Api;

use App\libs\QiNiuManager;
use App\libs\Tools;
use App\Model\Cate;
use App\Model\Country;
use App\Model\Following;
use App\Model\Job;
use App\Model\Likes;
use App\Model\Order;
use App\Model\Ossitem;
use App\Model\Tag;
use App\Model\Work;
use App\Model\User;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Input;

class ModelApiController extends BaseApiController
{

    public function ModelDetailm(){
        $id = Input::get('id',0);
        $model = Work::where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0,'work_id'=>$id])->first();
        if(!$model){
            return $this->jsonErr("not found");
        }
        if($model->work_privacy==1 || $model->work_status!=1){
            return $this->jsonErr("not found");
        }
        $model->work_views +=1;
        $model->save();
        $_model = (object)[];
        $_model->name = $model->work_title;
        $_model_photos = [];
        $_photos = explode(",",$model->work_photos);

        foreach ($_photos AS $k=>$v){
            $_model_photos[] = $this->getOssPath($v,'1125');
        }

        $_model->cover = $this->getOssPath($model->work_cover,'1125');

        $_model_photos[]=$_model->cover;
        $_model->photos = $_model_photos;
        $_model->photos = array_values((array_unique($_model_photos)));

        $_model->description=$model->work_description?$model->work_description:'';
        $user = User::where(['user_id'=>$model->work_uid])
            ->select('user_id','user_name','user_lastname','user_type','user_country','user_icon','user_job','user_city','company_name')
            ->first();


        $user->avatar = $this->getAvatar($user->user_icon,'200');



        if($user->user_country>0){

            $user->user_country=$user->country->name;
        }else{
            $user->user_country='';
        }
        if($user->user_city>0){
            $user->user_city=$user->City->name;;
        }else{
            $user->user_city='';
        }
        $user->user_work='';

        if($user->user_job){
            $user->user_work=$user->job->name;
        }

        $user->user_name = $user->user_name.' '.$user->user_lastname;
        if($user->user_type==4){
            $user->user_name = $user->company_name;
        }
        unset($user->country);
        unset($user->company_name);
        unset($user->user_icon);
        unset($user->job);
        unset($user->user_lastname);


        $_model->author = $user;


        $_model->hasmodel = 0;

        $d3d = $this->getModelFilesById($model->work_detail);
        if($d3d ){
            if($model->work_model_edit){
                $d3d->edit=json_decode($model->work_model_edit,true);
            }
        }
        if($d3d){
            $_model->hasmodel = 1;
        }
        $_model->model = $d3d;
        $_model->video = $model->work_video?$model->work_video:'';
        return $this->jsonOk('ok',['model'=>$_model]);
    }

    public function ModelDetail($id){

        if($this->Mobile()){
            $id = Input::get('id',0);
        }
        $model = Work::where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0,'work_id'=>$id])->first();
        if(!$model){
            return $this->jsonErr("not found");
        }
        if($model->work_privacy==1 || $model->work_status!=1){
            return $this->jsonErr("not found");
        }
        $model->work_views +=1;
        $model->save();
        $_model = (object)[];
        $_model->name = $model->work_title;
        $_model_photos = [];
        $_photos = explode(",",$model->work_photos);
        foreach ($_photos AS $k=>$v){
            $_model_photos[] = $this->getOssPath($v,'-1');
        }
        $_model->cover = $this->getOssPath($model->work_cover,'-1');
        $_model_photos[]=$_model->cover;
        $_model->photos = $_model_photos;
        $_model->photos = array_values((array_unique($_model_photos)));
        $_model->views = $model->work_views;
        $_model->likes = Likes::where('like_eid',$model->work_id)->where('liked',1)->count();
        $_model->liked = 0;
        if($this->_user) {
            $_model->liked = Likes::where('like_eid', $model->work_id)
                ->where('like_uid', $this->_user->user_id)
                ->where('liked', 1)
                ->count();
        }
        $_model->shares = $model->work_shares;
        $_model->downloads = Order::where('order_eid',$model->work_id)->count();
        $_model->description=$model->work_description?$model->work_description:'';
        $user = User::where(['user_id'=>$model->work_uid])
            ->select('user_id','user_name','user_lastname','user_type','user_country','user_icon','user_job','user_city','company_name')
            ->first();

        $user->avatar = $this->getAvatar($user->user_icon,'200');
        $_model->has_download = 0;
        $_model->my_rate=0;
        $_uid = 0;
        if($this->_user){
            $_uid=$this->_user->user_id;
        }
        $ck_rate = DB::table('rates')->select('stars')->where(['eid'=>$id,'uid'=>$_uid])->first();
        if($ck_rate){
            $_model->my_rate=$ck_rate->stars;
        }
        if($user->user_country>0){

            $user->user_country=$user->country->name;
        }else{
            $user->user_country='';
        }
        if($user->user_city>0){
            $_city = Country::find($user->user_city);
            $_city = $_city->name;
            $user->user_city=$_city;
        }else{
            $user->user_city='';
        }
        $user->user_work='';
        if($user->user_job){
            $user->user_work=$user->job->name;
        }
        $user->me = 0;
        $user->followed=0;
        $my_icon = 0;
        if($this->_user){
            $user->followed = Following::where('to_uid',$model->work_uid)->where('from_uid',$this->_user->user_id)->where('followed',1)->count();
            $__user = User::find($this->_user->user_id);
            $my_icon=$__user->user_icon;
            $_model->has_download=Order::where('order_eid',$model->work_id)->where('order_uid',$this->_user->user_id)->count();
            if($this->_user->user_id==$model->work_uid){
                $user->me = 1;
            }

        }
        $user->user_name = $user->user_name.' '.$user->user_lastname;
        if($user->user_type==4){
            $user->user_name = $user->company_name;
        }
        unset($user->country,$user->company_name,$user->user_icon,$user->job,$user->user_lastname);
        $my_avatar = $this->getAvatar($my_icon,'200');
        $_model->my_avatar = $my_avatar;
        $_model->author = $user;
        $_model->price = $model->work_price>0?$model->work_price:0;
        $_model->has_zip = $model->work_permit;
//        $_model->has_zip=$model->work_model>0?1:0;
        $_model->attr = $this->modelAttrs($model);
        $_model->hasmodel = 0;
        $d3d = $this->getModelFilesById($model->work_detail);
        if($d3d){
            $_model->hasmodel = 1;
        }
        $_model->model = $d3d;
        $_model->has_video = 0;
        if($model->work_video!=null)
        {
            $_model->has_video = 1;
            $_model->video = $model->work_video;
        }

        return $this->jsonOk('ok',['model'=>$_model]);
    }

    private function modelAttrs($model){
        $_attr=(object)[];
        $_attr->rate = 3.0;
        $_attr->id = $model->work_id;
        $_attr->category='';
        if($model->work_cate){
            $cate = Cate::where('cate_id',$model->work_cate)->first();
            $_attr->category = $cate->cate_name;
        }
        $_attr->label =[];
        if($model->work_tags){
            $w_tags = explode(",",$model->work_tags);
            $tags = Tag::whereIn('tag_id',$w_tags)->get();
            $label = [];
            foreach($tags AS $k=>$v){
                $label[]=$v->tag_name;
            }
            $_attr->label = $label;
        }

        $_attr->format = '';
        if($model->work_detail){
            $detail = DB::table('work_detail')->where(['id'=>$model->work_detail])->first();
            if($detail){
                $_attr->format = $detail->w_format;
            }
        }

        $_attr->size = 0;
        $item = Ossitem::find($model->work_model);
        if($item){
            $_attr->size = Tools::sizeConvert($item->size);
        }

        $_attr->feature = [];
        if($model->work_animation){
            $_attr->feature[]='Animation';
        }
        if($model->work_texture){
            $_attr->feature[]='Texture';
        }
        if($model->work_rigged){
            $_attr->feature[]='Rigged';
        }
        if($model->work_lowpoly){
            $_attr->feature[]='Lowpoly';
        }
        if($model->work_uvmap){
            $_attr->feature[]='UV-mapping';
        }
        if($model->work_material){
            $_attr->feature[]='Material';
        }
        $_attr->faces = $model->work_faces?$model->work_faces:'';
        $_attr->vertices = $model->work_vertices?$model->work_vertices:'';
        $_attr->license = "";
        $licences = [
            [
                'id'=>1,
                'name'=>'Attribution',
            ],
            [
                'id'=>2,
                'name'=>'Non-Commercial',
            ],
            [
                'id'=>3,
                'name'=>'Non-derivatives',
            ],
            [
                'id'=>4,
                'name'=>'Share alike',
            ],

        ];

        if($model->work_license){

            foreach($licences AS $k=>$v){

                if($model->work_license == $v['id']){
                    $_attr->license = $v['name'];
                }
            }
        }
        return $_attr;
    }

    /**
     * 获取七牛云token
     * @return mixed
     */
    public function getToken()
    {
        $qiNiu = new QiNiuManager(1);
        $country = $this->checkIp();
        if($country=='CN'){
            $token = $qiNiu->getToken('elements-cn');
            $region = 'z1';     //上传域名区域   华北
        }else{
            $token = $qiNiu->getToken('elements-us');
            $region = 'na0';    //上传域名区域   北美
        }
        return $this->jsonOk('ok',['token'=>$token,'region'=>$region]);
    }
}
