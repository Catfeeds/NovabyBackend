<?php

namespace App\Http\Controllers\Api;

use App\libs\StaticConf;
use App\libs\Tools;
use App\Model\AuthConfig;
use App\Model\Feedback;
use App\Model\Order;
use App\Model\Ossitem;
use App\Model\Project;
use App\Model\User;
use App\Model\Country;
use App\Model\UserInfo;
use App\Model\Work;
use App\Model\Job;
use App\Model\Field;
use App\Model\Following;
use App\Model\Likes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Input;



class UsersController extends BaseApiController
{
    private $tot = 0;
    private $total;

    public function userInfo(){
        $uid = Input::get('uid',0);
        $user = $this->getUserInfoById($uid);
        if(!$user){
            return $this->jsonErr("user not found");
        }
        return $this->jsonOk('ok',['user_info'=>$user]);
    }
    public function userInfom(){
        $uid = Input::get('uid',0);
        $user = $this->getUserInfoByIdm($uid);
        if(!$user){
            return $this->jsonErr("user not found");
        }
        return $this->jsonOk('ok',['user_info'=>$user]);
    }

    /**
     * 通过id获取用户信息
     * @param $uid
     * @return array
     */
    public function getUserInfoById($uid){
        $_user = User::with(['build'=>function($query){$query->where(['prj_progress'=>3.5,'prj_success'=>1],'info')->orderBy('created_at','DESC')->take(3);}])->find($uid);
        if(!$_user){
            return null;
        }
        $lang = $this->lang;
        $user = [];
        $user['user_id']=$uid;
        $user['user_name']=$_user->user_name." ".$_user->user_lastname;
        $user['user_avatar']=$this->getAvatar($_user->user_icon,'300');
        $user['user_type'] = $_user->user_type;
        $user['company_name'] = $_user->company_name;
        if($_user->info) {
            switch ($_user->info->user_lang){
                case 'zh':
                    $user['lang'] = 'zh';
                    break;
                default:
                    $user['lang'] = 'en';
                    break;
            }
        }else{
            $user['lang'] = ' ';
        }
        $dict_field =[];
        $fields = explode(",",$_user->user_fileds);
        if(count($fields)>=3){
            $field = Field::whereIn('id',$fields)->get();
            foreach($field AS $k=>$v){
                if($lang=='zh') {
                    $dict_field[]=$v->name_cn;
                }else{
                    $dict_field[]=$v->name;
                }
            }
        }else{
            $field = Field::whereIn('id',[1,2,3])->get();
            foreach($field AS $k=>$v){
                if($lang=='zh') {
                    $dict_field[]=$v->name_cn;
                }else{
                    $dict_field[]=$v->name;
                }
            }
        }
        $user['cover']=$_user->cover!=0?$this->getOssPath($_user->cover,'1000'):env('APP_URL').'/images/personal-default.png';
        $user['cover_top']=$_user->cover_top==null?0:$_user->cover_top;
        $user['cover_left']=$_user->cover_left==null?0:$_user->cover_left;
//        $user['user_job']       = $_job;
        $user['user_country']   = $_user->user_country?$_user->country->name:'';;
        $user['user_city']      = $_user->user_city?$_user->city->name:'';
        $user['user_fileds']    = $dict_field;
        $user['user_description'] = $_user->user_description!=null?$_user->user_description:'';
        $user['user_facebook']  = $_user->user_facebook_id?1:0;
        $user['user_twitter']   = $_user->user_twitter_id?1:0;
        $user['user_linkedin']  = $_user->user_linkedin_id?1:0;
        $user['user_insgram']   = 0;
        $user['user_pinterest'] = $_user->user_pinterest_id?1:0;
        $user['user_isfollow']  = $this->_user===null ? 0 : 1;
        $user['year_founded']   = $_user->year_founded!=0?$_user->year_founded:'';
        switch ($lang){
            case 'zh':
                $user['work_exp']       = !empty($_user->user_work_exp)?StaticConf::$work_exp_zh[$_user->user_work_exp]:StaticConf::$work_exp_zh[1];
                $user['company_size']   = !empty($_user->company_size)?StaticConf::$company_size_zh[$_user->company_size]['name']:'';
                $user['company_type']   = !empty($_user->company_type)?StaticConf::$company_type_zh[$_user->company_type]['name']:'';
                $user['english']      = !empty($_user->english_level)?StaticConf::$english_level_zh[$_user->english_level]:StaticConf::$english_level_zh[0];
                $user['hourly_rate']  = !empty($_user->hourly_rate)?StaticConf::$hourly_rate_zh[$_user->hourly_rate]:StaticConf::$hourly_rate_zh[0];
                break;
            default:
                $user['work_exp']       = !empty($_user->user_work_exp)?StaticConf::$work_exp[$_user->user_work_exp]:StaticConf::$work_exp[1];
                $user['company_size']   = !empty($_user->company_size)?StaticConf::$company_size[$_user->company_size]['name']:'';
                $user['company_type']   = !empty($_user->company_type)?StaticConf::$company_type[$_user->company_type]['name']:'';
                $user['english']      = !empty($_user->english_level)?StaticConf::$english_level[$_user->english_level]:StaticConf::$english_level[0];
                $user['hourly_rate']  = isset($_user->hourly_rate)?StaticConf::$hourly_rate[$_user->hourly_rate]:StaticConf::$hourly_rate[0];
                break;
        }
        if($_user->user_type !=4 ) {
            $home_page = $_user->user_page_id?env('CLIENT_BASE').'homepage/'.$_user->user_page_id:'';
        }else{
            $home_page = $_user->user_page_id?$_user->user_page_id:'';
        }
        $user['home_page'] = $home_page;
        $user['user_me']        = 0;
        if($this->_user){
            $user['user_isfollow']  = Following::where(['from_uid'=>$this->_user->user_id,'to_uid'=>$uid,'followed'=>1])->count();
            $user['user_me']        = $this->_user->user_id==$uid?1:0;
        }
        $user['projects'] = count($_user->build)?$this->getProject($_user->build):'';
        $user['works']      = $_user->user_works;
        $user['likes']      = Likes::where(['like_uid'=>$uid,'liked'=>1])->count();
        $user['followers']  = Following::where(['to_uid'=>$uid,'followed'=>1])->count();
        $user['followings'] = Following::where(['from_uid'=>$uid,'followed'=>1])->count();
        return $user;
    }
    public function getUserInfoByIdm($uid){
        $_user = User::find($uid);
        if(!$_user){
            return null;
        }
        $user = [];
        $user['user_id']=$uid;
        $user['user_name']=$_user->user_type==4?$_user->company_name:$_user->user_name." ".$_user->user_lastname;
        $user['user_avatar']=$this->getAvatar($_user->user_icon,'100');
        $user['user_type'] = $_user->user_type;
        $_user->company_size = $_user->company_size?$_user->company_size:0;
        $_country='';
        if($_user->user_country){
            $_country = $_user->Country->name;
        }
        $_job='';
        if($_user->user_job){
            $_job = $_user->Job->name;
        }
        $dict_field =[];
        $_fields = explode(",",$_user->user_fileds);
        if(count($_fields)){
            $_dict_field = Field::whereIn('id',$_fields)->get();
            foreach($_dict_field AS $k=>$v){
                $dict_field[]=$v->name;
            }
        }
        $user['user_job']       = $_job;
        $user['user_country']   = $_country;
        $user['user_city']      = $_user->user_city?$_user->city->name:'';
        $user['user_fileds']    = $dict_field;
        $user['user_description'] = $_user->user_description!=null?$_user->user_description:'';
        $user['user_facebook']  = $_user->user_facebook_id?1:0;
        $user['user_twitter']   = $_user->user_twitter_id?1:0;
        $user['user_linkedin']  = $_user->user_linkedin_id?1:0;
        $user['user_insgram']   = 0;
        $user['user_pinterest'] = $_user->user_pinterest_id?1:0;

        $user['work_exp']       = StaticConf::$work_exp[$_user->user_work_exp];
        $user['company_size']   = StaticConf::$company_size[$_user->company_size]['name'];
        $user['year_founded']   = $_user->year_founded!=0?$_user->year_founded:'';
        $user['company_type']   = StaticConf::$company_type[$_user->company_type]['name'];
        if($_user->user_type !=4 )
        {
            $home_page = $_user->user_page_id!=null?env('CLIENT_BASE').'homepage/'.$_user->user_page_id:'';

        }else{
            $home_page = $_user->user_page_id!=null?$_user->user_page_id:'';
        }
        $user['home_page'] = $home_page;

        return $user;
    }


