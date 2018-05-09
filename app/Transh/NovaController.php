<?php
namespace App\Http\Controllers;

use App\libs\Tools;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PdaData;
use DB;
use App\libs\ApiConf;
use Illuminate\Support\Facades\Input;
use Session;
class NovaController extends Controller{
	private $oss_base_path=null;
	public function __construct(){
		$this->oss_base_path='http://'.ApiConf::OSS_BUKET_NAME_ELEMENTS.'.'.ApiConf::OSS_ENDPOINT;
	}
	public function newhome(){
	    return view('home.newhome',['title'=>'Home']);
    }
        public function elementlists(){

            $filter=$_GET['filter'];
            parse_str(ltrim($filter,'&'));
            $condation = [];
            $format_condation = [];
            $whereraw = "(1=1)";
            $order = "element_top_time";

            if(isset($format) && $format!=0){
                //$format_condation['element_format'] = $format;
                //$format_condation['element_format1'] = $format;
                //$format_condation['element_format2'] = $format; 
                $whereraw = "(element_format=$format OR element_format1=$format OR element_format2=$format)";
            }
            if(isset($category) && $category!=0){
                $condation['element_category']=$category;
            }
            if(isset($animate) && $animate!=0){
                $condation['element_animation']=$animate;
            }
            if(isset($rigge) && $rigge!=0){
                $condation['element_rigged']=$rigge;
            }
            if(isset($poly) && $poly!=0){
                $condation['element_poly']=$poly;
            }
            if(isset($_GET['user'])){
                $condation['user_id']=intval($_GET['user']);
            }
            $islike=0;
            if(isset($_GET['likes'])){
                $islike=1;
            }
            if(isset($hot) && $hot == 1){
                $order = "element_likenum";

                
            }
            $issearch = 0;
            if(isset($kw)){
                $kw = addslashes($kw);
                $issearch = 1;
                
            }
            $info=Session::get('userInfo',null);

            DB::connection()->enableQueryLog();
            header("Access-Control-Allow-Origin: *");
            $condation['element_show']=0;
            $page_size=30;
            $page=Input::get('page')>0 ? intval(Input::get('page')):1;
            
            $offset=($page-1)*$page_size;
            if($islike){

                $lists=DB::table('likes')->leftJoin('element', 'likes.like_eid', '=', 'element.element_id')
                    ->select('element_id','user_id','element_cover_id','element_name','element_create_time','element_cover_id','element_views','element_likenum')->where(['likes.like_uid'=>intval($_GET['user']),'likes.liked'=>1])
                    ->skip($offset)->take($page_size)->orderBy('likes.id','DESC')->get();
                $count=DB::table('likes')->where(['like_uid'=>intval($_GET['user'])])->count();   
            }else{
                //DB::connection()->enableQueryLog(); 
                $lists=DB::table('element')
                    ->select('element_id','user_id','element_cover_id','element_name','element_create_time','element_cover_id','element_views','element_likenum')
                    ->where($condation)->WhereRaw($whereraw)->skip($offset)->take($page_size)->orderBy($order,'DESC')->orderBy('element_id','DESC')
                    ->get();

                $count=DB::table('element')->where($condation)->WhereRaw($whereraw)->count();
            }
            if($issearch){
                $ck_cate = DB::table('category')->select('cate_id')->where(['cate_name'=>$kw,'cate_pid'=>1])->first();
                $scate_id=$ck_cate ? $ck_cate->cate_id : 0;
                $search = "element_name LIKE '%".$kw."%'";
                if($scate_id){
                    $search = "element_name LIKE '%".$kw."%' OR element_category = ".$scate_id;
                }
                //DB::connection()->enableQueryLog();
                $lists=DB::table('element')
                    ->select('element_id','user_id','element_cover_id','element_name','element_create_time','element_cover_id','element_views','element_likenum')
                    ->WhereRaw($search)->skip($offset)->take($page_size)->orderBy($order,'DESC')
                    ->get();
                    //$queries = DB::getQueryLog(); // 获取查询日志

                //print_r($queries);
                $count=DB::table('element')->WhereRaw($search)->count();
            }
            $hasMore=($page*$page_size)<$count?1:0;
            $bgs = ['083a0c','5c5748','201633','192d44','311317'];
            foreach($lists AS $k=>$v){
                $cover_id = $v->element_cover_id;
                
                if($v->element_cover_id==0){
                    $_imgs = explode(',',$v->element_images);
                    $cover_id = $_imgs[0];

                }
                $cover=DB::table('oss_item')->where('oss_item_id',$cover_id)->first();

                $lists[$k]->image=$cover?$this->oss_base_path.'/'.$cover->oss_path:'';
                $user=DB::table('user')
                            ->select('user_icon','user_id','user_name','user_lastname','user_page_id','user_ismodeler')
                            ->where('user_id',$v->user_id)->first();

                $user_icon=DB::table('oss_item')->where('oss_item_id',$user->user_icon)->first();

                $lists[$k]->user_icon= $user_icon?ApiConf::IMG_URI.$user_icon->oss_path.'@0o_0l_50w_90q.src':'/images/logo.jpg';
                $lists[$k]->user_name=$user->user_name." ".$user->user_lastname;
                $lists[$k]->pubtime=date('H:i A',strtotime($v->element_create_time));
                
                $cover->width=($cover->width==0) ? 100 : $cover->width;
                $lists[$k]->iht=$cover?intval($cover->height/$cover->width*200):100;
                $lists[$k]->isrc=$cover?ApiConf::IMG_URI.$cover->oss_path.'@0o_0l_300w_90q.src':'';
                $lists[$k]->bg = '#'.$bgs[$k%5];
                $lists[$k]->user_id = $user->user_page_id?$user->user_page_id:$user->user_id;
                $lists[$k]->user_home = $user->user_page_id ? $user->user_page_id : 'user/'.$user->user_id;
                $lists[$k]->ism = $user->user_ismodeler ? 1:0;
                $hasLike='';
                if($info){
                    $ck_liked=DB::table('likes')->where(['like_eid'=>$v->element_id,'like_uid'=>$info->user_id])->first();
                    if($ck_liked && $ck_liked->liked) $hasLike=1;
                }
                $lists[$k]->like=$hasLike;
                $lists[$k]->element_name = $v->element_name;

            }
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
        
                public function demodatas(){
            header("Access-Control-Allow-Origin: *");
            $list_condation=[
                'element_show'=>1,
                ];
            $page_size=30;
            $page=Input::get('page')>0 ? intval(Input::get('page')):1;
            $offset=($page-1)*$page_size;
            $lists=DB::table('element')
                    ->select('element_id','user_id','element_cover_id','element_name','element_create_time','element_cover_id','element_views','element_likenum')
                    ->where($list_condation)->skip($offset)->take($page_size)->orderBy('element_id','DESC')
                    ->get();
           
            $count=DB::table('element')->where($list_condation)->count();
            $hasMore=($page*$page_size)<$count?1:0;
            foreach($lists AS $k=>$v){
                
                    $cover=DB::table('oss_item')->where('oss_item_id',$v->element_cover_id)->first();
                    /*
                    $lists[$k]->image=$this->oss_base_path.'/'.$cover->oss_path;
                    
                    $user=DB::table('user')
                            ->select('user_icon','user_id','user_name','user_lastname')
                            ->where('user_id',$v->user_id)->first();
                    $user_icon=DB::table('oss_item')->where('oss_item_id',$user->user_icon)->first();
                    
                    
                    $lists[$k]->user_icon=$this->oss_base_path.'/'.$user_icon->oss_path;
                    $lists[$k]->user_name=$user->user_name." ".$user->user_lastname;
                    $lists[$k]->pubtime=date('H:i A',strtotime($v->element_create_time));
                              $lists[$k]->albnm='花花女子与花花世界';
                              $lists[$k]->ava="http://cdn.duitang.com/uploads/people/201401/15/20140115014745_4K4Tr.png";
                              $lists[$k]->cmts=[];
                              $lists[$k]->favc=99;
                              //echo "#".$cover->width."/".$cover->height."=".$cover->width/$cover->height."#### w:200 ,height:".intval($cover->height/$cover->width*200)."<br/>";
                              $lists[$k]->iht=intval($cover->height/$cover->width*200);
                              //$lists[$k]->isrc=$this->oss_base_path.'/'.$cover->oss_path;
                              $lists[$k]->isrc='http://elements.img-cn-hongkong.aliyuncs.com/user-dir/'.$cover->oss_path.'@0o_0l_200w_90q.src';
                              //$lists[$k]->isrc='/upload/'.($v->element_cover_id-25).'.jpeg';
                              //$lists[$k]->iwd=200;
                              $lists[$k]->msg="【辣味椒盐虾】";
                              $lists[$k]->unm="一抹红嫣";
                              $lists[$k]->wait_audit=false;
*/
                
                
                $lists[$k]->is_robot=false;
                $lists[$k]->photo_id=10596568;
                $lists[$k]->unm="一抹红嫣";
                $lists[$k]->uid=1693226;
                $lists[$k]->cmts=[];
                $lists[$k]->good=false;
                $lists[$k]->common=false;
                $lists[$k]->album_wait_audit=false;
                $lists[$k]->price=0;
                $lists[$k]->rid=95800538;
                $lists[$k]->buylnk="";
                $lists[$k]->sender_wait_audit=false;
                $lists[$k]->zanc=0;
                $lists[$k]->sta=0;
                $lists[$k]->ava="http://elements.img-cn-hongkong.aliyuncs.com/user-dir/29.jpeg@0o_0l_200w_90q.src";
                $lists[$k]->coupon_price=0;
                $lists[$k]->albnm="花花女子与花花世界";
                $lists[$k]->iht=intval($cover->height/$cover->width*200);;
                $lists[$k]->albid=5542595;
                $lists[$k]->favc=99;
                $lists[$k]->wait_audit=false;
                $lists[$k]->ruid=802123;
                $lists[$k]->id=97290923;
                $lists[$k]->repc=0;
                //$lists[$k]->isrc="http://img4.duitang.com/uploads/blog/201309/14/20130914190628_rFT3G.thumb.200_0.jpeg";
                $lists[$k]->isrc=ApiConf::IMG_URI.'user-dir/'.$cover->oss_path.'@0o_0l_200w_90q.src';
                $lists[$k]->msg="【辣味椒盐虾】1、 洋葱切丝、葱切小段；2、 锅中放油，稍稍多一些，虾入油锅炸；3、 一直大火炸，炸至虾壳跟虾肉分离,捞起沥油；4、 锅中放一部分炸虾的油，放干辣椒爆香略炒；5、 放洋葱，小火炒香,放入炸好的虾，转大火，稍稍翻炒,放入椒盐，炒匀；6、 撒葱花，出锅。";

            }
            $data=[
                'data'=>[
                    'albums'=>$lists,  
                    
                    "hasrp"=>true,
                    "has_next"=>true,
                    "pgsource"=>"tp_",
                    "nopth"=>false
                    
                ],
                 'success'=>true,
            ];
   
            return response()->json($data);
        }
        public function data(){
            $info=Session::get('userInfo',null);
            if(!$info){
                
                exit;
            }
            $data=[];
            
            for($i=0;$i<20;$i++){
                $tmp=[
                'image' => 'http://wlog.cn/demo/waterfall/images/001.jpg',
                'width'=> 192,
                'height'=> 288
                        ];
                $data[]=$tmp;
            }
            $follows_users=DB::table('following')->select('to_uid')->where(['from_uid'=>$info->user_id,'followed'=>1])->get();
            $follows_users_arr=[];
            foreach($follows_users AS $k=>$v){
                $follows_users_arr[]=$v->to_uid;
            }
            if(!$follows_users_arr) exit;
            //$follows_users_str=rtrim($follows_users_str,',');
            //dd($follows_users_str);
            $list_condation=[
                'element_show'=>0,
                
                ];
            $page_size=30;
            $page=Input::get('page')>0 ? intval(Input::get('page')):1;
            $offset=($page-1)*$page_size;
            if(!$follows_users_arr){
                $follows_users_arr=[10000,10001];
            }
                $follows_users_str= implode(",",$follows_users_arr);


            /*
            $lists=DB::table('element')
                    ->select('element_id','user_id','element_cover_id','element_name','element_create_time','element_cover_id','element_views','element_likenum')
                    ->whereIn('user_id',$follows_users_arr)->where(['element_show'=>1])->skip($offset)->take($page_size)->orderBy('element_id','DESC')
                    ->get();
            */
            $lists=DB::table('element')
                ->select('element_id','user_id','element_cover_id','element_name','element_create_time','element_cover_id','element_views','element_likenum')
                ->whereRAW('user_id IN ('.$follows_users_str.') AND element_show=0')->skip($offset)->take($page_size)->orderBy('element_id','DESC')
                ->get();
           
            $count=DB::table('element')->whereRAW('user_id IN ('.$follows_users_str.') AND element_show=0')->count();
            $hasMore=($page*$page_size)<$count?1:0;
            foreach($lists AS $k=>$v){
                //$novas[$k]->first=$imgs[0];
                $cover=DB::table('oss_item')->where('oss_item_id',$v->element_cover_id)->first();
                $lists[$k]->image=ApiConf::IMG_URI.$cover->oss_path;
                $user=DB::table('user')
                        ->select('user_icon','user_id','user_name','user_lastname','user_ismodeler')
                        ->where('user_id',$v->user_id)->first();
                $user_icon=DB::table('oss_item')->where('oss_item_id',$user->user_icon)->first();
                $lists[$k]->user_icon=$user->user_icon?$this->oss_base_path.'/'.$user_icon->oss_path:'/images/logo.jpg';
                $lists[$k]->ism=$user->user_ismodeler?1:0;
                $lists[$k]->user_name=$user->user_name." ".$user->user_lastname;
                $lists[$k]->pubtime=date('H:i A',strtotime($v->element_create_time));


            }
            
            return response()->json(['total'=>20,'result'=>$data,'result'=>$lists,'path'=>$this->oss_base_path,'hasMore'=>$hasMore]);
        }
        public function follow(){
            $info=Session::get('userInfo',null);
            if(!$info){
                header('Location: /#login');
                exit;
            }
            $user=DB::table('user')->where('user_id',$info->user_id)->first();
            $oss_item=DB::table('oss_item')->where('oss_item_id',$user->user_icon)->first();
            $user->user_icon=$user->user_icon ? ApiConf::IMG_URI.$oss_item->oss_path.'@0o_0l_100w_90q.src':'/image/defaultuser.png';
            $works=DB::table('element')->where(['user_id'=>$info->user_id,'element_show'=>0])->count();
            $user->works=$works;
            $follower=DB::table('following')->where(['to_uid'=>$info->user_id,'followed'=>1])->count();
            $following=DB::table('following')->where(['from_uid'=>$info->user_id,'followed'=>1])->count();
            $user->follower=$follower;
            $user->following=$following;
            $user->ism = $user->user_ismodeler?1:0;
            $cart_info=[];
            $notices=[];

            if($info){
                $notices=$this->getNoticesLists($info->user_id);
                $cart_info=$this->getCart($info->user_id);
            }
            $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
            \App::setLocale($lang);
            $title=isset($lang)?'我的关注':'My Following';
            return view('home.follow',['user'=>$user,'path'=>$this->oss_base_path,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>$title]);
        }
        public function followlist(){
            $info=Session::get('userInfo',null);
            if(!$info) exit;
            $users=DB::table('user')->select('user_id','user_name','user_lastname','user_icon')->where('user_id','!=',$info->user_id)->limit(5)->inRandomOrder()->get();
            foreach($users AS $k=>$v){
                $works=DB::table('element')->where('user_id',$v->user_id)->count();
                $icon=DB::table('oss_item')->where('oss_item_id',$v->user_icon)->first();
                $users[$k]->avatar=$v->user_icon?ApiConf::IMG_URI.$icon->oss_path.'@0o_0l_200w_90q.src':'/images/logo.jpg';
                $users[$k]->works=$works;
                $_ism = DB::table('user')->select('user_ismodeler')->where(['user_id'=>$v->user_id])->first();
                $users[$k]->ism =$_ism->user_ismodeler?1:0;
                $models=DB::table('element')->select('element_id','element_cover_id')->where('user_id',$v->user_id)->limit(5)->get();
                foreach($models AS $ki=>$vi){
                    $oss_item=DB::table('oss_item')->where('oss_item_id',$vi->element_cover_id)->first();
                    $models[$ki]->img=ApiConf::IMG_URI.$oss_item->oss_path;
                }
                $isfollow=DB::table('following')->where(['to_uid'=>$v->user_id,'from_uid'=>$info->user_id,'followed'=>1])->first();
                $users[$k]->isfollow=$isfollow ?1:0;
                $users[$k]->models=$models;
            }
            
            return response()->json(['code'=>200,'msg'=>'ok','lists'=>$users]);
        }
        public function home1(){
            $info=Session::get('userInfo',null);
            $isajax=Input::get('ajax');
            $category=Input::get('category');
            $style=Input::get('style');
            $level=Input::get('level');
            $format=Input::get('format');
            $price=Input::get('price');
            $animation=Input::get('animation');
            $list_condation=[];
            if($category){
                    $list_condation['element_category']=$category;
            }
            if($style){
                    $list_condation['element_style']=$style;
            }
            if($level){
                    $list_condation['element_level']=$level;
            }
            if($format){
                    $list_condation['element_format']=$format;
            }
            if($price){
                    $list_condation['element_price']=$price;
            }
            if($animation){
                    $list_condation['element_animation']=$animation;
            }

            $page_size=30;
            $page=Input::get('page')>0 ? intval(Input::get('page')):1;
            $offset=($page-1)*$page_size;
            $novas=DB::table('element')->where($list_condation)->skip($offset)->take($page_size)->orderBy('element_id','DESC')->get();
            
            $count=DB::table('element')->where($list_condation)->count();
            $hasMore=($page*$page_size)<$count?1:0;


            foreach($novas AS $k=>$v){

                    $tmp_arr=explode(',',$v->element_images);

                    $imgs=DB::table('oss_item')->whereIn('oss_item_id',$tmp_arr)->lists('oss_path');

                    $novas[$k]->images=$imgs;
                    //$novas[$k]->first=$imgs[0];
                    $cover=DB::table('oss_item')->where('oss_item_id',$v->element_cover_id)->first();
                    $novas[$k]->cover=$cover->oss_path;
                    $tag=DB::table('tags')->where('tag_id',$v->element_tags)->first();
                    $novas[$k]->tags=$tag;
                    $user=DB::table('user')->where('user_id',$v->user_id)->first();
                    $novas[$k]->user=$user;


            }
//dd($novas);
            if($isajax){
                    echo json_encode(['datas'=>$novas,'path'=>$this->oss_base_path,'hasMore'=>$hasMore]);
                    return;
            }

            $data=DB::table('category')->get();
            $tmp=[];
            foreach ($data as $k => $v) {
                    $tmp[$v->cate_pid][]=$v;
            }
            $cates=[];
            foreach ($tmp[0] as $k => $v) {
                    $cates[$v->cate_name]=$tmp[$k+1];
            }
           
            return view('home.home',['user'=>$info,'lists'=>$novas,'path'=>$this->oss_base_path]);
        }
        private function isValidInvite($code){
            if($code=='53550014ea33c66b0e24317d5c7eaf53') return 1;
            $ck_data = DB::table('invite')->where(['code'=>$code])->first();

            if($ck_data && $ck_data->times <=5){
                return 1;
            }
            return 0;
        }
        public function home(){
            if(strpos($_SERVER['HTTP_USER_AGENT'],"Mobile")!==false){
                return redirect('/m/home');
            }
            $invite_code = Input::get('invite','');
            $invite = $this->isValidInvite($invite_code);
            $info=Session::get('userInfo',null);
            $survey_op=0;
            if(Session::get('skipsurvey') && Session::get('skipsurvey')-time()>0){
                $survey_op=2;
            }
            if(Session::get('survey') && Session::get('survey')-time()>0){
                $survey_op=1;
            }

            $cate=DB::table('category')->where('cate_pid',0)->get();

            foreach ($cate as $k => $v) {
                $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();

                $cate[$k]->sub=$sub_data;
            }


            $cates=(object)[];
            foreach($cate AS $k=>$v){
                $attr=$v->cate_name;
                if(isset($_COOKIE['lang']) && $attr=='category'){
                    foreach($v->sub AS $sk=>$sv){

                        $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                    }
                }
                $cates->$attr=$v->sub;
            }
            //$cates->category=[];

            $isajax=Input::get('ajax');
            $category=Input::get('category');
            $style=Input::get('style');
            $level=Input::get('level');
            $format=Input::get('format');
            $price=Input::get('price');
            $animation=Input::get('animation');
            $list_condation=[];
            $list_condation['element_show']=1;
            if($category){
                    $list_condation['element_category']=$category;
            }
            if($style){
                    $list_condation['element_style']=$style;
            }
            if($level){
                    $list_condation['element_level']=$level;
            }
            if($format){
                    $list_condation['element_format']=$format;
            }
            if($price){
                    $list_condation['element_price']=$price;
            }
            if($animation){
                    $list_condation['element_animation']=$animation;
            }

            $page_size=30;
            $page=Input::get('page')>0 ? intval(Input::get('page')):1;
            $offset=($page-1)*$page_size;
            $novas=DB::table('element')->where($list_condation)->skip($offset)->take($page_size)->orderBy('element_id','DESC')->get();
            
            $count=DB::table('element')->where($list_condation)->count();
            $hasMore=($page*$page_size)<$count?1:0;

            foreach($novas AS $k=>$v){
                    $tmp_arr=explode(',',$v->element_images);

                    $imgs=DB::table('oss_item')->whereIn('oss_item_id',$tmp_arr)->lists('oss_path');

                    $novas[$k]->images=$imgs;
                    //$novas[$k]->first=$imgs[0];
                    $cover=DB::table('oss_item')->where('oss_item_id',$v->element_cover_id)->first();
                    $novas[$k]->cover=$cover->oss_path;
                    $tag=DB::table('tags')->where('tag_id',$v->element_tags)->first();
                    $novas[$k]->tags=$tag;
                    $user=DB::table('user')->where('user_id',$v->user_id)->first();
                    $novas[$k]->user=$user;


            }
            if($isajax){
                    echo json_encode(['datas'=>$novas,'path'=>$this->oss_base_path,'hasMore'=>$hasMore]);
                    return;
            }



            $cart_info=[];
            $notices=[];

            if($info){
                $notices=$this->getNoticesLists($info->user_id);
                $cart_info=$this->getCart($info->user_id);
            }
            $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
            //dd($lang);
            //\App::setLocale($lang);
            $title=($lang=='zh_cn')?'首页':'Home';
            return view('home.home',['user'=>$info,'lists'=>$novas,'path'=>$this->oss_base_path,'notices'=>$notices,'cart_info'=>$cart_info,'cates'=>$cates,'invite'=>$invite,'title'=>$title,'_op'=>$survey_op]);
        }
	
	public function lists(){
            $isajax=Input::get('ajax');
		$category=Input::get('category');
		$style=Input::get('style');
		$level=Input::get('level');
		$format=Input::get('format');
		$price=Input::get('price');
		$animation=Input::get('animation');
		$list_condation=[];
		if($category){
			$list_condation['element_category']=$category;
		}
		if($style){
			$list_condation['element_style']=$style;
		}
		if($level){
			$list_condation['element_level']=$level;
		}
		if($format){
			$list_condation['element_format']=$format;
		}
		if($price){
			$list_condation['element_price']=$price;
		}
		if($animation){
			$list_condation['element_animation']=$animation;
		}
		
		$page_size=9;
		$page=Input::get('page')>0 ? intval(Input::get('page')):1;
		$offset=($page-1)*$page_size;
		$novas=DB::table('element')->where($list_condation)->skip($offset)->take($page_size)->orderBy('element_id','DESC')->get();

		$count=DB::table('element')->where($list_condation)->count();
		$hasMore=($page*$page_size)<$count?1:0;
		

		foreach($novas AS $k=>$v){
			
			$tmp_arr=explode(',',$v->element_images);
			
			$imgs=DB::table('oss_item')->whereIn('oss_item_id',$tmp_arr)->lists('oss_path');
			
			$novas[$k]->images=$imgs;
			$novas[$k]->first=$imgs[0];

	
		}
                
                

		
		if($isajax){
			return response()->json(['datas'=>$novas,'path'=>$this->oss_base_path,'hasMore'=>$hasMore]);
			
		}

		$data=DB::table('category')->get();
		$tmp=[];
		foreach ($data as $k => $v) {
			$tmp[$v->cate_pid][]=$v;
		}
		$cates=[];
		foreach ($tmp[0] as $k => $v) {
			$cates[$v->cate_name]=$tmp[$k+1];
		}
		
		
		return view('nova.list',['cates'=>$cates,'datas'=>$novas,'path'=>$this->oss_base_path,'title'=>'Elements']);
	
		}
        public function model($id=0){

            $element=DB::table('element')->where('element_id',$id)->first();
            if(!$element){
              abort(404);
              exit;  
            } 
            DB::table('element')->where('element_id',$id)->increment('element_views');
            $info=Session::get('userInfo',null);
            $hasBuy=0;
            if($info){
                $myicon=DB::table('user')->select('user_icon')->where('user_id',$info->user_id)->first();
                if($myicon->user_icon==0){
                    $info->user_icon='';
                }else{
                    $info->user_icon=$this->getOssPath($myicon->user_icon)->oss_path;
                }
                $ckbuy=DB::table('orders')->where(['order_uid'=>$info->user_id,'order_eid'=>$id])->first();
                if($ckbuy){
                    $hasBuy=$ckbuy->order_status+1;
                }
            }

            $cover=DB::table('oss_item','width','height')->where('oss_item_id',$element->element_cover_id)->first();

            $element->cover=$cover->oss_path;
            $element->coverw=$cover->width;
            $element->coverh=$cover->height;
            $element->element_description = htmlspecialchars_decode(htmlentities($element->element_description));

            $geometry=DB::table('category')->where(['cate_id'=>$element->element_geometry])->first();
            $element->element_geometry=$geometry->cate_name;
            $user=DB::table('user')->where('user_id',$element->user_id)->first();

            $icon=DB::table('oss_item')->where('oss_item_id',$user->user_icon)->first();
            $user->icon=$icon?ApiConf::IMG_URI.$icon->oss_path:'';
            $user->ism = $user->user_ismodeler?1:0;
            //$user->works=DB::table('element')->where('user_id',$user->user_id)->count();
            $works=DB::select("SELECT COUNT(*) AS tot FROM element WHERE user_id=".$user->user_id." AND element_show!=2");
            $user->works=$works[0]->tot;
            $user->following=DB::table('following')->where(['from_uid'=>$user->user_id,'followed'=>1])->count();
            $user->follower=DB::table('following')->where(['to_uid'=>$user->user_id,'followed'=>1])->count();
            $user->hasFollow=0;
            $user->isme=0;
            $user->homepage=$user->user_page_id?'/'.$user->user_page_id:'/user/'.$user->user_id;
            $user->homepage= 'http://'.$_SERVER['HTTP_HOST'].$user->homepage;
            if($info){
                $ck_follow=DB::table('following')->where(['from_uid'=>$info->user_id,'to_uid'=>$element->user_id,'followed'=>1])->get();
                if($ck_follow) $user->hasFollow=1;
                $user->isme=$info->user_id==$element->user_id?1:0;
            }
            $element->user=$user;

            //dd($element);
            $element->hasBuy=$hasBuy;
            //DB::connection()->enableQueryLog();
            $pics=DB::table('oss_item')->select('oss_key','oss_path','width','height')->whereIn('oss_item_id',explode(",",$element->element_images))->get();
            $element->pics=$pics;
            $rate=DB::table('rates')->select(DB::raw('SUM(stars) as stars'),DB::raw('COUNT(id) as ids'))->where(['eid'=>$element->element_id])->first();
            $e_rate=3;
            if($rate && $rate->ids>0){
                $e_rate=floor($rate->stars/$rate->ids);
            }
            $element->rate=$e_rate;
            $chat=[];
            if($info){
                $chat=DB::table('chat')->where(['from_uid'=>$info->user_id,'to_uid'=>$element->user_id])->orWhere(['to_uid'=>$info->user_id,'from_uid'=>$element->user_id])->get();
                foreach($chat AS $ck=>$cv){
                    $sender_ico=DB::table('user')->select('user_icon')->where('user_id',$cv->sender)->first();

                    $chat[$ck]->icon=$sender_ico->user_icon?$this->getOssPath($sender_ico->user_icon)->oss_path:'';

                }
                
            }
            //dd($chat);

            $element_models=DB::table('oss_item')->whereIn('oss_item_id',explode(',',$element->element_models))->get();
            $element->models=$element_models;
            
            $hasliked=0;
            
            if($info){
                $has_like=DB::table('likes')->select('liked')->where(['like_uid'=>$info->user_id,'like_eid'=>$id])->first();
                if($has_like && $has_like->liked){
                    $hasliked=1;
                }
            }
            $element->liked=$hasliked;
            $cart_info=[];
            $notices=[];

            if($info){
                $notices=$this->getNoticesLists($info->user_id);
                $cart_info=$this->getCart($info->user_id);
            }
            $user_elements=DB::table('element')->select('element_id','element_name','element_cover_id')->where(['user_id'=>$element->user_id,'element_show'=>0])->orderBy('element_id','DESC')->limit(6)->get();
            foreach($user_elements AS $k=>$v){
                $cover=$this->getOssPath($v->element_cover_id)->oss_path;
                $user_elements[$k]->cover=$cover;
            }

            $element->element_price = Tools::trans_currency($element->element_price,$element->element_currency);
            //dd($element);

            return view('nova.model',['user'=>$info,'element'=>$element,'chat'=>$chat,'path'=>$this->oss_base_path,'notices'=>$notices,'cart_info'=>$cart_info,'img_server'=>ApiConf::IMG_URI,'elements'=>$user_elements,'title'=>$element->element_name]);
        }
    public function newmodel($id=0){

        $element=DB::table('element')->where('element_id',$id)->first();
        if(!$element){
            abort(404);
            exit;
        }
        DB::table('element')->where('element_id',$id)->increment('element_views');
        $info=Session::get('userInfo',null);
        $hasBuy=0;
        if($info){
            $myicon=DB::table('user')->select('user_icon')->where('user_id',$info->user_id)->first();
            if($myicon->user_icon==0){
                $info->user_icon='';
            }else{
                $info->user_icon=$this->getOssPath($myicon->user_icon)->oss_path;
            }
            $ckbuy=DB::table('orders')->where(['order_uid'=>$info->user_id,'order_eid'=>$id])->first();
            if($ckbuy){
                $hasBuy=$ckbuy->order_status+1;
            }
        }

        $cover=DB::table('oss_item','width','height')->where('oss_item_id',$element->element_cover_id)->first();

        $element->cover=$cover->oss_path;
        $element->coverw=$cover->width;
        $element->coverh=$cover->height;
        $element->element_description = htmlspecialchars_decode(htmlentities($element->element_description));

        $geometry=DB::table('category')->where(['cate_id'=>$element->element_geometry])->first();
        $element->element_geometry=$geometry->cate_name;
        $user=DB::table('user')->where('user_id',$element->user_id)->first();

        $icon=DB::table('oss_item')->where('oss_item_id',$user->user_icon)->first();
        $user->icon=$icon?ApiConf::IMG_URI.$icon->oss_path:'';
        //$user->works=DB::table('element')->where('user_id',$user->user_id)->count();
        $works=DB::select("SELECT COUNT(*) AS tot FROM element WHERE user_id=".$user->user_id." AND element_show!=2");
        $user->works=$works[0]->tot;
        $user->following=DB::table('following')->where(['from_uid'=>$user->user_id,'followed'=>1])->count();
        $user->follower=DB::table('following')->where(['to_uid'=>$user->user_id,'followed'=>1])->count();
        $user->hasFollow=0;
        $user->isme=0;
        $user->homepage=$user->user_page_id?'/'.$user->user_page_id:'/user/'.$user->user_id;
        $user->homepage= 'http://'.$_SERVER['HTTP_HOST'].$user->homepage;
        if($info){
            $ck_follow=DB::table('following')->where(['from_uid'=>$info->user_id,'to_uid'=>$element->user_id,'followed'=>1])->get();
            if($ck_follow) $user->hasFollow=1;
            $user->isme=$info->user_id==$element->user_id?1:0;
        }
        $element->user=$user;

        //dd($element);
        $element->hasBuy=$hasBuy;
        //DB::connection()->enableQueryLog();
        $pics=DB::table('oss_item')->select('oss_key','oss_path','width','height')->whereIn('oss_item_id',explode(",",$element->element_images))->get();
        $element->pics=$pics;
        $rate=DB::table('rates')->select(DB::raw('SUM(stars) as stars'),DB::raw('COUNT(id) as ids'))->where(['eid'=>$element->element_id])->first();
        $e_rate=3;
        if($rate && $rate->ids>0){
            $e_rate=floor($rate->stars/$rate->ids);
        }
        $element->rate=$e_rate;
        $chat=[];
        if($info){
            $chat=DB::table('chat')->where(['from_uid'=>$info->user_id,'to_uid'=>$element->user_id])->orWhere(['to_uid'=>$info->user_id,'from_uid'=>$element->user_id])->get();
            foreach($chat AS $ck=>$cv){
                $sender_ico=DB::table('user')->select('user_icon')->where('user_id',$cv->sender)->first();

                $chat[$ck]->icon=$sender_ico->user_icon?$this->getOssPath($sender_ico->user_icon)->oss_path:'';

            }

        }
        //dd($chat);

        $element_models=DB::table('oss_item')->whereIn('oss_item_id',explode(',',$element->element_models))->get();
        $element->models=$element_models;

        $hasliked=0;

        if($info){
            $has_like=DB::table('likes')->select('liked')->where(['like_uid'=>$info->user_id,'like_eid'=>$id])->first();
            if($has_like && $has_like->liked){
                $hasliked=1;
            }
        }
        $element->liked=$hasliked;
        $cart_info=[];
        $notices=[];

        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }
        $user_elements=DB::table('element')->select('element_id','element_name','element_cover_id')->where(['user_id'=>$element->user_id,'element_show'=>0])->orderBy('element_id','DESC')->limit(6)->get();
        foreach($user_elements AS $k=>$v){
            $cover=$this->getOssPath($v->element_cover_id)->oss_path;
            $user_elements[$k]->cover=ApiConf::IMG_URI.$cover.'@0o_0l_100w_90q.src';
        }

        $element->relatedElement = $user_elements;
        $element->element_price = Tools::trans_currency($element->element_price,$element->element_currency);
        //dd($element);

        return view('nova.newmodel',['user'=>$info,'element'=>$element,'chat'=>$chat,'path'=>$this->oss_base_path,'notices'=>$notices,'cart_info'=>$cart_info,'img_server'=>ApiConf::IMG_URI,'elements'=>$user_elements,'title'=>$element->element_name]);
    }
	public function view($id=0){
		//DB::connection()->enableQueryLog();
		/*
		$data=DB::table('element AS elem')
			->leftJoin('category AS a1','elem.element_category','=','a1.cate_id')
			->leftJoin('category AS a2','elem.element_style','=','a2.cate_id')
			->leftJoin('category AS a3','elem.element_format','=','a3.cate_id')
			->where('elem.element_id',$id)
			->select("'elem.*','a1.cate_name AS category','a2.cate_name AS style','a3.cate_name AS format'")
			->get();
			*/
		$sql=" select elem.*,u.user_name,a1.cate_name AS category,a2.cate_name AS style,a3.cate_name AS format,a4.cate_name AS level from `element` as `elem` left join `category` as `a1` on `elem`.`element_category` = `a1`.`cate_id` left join `category` as `a2` on `elem`.`element_style` = `a2`.`cate_id` left join `category` as `a3` on `elem`.`element_format` = `a3`.`cate_id` LEFT JOIN category AS a4 ON elem.element_level=a4.cate_id LEFT JOIN user AS u ON elem.user_id=u.user_id where `elem`.`element_id` = ".$id." AND element_isdel=0";
		$data=DB::select($sql);
		if(!$data){
			echo 404;
			exit;
		}
		DB::table('element')->where('element_id',$id)->increment('element_views');
		$data=$data[0];
		$tmp_arr=explode(',',$data->element_images);
		$imgs=DB::table('oss_item')->whereIn('oss_item_id',$tmp_arr)->lists('oss_path');
		$data->images=$imgs;
		$data->animation=$data->element_animation==1?'Yes':'No';
		$data->texture=$data->element_texture==1?'Yes':'No';
		$data->size=sprintf("%.2f",$data->element_size/1024/1024);
		$data->rate=4;
                
		return view('nova.view',['data'=>$data,'path'=>$this->oss_base_path,'title'=>'Element Details']);
	}
    public function verifyCode(){
        $info=Session::get('userInfo',null);
        $code = Input::get('code',NULL);
        $cart_info=[];
        $notices=[];

        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }

