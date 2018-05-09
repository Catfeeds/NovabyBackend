<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
use Cookie;
use Response;
use DB;
use Illuminate\Support\Facades\Input;

class IndexController extends Controller
{
    //
    public function userlists(){
        $info=Session::get('userInfo',null);
        $cat=Input::get('cat');
        $page=Input::get('page');
        $uid=Input::get('user');

        $page_size=30;
        $page=Input::get('page')>0 ? intval(Input::get('page')):1;
        $offset=($page-1)*$page_size;
        if($cat==2){
            $lists=DB::table('following')->select('user.user_icon','user.user_lastname','user.user_name','user.user_id','user.user_ismodeler')->leftJoin('user','following.to_uid','=','user.user_id')->where(['following.from_uid'=>$uid,'following.followed'=>1])->skip($offset)->take($page_size)->get();
            $count=DB::table('following')->where(['following.from_uid'=>$uid,'following.followed'=>1])->count();
        }
        if($cat==1){

            $lists=DB::table('following')->select('user.user_icon','user.user_lastname','user.user_name','user.user_id','user.user_ismodeler')->leftJoin('user','following.from_uid','=','user.user_id')->where(['following.to_uid'=>$uid,'following.followed'=>1])->skip($offset)->take($page_size)->get();
            $count=DB::table('following')->where(['following.from_uid'=>$uid,'following.followed'=>1])->count();
        }
        if($cat==3){
            $kw=Input::get('kw','');
            $kw=addslashes($kw);
            $lists=DB::table('user')->select('user_icon','user_lastname','user_name','user_id','user_ismodeler')->whereRAW("user_name LIKE '%".$kw."%' OR user_lastname LIKE '%".$kw."%'")->skip($offset)->take($page_size)->get();
            $count=DB::table('user')->whereRAW("user_name LIKE '%".$kw."%' OR user_lastname LIKE '%".$kw."%'")->count();
        }
        foreach($lists AS $k=>$v){

            $user_icon=DB::table('oss_item')->where('oss_item_id',$v->user_icon)->first();
            $lists[$k]->user_icon= $user_icon?ApiConf::IMG_URI.$user_icon->oss_path:'/images/logo.jpg';
            $lists[$k]->user_name=$v->user_name." ".$v->user_lastname;
            $lists[$k]->ism=$v->user_ismodeler?1:0;


            $lists[$k]->iht=200;;

            $hasLike=0;
            $followstr='follow';
            if($info){
                $ck_liked=DB::table('following')->where(['to_uid'=>$v->user_id,'from_uid'=>$info->user_id])->first();
                if($ck_liked && $ck_liked->followed){

                    $hasLike=1;
                    $followstr='unfollow';
                }

            }
            $lists[$k]->folowed=$hasLike;
            $lists[$k]->folowstr=$followstr;

        }
        $hasMore=($page*$page_size)<$count?1:0;
        $data=[
            'data'=>[
                'blogs'=>$lists,

                "hasrp"=>$hasMore?true:false,
                "has_next"=>$hasMore?true:false,
                "pgsource"=>"tp_",
                "nopth"=>false,
                'total'=>$count,

            ],
            'success'=>true,
        ];

        return response()->json($data);
    }
    public function userHome($id=0){
        $isc = preg_match('/[a-z_ ]/', $id);

        $info=Session::get('userInfo',null);
        if($isc){
            $user_info=DB::table('user')->where(['user_page_id'=>$id])->first();
        }else{
            $user_info=DB::table('user')->where(['user_id'=>$id])->first();
        }
        if(!$user_info){
            abort(404);
            exit;
        }
        header('location: https://www.novaby.com/#/home-page/'.$user_info->user_id);

        $user_info->user_icon=$user_info->user_icon?ApiConf::IMG_URI.$this->getOssPath($user_info->user_icon)->oss_path:'';
        $user_info->ism = $user_info->user_ismodeler?1:0;
        $cart_info=[];
        $notices=[];
        //$user_info->works=DB::table('element')->where('user_id'=>$user_info->user_id)->where('element_show','!=',2)->count();
        $works=DB::select("SELECT COUNT(*) AS tot FROM element WHERE user_id=".$user_info->user_id." AND element_show!=2");
        $user_info->works=$works[0]->tot;
        $user_info->following=DB::table('following')->where(['from_uid'=>$user_info->user_id,'followed'=>1])->count();
        $user_info->likes=DB::table('likes')->where(['like_uid'=>$user_info->user_id,'liked'=>1])->count();
        $user_info->follower=DB::table('following')->where(['to_uid'=>$user_info->user_id,'followed'=>1])->count();
        $hasFollow=0;
        $isme = 0;
        if($info){

            $isme=$info->user_id==$id?1:0;
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
            $ck_follow=DB::table('following')->where(['from_uid'=>$info->user_id,'to_uid'=>$user_info->user_id])->first();
            $hasFollow=$ck_follow?$ck_follow->followed:0;
        }
        $user_info->hasFollow=$hasFollow;
        $user_info->isme=$isme;


        return view('home.user',['user'=>$info,'userinfo'=>$user_info,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>$user_info->user_name.' '.$user_info->user_lastname]);
    }
    public function checkapply(){
        $info = Session::get('userInfo', null);
        if($info){
            $ck_survey = DB::table('wallet_logs')->where(['uid'=>$info->user_id,'type'=>1])->count();
            $verify = DB::table('user')->select('user_isvalidate','user_email')->where(['user_id'=>$info->user_id])->first();

            if($verify->user_isvalidate!=1){
                return response()->json(['code'=>200,'data'=>-10,'email'=>$verify->user_email,'_s'=>$ck_survey]);
            }
            $apply_info = DB::table('apply_user')->where(['uid'=>$info->user_id])->first();
            if($apply_info){
                if($apply_info->apply_model_id==0){
                    return response()->json(['code'=>200,'data'=>-3,'r'=>$apply_info->isread,'_s'=>$ck_survey]);
                }

                return response()->json(['code'=>200,'data'=>$apply_info->status,'r'=>$apply_info->isread,'_s'=>$ck_survey]);
            }else{
                return response()->json(['code'=>200,'data'=>-2,'r'=>0,'_s'=>$ck_survey]);
            }

        }else{
            return response()->json(['code'=>200,'data'=>-1,'r'=>0]);
        }
    }
    public function ClientLang($id=0){
        if($id==1){
            $r = setcookie("lang",1,time()+3600*24*30 ,"/");
            $r = setcookie("setlang",1,time()+3600*24*30 ,"/");
            var_dump($r);
        }elseif($id==0){
            $r = setcookie("setlang",1,time()+3600*24*30 ,"/");
            $r = setcookie("lang","",time()-10000,"/");
            var_dump($r);
        }
    }
    public function ckdraft(){
        $info = Session::get('userInfo', null);
        if(!$info){
            echo 0;
            exit;
        }
        $data = DB::table('element_pub')->where(['uid'=>$info->user_id,'ispub'=>0])->first();
        if(!$data){
            echo 0;
            exit;
        }else{
            echo $data->step;
        }
    }
    private function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
    public function ossToken(){
        $id= ApiConf::OSS_ACCESS_KEY;
        $key= ApiConf::OSS_SECRET_KEY;
        $host = 'https://elements.oss-cn-hongkong.aliyuncs.com';

        $now = time();
        $expire = 300; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        $dir = 'upload/'.date('Ymd').'/';

        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        //echo json_encode($arr);
        //return;
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        echo json_encode($response);
    }
    public function newhome(){
        return view('novahub.index');
        exit;

        $user_info = Session::get('userInfo',null);
        $user_id = 0;
        if($user_info){
            $user_id=$user_info->user_id;
            $_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$user_info->user_id])->first();
            if($_icon->user_icon==0){
                $user_info->icon = '/images/new/logo.jpeg';
            }else{
                $_oss_item = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_icon->user_icon])->first();
                $user_info->icon = ApiConf::IMG_URI.$_oss_item->oss_path."@0o_0l_30w_90q.src";
            }
        }
        $ids = [14355,14350,14349,14353,14390];
        $ids = [31342,31343,31347,31346,31345];

        //$ids = [10130,25282,10050,10024,31328,10061];
        $models = DB::table('element')->leftJoin('user','element.user_id','=','user.user_id')->select('element.element_id','element.user_id','element.element_name','element.element_description','element.element_cover_id','user.user_name','user.user_lastname')->whereIn('element.element_id',$ids)->orderBy('op_order','DESC')->get();
        foreach($models AS $k=>$v){
            $ck_likes = DB::table('likes')->where(['like_eid'=>$v->element_id,'like_uid'=>$user_id,'liked'=>1])->count();
            $_cover = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$v->element_cover_id])->first();
            $models[$k]->cover = ApiConf::IMG_URI.$_cover->oss_path;
            $models[$k]->liked=$ck_likes;

        }
        $list_ids = [14393,14378,14366,14357,14363,14377];
        $list_ids = [30999,27224,27311,11522,29719,29512];
        $list_ids = [10130,25282,10050,31341,31328,10061];

        $lists = DB::table('element')->select('element_id','user_id','element_name','element_cover_id')->whereIn('element_id',$list_ids)->get();
        foreach($lists AS $k=>$v){
            $_cover = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$v->element_cover_id])->first();
            $lists[$k]->cover = ApiConf::IMG_URI.$_cover->oss_path;
        }

        $artists_nums = DB::table('user')->count();
        $works_nums = DB::table('element')->count();
        $projects_nums = DB::table('projects')->count();
        if($artists_nums>1000){
            $artists_nums = (intval($artists_nums/1000)*1000).'+';
        }
        if($projects_nums>1000){
            $projects_nums = (intval($projects_nums/1000)*1000).'+';
        }
        $nums = (object)['artists'=>$artists_nums,'works'=>intval($works_nums/1000),'projects'=>$projects_nums];


        $cates = (object)[];

        $cart_info=[];
        $notices=[];
        $newcover = [
            [
                'pic'=>'/images/new/banner.png',
                'url'=>'/model/31345',
                'is_model'=>1,
                'text'=>'Spaceship@novaby.com/Jimmy Yen',
                'liked'=>0,
                'element_id'=>31345,
            ],
            [
                'pic'=>'/images/new/banner2.png',
                'url'=>'/model/31346',
                'is_model'=>1,
                'text'=>'KillerCrab@novaby.com/Jimmy Yen',
                'liked'=>0,
                'element_id'=>31346,
            ],
            [
                'pic'=>'/images/new/banner1.png',
                'url'=>'/model/31343',
                'is_model'=>1,
                'text'=>'CreaTure@novaby.com/Jimmy Yen',
                'liked'=>0,
                'element_id'=>31343,
            ],
            [
                'pic'=>'/images/new/banner0.png',
                'url'=>'/model/31349',
                'is_model'=>1,
                'text'=>'Monster@novaby.com/Jimmy Yen',
                'liked'=>0,
                'element_id'=>31349,
            ],
            [
                'pic'=>'/images/new/banner3.png',
                'url'=>'/model/31350',
                'is_model'=>1,
                'text'=>'Soldier@novaby.com/Jimmy Yen',
                'liked'=>0,
                'element_id'=>31350,

            ],
        ];

        if($user_id){
        foreach($newcover AS $k=>$v){
            $newcover[$k]['liked'] = DB::table('likes')->where(['like_eid'=>$v['element_id'],'like_uid'=>$user_id,'liked'=>1])->count();
        }}

        //dd($newcover);
        //$newcover = (object)$newcover;


        if($user_id){


            $notices=$this->getNoticesLists($user_id);

            $cart_info=$this->getCart($user_id);

        }

        return view('new.home.index',['covers'=>$newcover,'lists'=>$lists,'nums'=>$nums,'user_info'=>$user_info,'cates'=>$cates,'user'=>$user_info,'notices'=>$notices,'cart_info'=>$cart_info]);
    }

    public function uploadtest(){
        return view('test.uploadtest');

    }
}