    public function userWorks(){
        $uid        = Input::get('uid',0);
        $page       = Input::get('page',1);
        $page_size  = Input::get('pagesize',10);
        //$count = DB::table('works')->where(['work_uid'=>$uid])->count();
        $offset     = ($page-1)*$page_size;
        $tot        = Work::where(['work_uid'=>$uid])->count();

        $works      = Work::select('work_uid','work_id','work_cover','work_objs','work_price','work_views','work_model','created_at','work_detail')
            ->where(['work_uid'=>$uid])
            ->where('work_status',1)
            ->where('work_privacy',0)
            ->where('work_del',0)
            ->skip($offset)->take($page_size)->orderBy('work_id','DESC')->get();

        $works = $this->formatWorks($works);
        $me = 0;
        if($this->_user){

            $me = $this->_user->user_id == $uid ? 1 : 0;
        }
        if(count($works)>0){
            return $this->jsonOk('ok',['works'=>$works,'me'=>$me,'pages'=>ceil($tot/$page_size)]);
        }else{
            return $this->jsonErr(' You don\'t have any uploads');
        }


    }
    public function userWorksm(){
        $uid        = Input::get('uid',0);
        $page       = Input::get('page',1);
        $page_size  = Input::get('pagesize',10);
        //$count = DB::table('works')->where(['work_uid'=>$uid])->count();
        $offset     = ($page-1)*$page_size;
        $tot        = Work::where(['work_uid'=>$uid])->count();

        $works      = Work::select('work_uid','work_id','work_cover','work_objs','work_title')
            ->where(['work_uid'=>$uid])
            ->where('work_status',1)
            ->where('work_privacy',0)
            ->skip($offset)->take($page_size)->orderBy('work_id','DESC')->get();

        $works = $this->formatWorksm($works);
        $me = 0;
        if($this->_user){

            $me = $this->_user->user_id == $uid ? 1 : 0;
        }
        $user_info = [
            'user_facebook'=>0,
            'user_linkedin'=>0,
            'user_pinterest'=>0,
            'user_twitter'=>0,
        ];
        if(count($works)>0){
            return $this->jsonOk('ok',['works'=>$works,'me'=>$me,'pages'=>ceil($tot/$page_size)]);
        }else{
            return $this->jsonErr(' You don\'t have any uploads');
        }
    }