        if(!$code){
            exit;
        }

        $data = DB::table('verify_codes')->where(['v_code'=>$code])->first();

        if($data){
            $res = DB::table('user')->where(['user_id'=>$data->v_uid])->update(['user_isvalidate'=>1]);
            if($res){
                return view('home.verifyAccountSuccess',['user'=>$info,'title'=>'verify Successfully!','notices'=>$notices,'cart_info'=>$cart_info]);
            }
        }
        /*
        if($code =='251f58d2ee3995a35bdfddbb3148fe19'){
            DB::table('user')->where(['user_id'=>$data->v_uid])->update(['user_isvalidate'=>1]);
            return view('home.verifyAccountSuccess',['user'=>$info,'title'=>'verify Successfully!','notices'=>$notices,'cart_info'=>$cart_info]);
        }
        */
    }
    public function modelshow($id = 0){

        $m = DB::table('element')->select('element_model')->where(['element_id'=>$id])->first();

        if(!$m) exit;
        $model = DB::table('model_shows')->where(['id'=>$m->element_model])->first();
        $paths = explode(";",$model->model_path);
        $model->model_path = $paths;
        $tpls = ['obj'=>'nova.modelshow_obj','fbx'=>'nova.modelshow_fbx','stl'=>'nova.modelshow_stl'];
        $tpl = $tpls[$model->model_format];
        return view($tpl,['model'=>$model]);
    }
}