    public function likedWorks(){
        $uid        = Input::get('userId',0);
        $page       = Input::get('page',1);
        $page_size  = Input::get('pagesize',10);
        $offset     = ($page-1)*$page_size;
        $tot        = DB::table('likes')->where(['likes.like_uid'=>$uid,'likes.liked'=>1])->count();
        $works      = DB::table('likes')->leftJoin('works','likes.like_eid','=','works.work_id')->select('work_uid','works.work_id','works.work_cover','works.work_objs','works.work_price','works.work_views','work_photos','work_model','work_detail')
            ->where(['likes.like_uid'=>$uid,'likes.liked'=>1])->skip($offset)->take($page_size)->orderBy('work_id','DESC')->get();
        foreach($works AS $k=>$v){
            $_photos    = explode(",",$v->work_photos);
            unset($works[$k]->work_photos);
            $works[$k]->work_price      = $v->work_price?$v->work_price:0;
            $works[$k]->work_photo_nums = count($_photos);
            $works[$k]->work_objs       = $v->work_detail?$v->work_detail:0;
            $works[$k]->work_cover      = $this->getOssPath($v->work_cover,'800');
            $works[$k]->author          = $this->getAuthorAndWorks($v->work_uid);
            $works[$k]->work_likes      = Likes::where('like_eid',$v->work_id)->count();
            $works[$k]->has_download    =   0;
            if($this->_user){
                $works[$k]->has_download    =   Order::where('order_uid',$this->_user->user_id)->where('order_eid',$v->work_id)->count();
                $works[$k]->liked           =   DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1,'like_uid'=>$this->_user->user_id])->count();
            }
            $works[$k]->has_zip         = $v->work_model>0?1:0;

        }
        if($works){
            return $this->jsonOk('ok',['works'=>$works,'pages'=>ceil($tot/$page_size)]);
        }else{
            return $this->jsonErr(' You don\'t have any likes');
        }
    }

    public function getAuthorAndWorks($uid){
        $user = User::select('user_id','user_name','user_lastname','user_type','user_icon','user_country','user_job','company_name','user_city')->where(['user_id'=>$uid])->first();
        if(!$user){
            return '';
        }
        if($user->user_type==4) {
            $user->user_name = $user->company_name;
        }else{
            $user->user_name = $user->user_name.' '.$user->user_lastname;
        }
        unset($user->company_name);
        unset($user->user_lastname);

        $_country='';
        if($user->user_country){
            $_country       =   Country::where(['id'=>$user->user_country])->first();
            $_country       =   $_country->name;
        }
        $_city='';
        if($user->user_city){
            $_city       =   Country::where(['id'=>$user->user_city])->first();
            $_city       =   $_city->name;
        }
        $_job='';
        if($user->user_job){
            $_job = Job::where(['id'=>$user->user_job])->first();
            $_job = $_job->name;
        }
        $user->user_country = $_country;
        $user->user_city = $_city;
        $user->user_job     = $_job;
        $user->user_avatar= $this->getAvatar($user->user_icon,'200');
        unset($user->user_icon);
        $_recent_works = Work::select('work_id','work_cover')->where('work_uid',$uid)->where(['work_status'=>1,'work_del'=>0])->orderBy('work_id','desc')->limit(3)->get();

        foreach($_recent_works AS $k=>$v){
            $_recent_works[$k]->work_cover = $this->getOssPath($v->work_cover,'500');

        }
        $user->works = $_recent_works;

        $user->isfollow = 0;
        $user->me = 0;
        if($this->_user){
            $user->isfollow = Following::where(['from_uid'=>$this->_user->user_id,'to_uid'=>$uid,'followed'=>1])->count();
            $user->me = $this->_user->user_id==$uid?1:0;
        }
        return $user;
    }

    public function followers(){
        $uid        = Input::get('uid',0);
        $page       = Input::get('page',1);
        $page_size  = Input::get('pagesize',10);
        $followers  = $this->followlist($uid,$page,$page_size);
        //关注我的人
        if(count($followers)>0){
            //$hasMore = intval($this->tot!=($page-1)*$page_size+count($followers));
            $_c = ($page-1)*$page_size+count($followers);
            if($_c<0){
                $hasMore=0;
            }else{
                $hasMore = intval($this->total!=($page-1)*$page_size+count($followers));
            }
            return $this->jsonOk('ok',['followers'=>$followers,'pages'=>$this->tot,'has_more'=>$hasMore]);
        }else{
            return $this->jsonErr('You don\'t have any followers');
        }


    }
    public function followings(){
        $uid        = Input::get('uid',0);
        $page       = Input::get('page',1);
        $page_size  = Input::get('pagesize',10);
        $followings = $this->followlist($uid,$page,$page_size,1);
        if(count($followings)>0){


            //$hasMore = intval($this->tot!=($page-1)*$page_size+count($followings));

            $_c = ($page-1)*$page_size+count($followings);
            if($_c<0){
                $hasMore=0;
            }else{
                $hasMore = intval($this->total!=($page-1)*$page_size+count($followings));
            }
            return $this->jsonOk('ok',['followings'=>$followings,'pages'=>$this->tot,'has_more'=>$hasMore]);
        }else{
            return $this->jsonErr('You don\'t have any following');
        }


    }
    private function followlist($uid=0,$page=1,$page_size=10,$flag=0){
        $field          = 'to_uid';
        $sid            = 'from_uid AS sid';
        if($flag==1){
            $field      = 'from_uid';
            $sid        = 'to_uid AS sid';
        }
        $offset     = ($page-1)*$page_size;
        $followers  = $user['followers'] = Following::select($sid)
            ->where([$field=>$uid,'followed'=>1])
            ->skip($offset)->take($page_size)
            ->orderBy('op_time','DESC')
            ->get();
        $tot = $user['followers'] = Following::select('from_uid')
            ->where([$field=>$uid,'followed'=>1])
            ->count();
        $this->tot = ceil($tot/$page_size);
        $this->total = $tot;
        foreach($followers AS $k=>$v){
            $followers[$k]->authors = $this->getAuthorAndWorks($v->sid);
        }
        return $followers;

    }

    /**
     *模型列表market
     * 能否下载has_zip:1能下载,0不能下载
     * 是否有3D模型work_objs:1有，0无
     * @return mixed
     */
    public function market(){
        $page_size = Input::get('pagesize',10);
        $page = Input::get('page',1);
        $cate = Input::get('cate',0);
        $format = Input::get('format',0);
        $wnum = Input::get('wnum',30);
        $offset = ($page-1)*$page_size;
        $curr =$offset+$page_size;

        $flag=0;
        if($wnum>0){
            $rtimes = $curr/$wnum;
            if($curr>= $wnum && $curr%$wnum==0){//需要特殊数据
                $flag=1;
//                $offset-=($rtimes-1);
                $page_size -=1;
            }
        }
        $where = ' 1=1';
        if($cate){
            $where.= ' AND works.work_cate ='.$cate;
        }
        if($format){
            $where.= ' AND works.work_formats like \'%'.$format.'%\'';
        }
        $tot=DB::table('works')->where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0])
            ->whereRAW($where)
            ->count();
        $works = DB::table('works')->leftJoin('user','works.work_uid','=','user.user_id')
            ->select('work_uid','works.work_id','works.work_cover','works.work_detail','works.work_price','works.work_views','work_detail','work_model','created_at','work_permit','work_title')
            ->selectraw('0 AS whotofollow')
            ->where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0])
            ->whereRAW($where)
            ->skip($offset)
            ->take($page_size)
            ->orderBy('work_recommend','DESC')
            ->orderBy('work_id','DESC')
            ->get();
        if($flag){
                $_fusers =[];
                if($this->_user){
                    $my_followings = Following::where('from_uid',$this->_user->user_id)->
                    where('followed',1)->
                    select('to_uid')->get();

                    foreach($my_followings AS $k=>$v){
                        $_fusers[]=$v->to_uid;
                    }

                    $_fusers[]=$this->_user->user_id;
                }
                $unfollow_user = DB::table('user')->select('user_id')->whereNotIn('user_id',$_fusers)->where('user_works','>','0')->orderBy(DB::raw('RAND()'))->limit(1)->first();
                $r_works = DB::table('works')->select('work_uid','works.work_id','works.work_cover','works.work_detail','works.work_price','works.work_views','work_detail','work_model','created_at','work_permit')
                    ->selectraw('1 AS whotofollow')
                    ->leftJoin('user','user.user_id','=','works.work_uid')
                    ->where('works.work_uid',$unfollow_user->user_id)
                    ->where('works.work_status',1)
                    ->where('works.work_privacy',0)
                    ->where('works.work_del',0)
                    ->limit(1)
                    ->first();
                if($r_works){
                    $works[]=$r_works;
                }
        }

        foreach($works AS $k=>$v){

            $_cover_id = $v->work_cover;
            $works[$k]->whotofollow = $v->whotofollow;
            $works[$k]->work_price = $v->work_price?$v->work_price:0;
            $works[$k]->has_zip = $v->work_model>0?1:0;
            if( $works[$k]->has_zip){
                $works[$k]->has_zip = $v->work_permit;
            }
            $works[$k]->has_download=0;

            $works[$k]->work_objs = $v->work_detail?1:0;
            $works[$k]->work_cover = $this->getOssPath($v->work_cover,'500');
            $works[$k]->cover=$works[$k]->work_cover;
            if($_cover_id!=1){
                $size = Ossitem::find($_cover_id);
                $size->width=$size && $size->width?$size->width:1000;
                $size->height=$size && $size->height?$size->height:1000;
            }else{
                $size = (object)[];
                $size->width=1000;
                $size->height=1000;
            }
            $_r = 1000/$size->width;

            $_w =intval($size->width*$_r);
            $_h = intval($size->height*$_r);
            $works[$k]->cover_size =['width'=>$_w,'height'=>$_h];
            $works[$k]->work_pubtime = $v->created_at;
            $works[$k]->work_likes = DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1])->count();
            $works[$k]->liked = 0;
            if($this->_user){
                $works[$k]->liked=DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1,'like_uid'=>$this->_user->user_id])->count();
                $works[$k]->has_download=Order::where('order_uid',$this->_user->user_id)->where('order_eid',$v->work_id)->count();
            }

            $works[$k]->author = $this->getAuthorAndWorks($v->work_uid);
        }


        if($works){
            $maxid = Work::select('work_id')->where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0])->orderby('work_id','desc')->first();
            return $this->jsonOk('ok',['works'=>$works,'pages'=>ceil($tot/$page_size),'maxid'=>$maxid->work_id]);
        }else{
            return $this->jsonErr('No More Data');
        }

    }

    public function myfollowings(){
        if(!$this->_user){
            return response()->json(['code'=>-2,'msg'=>'not login ','data'=>[]]);
        }
        $page_size = Input::get('pagesize',10);
        $page = Input::get('page',1);
        $offset = ($page-1)*$page_size;
        $my_following_users = DB::table('following')->select('to_uid')->where(['from_uid'=>$this->_user->user_id,'followed'=>1])->get();
        $_fusers =[];
        foreach($my_following_users AS $k=>$v){
            $_fusers[]=$v->to_uid;
        }

        $works = DB::table('works')->select('work_uid','works.work_id','works.work_cover','works.work_objs','works.work_price','works.work_views','works.created_at','work_model')
            ->where(['works.work_privacy'=>0,'works.work_status'=>1,'work_del'=>0])->
            whereIn('works.work_uid',$_fusers)->skip($offset)->take($page_size)->orderBy('work_id','DESC')->get();
        $works = $this->formatWorks($works);
        if($works){
            $hasMore = 0;

            return $this->jsonOk('ok',['works'=>$works]);
        }else{
            return $this->jsonErr('No More Data');
        }
    }
    public function userCard(){

        $uid = Input::get('uid',0);
        $info = $this->getAuthorAndWorks($uid);
        if($info){
            return $this->jsonOk("ok",['author'=>$info]);
        }else{
            return $this->jsonErr("user not found");
        }
    }

    private function formatWorks($works){
        foreach($works AS $k=>$v){
            $works[$k]->work_price      = $v->work_price?$v->work_price:0;
            $works[$k]->work_objs       = $v->work_objs?$v->work_objs:0;
            $works[$k]->work_cover      = $this->getOssPath($v->work_cover,'800');
            $t_carbon=new Carbon($v->created_at);
            $t_int=$t_carbon->timestamp;
            $works[$k]->work_pubtime    = strtotime($v->created_at);
            $works[$k]->time    = time();
            unset($works[$k]->created_at);
            $works[$k]->has_zip         = $v->work_model?1:0;
            $works[$k]->has_download    = 0;
            $works[$k]->author          = $this->getAuthorAndWorks($v->work_uid);
            $works[$k]->work_likes      = DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1])->count();
            $works[$k]->liked           = 0;
            if($this->_user){
                $works[$k]->liked       = DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1,'like_uid'=>$this->_user->user_id])->count();
                $works[$k]->has_download= Order::where('order_uid',$this->_user->user_id)->where('order_eid',$v->work_id)->count();
            }
        }
        return $works;
    }

    private function formatWorksm($works){
        foreach($works AS $k=>$v) {
            $works[$k]->work_objs = $v->work_objs ? $v->work_objs : 0;
            $works[$k]->work_cover = $this->getOssPath($v->work_cover, '800');

            $works[$k]->cover=$works[$k]->work_cover;

        }
        return $works;
    }

    /**
     * 获取用户类型
     * @return mixed
     */
    public function userType()
    {
        $user = $this->_user;
        if(!$user)
        {
            return $this->jsonErr('error',' not login');
        }
        return $this->jsonOk('ok',['type'=>$user->user_type]);
    }

    /**
     * 反馈
     * @param Request $request
     * @return mixed
     */
    public function  feedback(Request $request)
    {
        $this->validate($request,[
            'email'=>'required|email',
            'content'=>'required'
        ]);
        $email = $request->get('email');
        $content = $request->get('content');
        $feedback = new Feedback();
        $feedback->feed_email = $email;
        $feedback->feed_content = $content;
        if($request->get('title'))
        {
            $feedback->feed_title = $request->get('title');
        }
        $feedback->save();
        return $this->jsonOk('ok','SeedBack successful,think you!');
    }

    /**
     * 模型问题反馈
     * @param Request $request
     * @return mixed
     */
    public function modelFeedback(Request $request)
    {
        $email = $this->_user->user_email;
        $title = '';
        $content = '';
        if($request->get('title'))
        {
            $this->validate($request,[
                'title' => 'required',
            ]);
            $title = $request->get('title');
        }
        if($request->get('content'))
        {
            $this->validate($request,[
                'content' => 'required',
            ]);
            $content = $request->get('content');
        }
        $this->validate($request,[
            'id' => 'required',
        ]);
        $id = $request->get('id');
        $feedback = new Feedback();
        $feedback->feed_email = $email;
        $feedback->feed_content = $content;
        $feedback->feed_title = $title;
        $feedback->feed_wid = $id;
        $feedback->save();
        return $this->jsonOk('ok','SeedBack successful,think you!');
    }

    /**
     * 取得申请介绍信息
     * @return mixed
     */
    public function information()
    {
        $user = $this->getAvatar($this->_user->user_icon,'200');
        $introduction = AuthConfig::find(1)->content;
        return $this->jsonOk('ok',['user'=>$user,'introduction'=>$introduction]);
    }
    /**
     * 取得申请介绍结果
     * @return mixed
     */
    public function result()
    {
        $result = $this->_user->user_type;
        $user = $this->getAvatar($this->_user->user_icon,'200');
        $functions = AuthConfig::where('id','!=',1)->get();
        $functions = $functions->map(function ($item){
            return $item->content;
        });
        return $this->jsonOk('ok',['result'=>$result,'user'=>$user,'function'=>$functions]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cover(Request $request)
    {

    }
    /*
     * 根据用户homepageid 查找用户id 前端跳转
     */

    public function findUser(){
        $name = Input::get('name','');
        if(!$name){
            return $this->jsonErr("not found");
        }
        $user_info=DB::table('user')->where(['user_page_id'=>$name])->first();

        if(!$user_info){
            return $this->jsonErr("not found");
        }
        return $this->jsonOk('ok',['user_id'=>$user_info->user_id]);
    }
    public function modelersearch(){
        $kw = Input::get('kw','');
        $kw = trim($kw);
        if(!$kw){
            return $this->jsonErr("not found");
        }
        $follow = $this->_user->follow;
        $follow =  $follow->map(function($item){
            return $item = $item->to_uid;
        })->all();
        $users=User::select('user_id','user_name','user_lastname','user_country','user_job','user_icon','user_type','user_city','company_name')
            ->whereRAW('(user_name like "%'.$kw.'%" OR user_lastname like "%'.$kw.'%" OR company_name LIKE "%'.$kw.'%")')
            ->where('user_type','>','2')
            ->whereIn('user_id',$follow)
            ->limit(5)
            ->get();
        if(!$users){
            return $this->jsonErr("not found");
        }
        foreach($users AS $k=>$v){
            $users[$k] = $this->getAuthorAndWorks1($v->user_id);
            $users[$k]->user_name=$v->user_name.' '.$v->user_lastname;
            $city = '';
            if($v->user_type==4){
                $city = Country::find($v->user_city);
                $city = $city->name;
                $users[$k]->user_name=$v->company_name;
            }
            $users[$k]->user_city = $city;
            unset($users[$k]->works);
        }
        if(count($users)){
            return $this->jsonOk('ok',['users'=>$users]);
        }else{
            return $this->jsonErr("not found");
        }
    }


    public function latestworknums(){
        $id = Input::get('id','0');
        if(!$id){
            return $this->jsonErr("error ");
        }
        $count = Work::where('work_id','>',$id)->where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0])->count();

        return $this->jsonOk('ok',['nums'=>$count]);
    }
    public function latestworks(){
        $id = Input::get('id','0');
        if(!$id){
            return $this->jsonErr("error ");
        }
        $works = DB::table('works')->leftJoin('user','works.work_uid','=','user.user_id')
            ->select('work_uid','works.work_id','works.work_cover','works.work_detail','works.work_price','works.work_views','work_detail','work_model','created_at','work_permit')
            ->where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0])
            ->where('work_id','>',$id)
            ->orderBy('work_id','DESC')
            ->get();

        if(count($works)){
            foreach($works AS $k=>$v){
                $_cover_id = $v->work_cover;
                $works[$k]->work_price = $v->work_price?$v->work_price:0;
                $works[$k]->has_zip = $v->work_model>0?1:0;
                if( $works[$k]->has_zip==1){
                    $works[$k]->has_zip = $v->work_permit;
                }
                $works[$k]->has_download=0;

                $works[$k]->work_objs = $v->work_detail?1:0;
                $works[$k]->work_cover = $this->getOssPath($v->work_cover,'1000');

                $size = Ossitem::find($_cover_id);

                $size->width=$size->width?$size->width:1000;
                $size->height=$size->height?$size->height:1000;
                $_r = 1000/$size->width;

                $_w =intval($size->width*$_r);
                $_h = intval($size->height*$_r);
                $works[$k]->cover_size =['width'=>$_w,'height'=>$_h];
                $works[$k]->work_pubtime = $v->created_at;
                $works[$k]->work_likes = DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1])->count();
                $works[$k]->liked = 0;
                if($this->_user){
                    $works[$k]->liked=DB::table('likes')->where(['like_eid'=>$v->work_id,'liked'=>1,'like_uid'=>$this->_user->user_id])->count();
                    $works[$k]->has_download=Order::where('order_uid',$this->_user->user_id)->where('order_eid',$v->work_id)->count();
                }
                $works[$k]->author = $this->getAuthorAndWorks($v->work_uid);
            }
        }
        $maxid = Work::select('work_id')->where(['work_privacy'=>0,'work_status'=>1,'work_del'=>0])->orderby('work_id','desc')->first();
        return $this->jsonOk('ok',['works'=>$works,'maxid'=>$maxid->work_id]);
    }

    /**
     * 项目
     * @param $projects
     * @return mixed
     */
    private function getProject($projects)
    {
        $projects = $projects->map(function ($item){
            $start = Carbon::parse($item->created_at)->timestamp;
            $end = Carbon::parse($item->updated_at)->timestamp;
            $hour = ceil(($end-$start)/3600);
            $project['name'] = $item->prj_name;
            $project['hour'] = $hour;
            $project['start'] = $start;
            $project['price'] = $item->prj_price;
            $project['project_hourlyRate'] = ceil($item->prj_price/$hour).'.00';
            $project['description'] = $item->prj_desc?$item->prj_desc:'';
            $project['result_time'] = $item->rate->r_time;
            $project['result_quality'] = $item->rate->r_quality;
            $project['result_commucation'] = $item->rate->r_other;
            return $item = $project;
        });
        return $projects;
    }



}
