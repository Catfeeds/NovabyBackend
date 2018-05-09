<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use App\libs\Tools;
use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
use DB;
use Mail;

class ProjectsController extends Controller
{

    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    public function create($id=0){
        $isedit = 0;
        if($id>0){
            $ck_status = DB::table('projects')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();
            if($ck_status && ($ck_status->prj_status<=1 )){
                $isedit = $id;

            }else{
                abort(404);
                exit;
            }
        }


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $cates=DB::table('category')->where('cate_pid',0)->get();

        foreach ($cates as $k => $v) {
            $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
            $cates[$k]->sub=$sub_data;
        }

        $cateDatas=(object)[];
        foreach($cates AS $k=>$v){
            $attr=$v->cate_name;
            if(isset($_COOKIE['lang']) && ($attr=='category' || $attr=='Geometry')){
                foreach($v->sub AS $sk=>$sv){

                    $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                }
            }
            $cateDatas->$attr=$v->sub;
        }
//dd($cateDatas);
        return view('projects.new',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'cates'=>$cateDatas,'isedit'=>$isedit,'title'=>'New Project']);
    }
    public function detail($id = 0){
        if($id<=0){
            abort(404);
            return;
        }

        $proj=DB::table('projects')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        if(!$proj){
            abort(404);
            return;
        }

        $proj->isview = $proj->prj_status>3 ? 1 : 0;


        $prj_photos = [];
        $photo_ids = explode(',',$proj->prj_photos);
        $photos = DB::table('oss_item')->whereIN('oss_item_id',$photo_ids)->get();
        foreach($photos AS $K=>$v){
            $prj_photos[]=ApiConf::IMG_URI.$v->oss_path;
        }
        $proj->prj_photos =$prj_photos;

        $industry=DB::table('category')->where('cate_id',$proj->prj_industry)->first();
        $cate=DB::table('category')->where('cate_id',$proj->prj_cate)->first();
        $format=DB::table('category')->where('cate_id',$proj->prj_format)->first();

        $proj->prj_industry =$industry->cate_name;
        $proj->prj_cate =$cate->cate_name;
        $proj->prj_format =$format->cate_name;
        $proj->hasBids=[];
        $bids = DB::table('biddings')->where(['bid_pid'=>$id])->get();

        foreach($bids AS $k=>$v){
            $_albums = [];
            $cover = '';
            $albums=DB::table('element')->select('element_images','element_cover_id')->where(['user_id'=>$v->bid_uid])->OrderBy('element_id','DESC')->limit(1)->first();

            $_imgs = explode(",", $albums->element_images);

            $imgs = DB::table('oss_item')->select('oss_path','oss_item_id')->whereIn('oss_item_id',$_imgs)->get();


            foreach($imgs AS $ki=>$vi){
                $_albums[]=ApiConf::IMG_URI.$vi->oss_path;
                if($vi->oss_item_id==$albums->element_cover_id){
                    $cover=ApiConf::IMG_URI.$vi->oss_path;
                }
            }

            $bids[$k]->album=$_albums;
            $bids[$k]->cover=$cover;
            $bids[$k]->name=$v->bid_name;
            $bids[$k]->albums=implode(";",$_albums);
            $_u = DB::table('user')->select('user_icon')->where(['user_id'=>$v->bid_uid])->first();
            if($_u->user_icon==0){
                $_icon = '/images/defaultuser.png';
            }else{
                $_icon = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_u->user_icon])->first();
                $_icon = ApiConf::IMG_URI.$_icon->oss_path.'@0o_0l_100w_90q.src';

            }
            $bids[$k]->icon=$_icon;

        }



        $proj->hasBids = $bids;
        $proj->lefttime= Tools::leftTime($proj->prj_end_time);


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $proj->nav = $this->navHtml($id,1,$proj->prj_status);

        return view('projects.detail',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$proj,'title'=>'Project Detail']);

    }
    public function save(Request $req){
        $molders = [27,40];
        //$molders = [10001,10000];
        $molders_email = DB::table('user')->select('user_email','user_id')->whereIn('user_id',$molders)->get();
        $name = $req->get('name');
        $industry=$req->get('industry');
        $category=$req->get('category');
        $format=$req->get('format');
        $accuracy=$req->get('accuracy');
        $texture=$req->get('texture');
        $rigged=$req->get('rigged');
        $ref_photos=$req->get('ref_photos');
        $desc=$req->get('desc');
        $country=$req->get('country');
        $areacode=$req->get('areacode');
        $period=$req->get('period');
        $tel=$req->get('tel');
        $draft=$req->get('draft');
        $isedit=$req->get('isedit');
        $photos_ids=[];
        foreach($ref_photos AS $k=>$v){
            if($v['id']==0) {
                $insert_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'elements',
                        'oss_path' => $v['name'],
                        'oss_item_uid' => $this->info->user_id,
                        'width' => $v['size']['width'],
                        'height' => $v['size']['height'],
                    ]
                );

                $photos_ids[]=$insert_id;
            }else{
                $photos_ids[]=$v['id'];
                //if ($v['iscover']) {
                  //  $cover_id = $v['id'];
                //}
            }


        }
        $photos_ids = implode(",",$photos_ids);
        $_data=[
            'prj_uid'=>$this->info->user_id,
            'prj_name'=>$name,
            'prj_industry'=>$industry,
            'prj_cate'=>$category,
            'prj_format'=>$format,
            'prj_acc'=>$accuracy,
            'prj_texture'=>$texture,
            'prj_rigged'=>$rigged,
            'prj_desc'=>$desc,
            'prj_contact_country'=>$country,
            'prj_contact_code'=>$areacode,
            'prj_period'=>$period,
            'prj_contact_tel'=>$tel,
            'prj_pubtime'=>time(),
            'prj_photos'=>$photos_ids,
            'prj_status'=>$draft,
            'prj_start_time'=>time(),
            'prj_end_time'=>time()+$period*3600,
        ];
        if($isedit){
            DB::table('projects')->where(['prj_id'=>$isedit])->update($_data);
            $res = $isedit;
            $e=1;
        }else{
            $res = DB::table('projects')->insertGetId($_data);
            $e=0;
            $invite_url='https://'.$_SERVER['HTTP_HOST'].'/task/bid/'.$res;
            $subject='Novaby为您推荐了一个单子';
            foreach($molders_email AS $k=>$v){
                $email = $v->user_email;
                $res1 = Mail::send('emailtpl.invite_modler1',['url'=>$invite_url, 'name'=>'Novaby'],function($message) use ($email,$subject){
                    $to = $email;
                    $message ->to($to)->subject($subject);
                });
                DB::table('invite_modlers')->insert(
                    [
                        'uid'=>$v->user_id,
                        'email'=>$v->user_email,
                        'pid'=>$res,
                        'sendtime'=>time(),
                        'ignore'=>0,
                    ]);

            }
        }

        return response()->json(['code'=>'200','data'=>$res,'e'=>$e]);



    }
    public function trialpay($id = 0){

        $trial_info = DB::table('trial')->where(['trial_pid'=>$id])->first();
       // dd($trial_info);
        $trial_info->nav = $this->navHtml($id,1,1);
        if($trial_info->trial_payway==1){

            return response()->redirectToRoute('trialpay_paypal',$trial_info->trial_pid);
        }



        $cart_info=[];
        $notices=[];
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.trialpay',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>[],'title'=>'Project trial']);

    }
    public function trial($id = 0){
        if($id<=0){
            abort(404);
            exit;
        }
        $ck_status = DB::table('trial')->where(['trial_pid'=>$id])->first();
        if($ck_status){
            if(!$ck_status->trial_haspay){
                //return response()->redirectToRoute('trialpay',['id'=>$id]);
            }
            //exit;
        }

        $proj=DB::table('projects')->leftJoin('trial','projects.prj_id','=','trial.trial_pid')->select('projects.prj_name','projects.prj_id','projects.prj_status','projects.prj_isskip','trial.*')->where(['projects.prj_id'=>$id,'projects.prj_uid'=>$this->info->user_id])->first();
        $bidding_modler = DB::table('biddings')->select('biddings.*','user.user_icon')->leftJoin('user','user.user_id','=','biddings.bid_uid')->where(['biddings.bid_pid'=>$id])->get();
        $_select_modelers = [];
        $_select_modeler = DB::table('trial')->select('trial_modlers')->where(['trial_pid'=>$id,'trial_haspay'=>1])->first();
        if($_select_modeler){
            $_select_modelers = explode(",",$_select_modeler->trial_modlers);
        }
        foreach($bidding_modler AS $k=>$v){
            $_album = DB::table('element')->select('element_images','element_cover_id')->where(['user_id'=>$v->bid_uid])->orderBy('element_id','DESC')->first();
            $_albums = explode(',',$_album->element_images);
            $_album_items = DB::table('oss_item')->select('oss_path','oss_item_id')->whereIn('oss_item_id',$_albums)->get();
            $_album_pics = [];
            foreach($_album_items AS $k1=>$v1){
                if($v1->oss_item_id==$_album->element_cover_id){
                    $_cover = ApiConf::IMG_URI.$v1->oss_path;

                }
                $_album_pics[] = ApiConf::IMG_URI.$v1->oss_path;

            }

            $bidding_modler[$k]->Album=[
                'cover'=>$_cover,
                'pics'=>$_album_pics,
            ];
            //$bidding_modler[$k]->Trail_photos=[];
            if($v->user_icon==0){
                $_icon = '/images/defaultuser.png';
            }else{
                $_icon = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$v->user_icon])->first();
                $_icon = ApiConf::IMG_URI.$_icon->oss_path.'@0o_0l_100w_90q.src';

            }
            //$_ck_pay=DB::table('trial')->where(['trial_pid'=>$id,'trial_uid'=>$v->bid_uid,'trial_haspay'=>1])->count();
            if(in_array($v->bid_uid,$_select_modelers)){
                $bidding_modler[$k]->choose = 1;
            }else{
                $bidding_modler[$k]->choose = 0;
            }
            $bidding_modler[$k]->icon=$_icon;

        }



        $proj->bid_modlers = $bidding_modler;
        $coupon = 0;
        $_coupon = DB::table('wallet')->select('coupon')->where(['uid'=>$this->info->user_id])->first();
        if($_coupon && $_coupon->coupon>0){
            $coupon=$_coupon->coupon;
        }
        $proj->coupon=$coupon;
        $proj->trial_paid = DB::table('trial')->where(['trial_pid'=>$id,'trial_haspay'=>1])->count();
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        $proj->isview = $proj->trial_haspay;

        if($proj->prj_isskip==2){
            $proj->isview=1;
        }


        if($proj->isview && $proj->prj_isskip==!2){
            $_attach = DB::table('oss_item')->where(['oss_item_id'=>$proj->trial_attch])->first();
            $_attach_name = $_attach->oss_path;
            $_attach_url = ApiConf::IMG_URI.$_attach->oss_path;
            $_names = explode("/",$_attach_url);
            $_attach_name = $_names[count($_names)-1];
            $proj->attach =(object)['name'=>$_attach_name,'url'=>$_attach_url];
        }else{

        }

        $proj->nav = $this->navHtml($id,2,$proj->prj_status);
        //$proj->isview=0;
//dd($proj);

        return view('projects.trial',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$proj,'title'=>'Project trial']);
    }
    public function trialpub(Request $req){
        $pm = $req->get("pm");
        $trial_pid = $req->get('pid');
        $ch_trail_data = DB::table("trial")->where(['trial_pid'=>$trial_pid])->first();
        $trial_attachment= $req->get('trial_attachment');
        $trial_desc = $req->get('trial_desc');
        $trial_fee = $req->get('trial_fee');
        $trial_time = $req->get('trial_time');
        $trial_modlers_arr = $req->get('trial_modlers');
        //dd($trial_modlers_arr);
        $trial_modlers=implode(",", $trial_modlers_arr);

        $tot = count($trial_modlers_arr)*$trial_fee;
        $_prj_name = DB::table('projects')->select('prj_name')->where(['prj_id'=>$trial_pid])->first();
        $prj_name = $_prj_name->prj_name;
        //$modlers = DB::table('user')->select('user_email')->whereIn('user_id',$trial_modlers_arr)->get();

        if($pm==2){//没有验证邮箱，不能使用coupon
            $ck_account_valid= DB::table('user')->select('user_isvalidate')->where(['user_id'=>$this->info->user_id])->first();
            if(!$ck_account_valid || $ck_account_valid->user_isvalidate!=1){
                return response()->json(['code'=>200,'data'=>-2]);
            }
            $coupon = 0;
            $_coupon = DB::table('wallet')->select('coupon')->where(['uid'=>$this->info->user_id])->first();
            if($_coupon && $_coupon->coupon>0){
                $coupon=$_coupon->coupon;
            }
            if($tot>$coupon){
                return response()->json(['code'=>200,'data'=>-1]);
                exit;
            }

        }



        $trial_payway=$pm;
        if(!$ch_trail_data){//插入新的





        $insert_id = DB::table('oss_item')->insertGetId(
            [
                'oss_key' => 'attachment',
                'oss_path' => $trial_attachment['name'],
                'oss_item_uid' => $this->info->user_id,
                'size'=>$trial_attachment['name'],
            ]
        );
        $_data=[
            'trial_pid'=>$trial_pid,
            'trial_uid'=>$this->info->user_id,
            'trial_attch'=>$insert_id,
            'trial_desc'=>$trial_desc,
            'trial_fee'=>$trial_fee,
            'trial_time'=>$trial_time,
            'trial_modlers'=>$trial_modlers,
            'trial_tot'=>$tot,
            'trial_pubtime'=>time(),
            'trial_payway'=>$trial_payway,


        ];
        $res = DB::table('trial')->insertGetId($_data);
        }else{//更新老数据
            if($trial_attachment['id']>0){
                $att_id=$trial_attachment['id'];
            }else{
                $att_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'attachment',
                        'oss_path' => $trial_attachment['name'],
                        'oss_item_uid' => $this->info->user_id,
                        'size'=>$trial_attachment['name'],
                    ]
                );
            }
            $_data=[
                'trial_pid'=>$trial_pid,
                'trial_uid'=>$this->info->user_id,
                'trial_attch'=>$att_id,
                'trial_desc'=>$trial_desc,
                'trial_fee'=>$trial_fee,
                'trial_time'=>$trial_time,
                'trial_modlers'=>$trial_modlers,
                'trial_tot'=>$tot,
                'trial_pubtime'=>time(),
                'trial_payway'=>$trial_payway,


            ];
            $r = DB::table('trial')->where(['trial_pid'=>$trial_pid])->update($_data);
            $res = $trial_pid;

        }
        if($pm==2){
            DB::table('wallet')->where(['uid'=>$this->info->user_id])->decrement('coupon',$tot);
            $_wallet_log_data=[
                'type'=>6,
                'income'=>0,
                'amount'=>$tot,
                'method'=>1,
                'uid'=>$this->info->user_id,
                'ctime'=>time(),
            ];
            DB::table('wallet_logs')->insert($_wallet_log_data);
            DB::table('trial')->where(['trial_pid'=>$trial_pid])->update(['trial_payway'=>2,'trial_haspay'=>1,'trial_paytime'=>time()]);
            DB::table('projects')->where(['prj_id'=>$trial_pid])->update(['prj_status'=>4]);
            $subject='You are invited to have a trial on Novaby with '.$trial_fee.' USD!';
            $invite_url='https://'.$_SERVER['HTTP_HOST'].'/task/trial/'.$trial_pid;
            foreach($trial_modlers_arr AS $k1=>$v1){
                $email = DB::table('user')->select('user_email')->where(['user_id'=>$v1])->first();
                $email = $email->user_email;
                Mail::send('emailtpl.invite_modler',['url'=>$invite_url, 'name'=>'Novaby','price'=>$trial_fee,'prj_name'=>$prj_name],function($message) use ($email,$subject){
                    $to = $email;
                    $message ->to($to)->subject($subject);
                });
            }

        }
        if($res){
            return response()->json(['code'=>200,'data'=>$trial_pid]);
        }
    }
    public function trialtask($id=0){
        if($id<=0){
            abort(404);
            exit;
        }
        $trial = DB::table('trial')->leftJoin('projects','trial.trial_pid','=','projects.prj_id')->leftJoin('category','projects.prj_industry','=','category.cate_id')->select('trial.*','projects.prj_name','projects.prj_id','projects.prj_industry','projects.prj_end_time','projects.prj_start_time','projects.prj_start_time','projects.prj_status','category.cate_name')->where(['trial.trial_pid'=>$id])->first();
        if(!$trial){
            abort(404);
            exit;
        }
        $trial_modlers = explode("," ,$trial->trial_modlers);
        $trial_modlers_info = DB::table('user')->select('user_name','user_lastname','user_id')->whereIn('user_id',$trial_modlers)->get();
        $has_submit = 0;
        foreach($trial_modlers_info AS $k=>$v){
                $nick_name = DB::table('biddings')->select("bid_name")->where(['bid_uid'=>$v->user_id,'bid_pid'=>$id])->first();
                $trial_modlers_info[$k]->name=$nick_name->bid_name;
                $trial_submit = DB::table('trialsubmit')->where(['ts_uid'=>$v->user_id,'ts_pid'=>$id])->first();
                $_m_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$v->user_id])->first();
                if($_m_icon->user_icon==0){
                    $trial_modlers_info[$k]->icon = '/images/defaultuser.png';
                }else{
                    $_oss_path = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_m_icon->user_icon])->first();
                    $trial_modlers_info[$k]->icon = ApiConf::IMG_URI.$_oss_path->oss_path.'@0o_0l_100w_90q.src';
                }
                if($trial_submit){
                    $has_submit++;
                    $ps_arr = explode(',',$trial_submit->ts_photos);

                    $ps_img = DB::table('oss_item')->select('oss_path')->whereIn('oss_item_id', $ps_arr)->get();
                    $ps_imgs = '';
                    foreach($ps_img AS $k1=>$v1){
                        if($k1==0){
                            $trial_submit->cover = ApiConf::IMG_URI.$v1->oss_path;
                        }
                        $ps_imgs .= ApiConf::IMG_URI.$v1->oss_path.";";
                    }
                    $ps_imgs=trim($ps_imgs,";");


                    $trial_submit->photos =$ps_imgs;


                   $trial_modlers_info[$k]->submit=$trial_submit;
                   $trial_submit->ts_rate = $trial_submit->ts_rate==null ? 0 : $trial_submit->ts_rate;
                }else{

                   $trial_modlers_info[$k]->submit=[];
                }
                $_status = DB::table('invite_modlers')->where(['uid'=>$v->user_id,'pid'=>$id])->first();
                if(!$_status){
                    $status = 0;
                }else{
                    $status = $_status->ignore;
                }
            $trial_modlers_info[$k]->status=$status;
            //$trial_modlers_info[$k]->submit=[];
        }
        $trial->trial_modlers_info=$trial_modlers_info;

        $trial->has_submit=$has_submit;
        $trial->duetime=Tools::leftTime($trial->trial_pubtime+$trial->trial_time*3600);
        $pay_methods = [1=>'paypal',2=>'coupon',3=>'alipay',4=>'wechat'];
        $trial->trial_payway=$pay_methods[$trial->trial_payway];
        $trial->nav = $this->navHtml($id,2,$trial->prj_status);


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.trialtask',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$trial,'title'=>'Project Detail']);

    }
    public function directchoicepay($id=0){
        if($id<=0) exit;
        $prj = DB::table('projects')->where(['prj_id'=>$id])->first();
        if($prj->prj_status!=3){
            abort(404);
            return;
        }
        $bidding = DB::table('biddings')->where(['bid_pid'=>$id])->get();

        //$trial_modlers = explode("," ,$trial->trial_modlers);
        foreach($bidding AS $k=>$v){
            $bidding[$k]->name = $v->bid_name;
            $_m_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$v->bid_uid])->first();

            if($_m_icon->user_icon==0){
                $bidding[$k]->icon = '/images/defaultuser.png';
            }else{
                $_oss_path = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_m_icon->user_icon])->first();
                $bidding[$k]->icon = ApiConf::IMG_URI.$_oss_path->oss_path.'@0o_0l_100w_90q.src';
            }
            $trial_submit = DB::table('trialsubmit')->where(['ts_uid'=>$v->bid_uid,'ts_pid'=>$id])->first();
            if($trial_submit){

                $ps_arr = explode(',',$trial_submit->ts_photos);
                $ps_img = DB::table('oss_item')->whereIn('oss_item_id',$ps_arr)->get();
                $_photos =[];
                foreach($ps_img AS $ki=>$vi){
                    $_photos[]=ApiConf::IMG_URI.$vi->oss_path;
                }
                $_photos=implode(";",$_photos);
                $trial_submit->photos =$_photos;

                $trial_submit->cover = ApiConf::IMG_URI.$ps_img[0]->oss_path.'@0o_0l_300w_90q.src';
                $bidinfo = DB::table('biddings')->where(['bid_pid'=>$id,'bid_uid'=>$v->user_id])->first();
                $trial_submit->bidinfo=$bidinfo;
                $trial_submit->finishtime=time()+$bidinfo->bid_cost_time*3600;

                $bidding[$k]->submit=$trial_submit;
            }else{
                $bidding[$k]->submit=[];
            }
            $bidding[$k]->album='/images/logo.jpg';
        }
        $coupon = 0;
        $_coupon = DB::table('wallet')->select('coupon')->where(['uid'=>$this->info->user_id])->first();
        if($_coupon && $_coupon->coupon>0){
            $coupon=$_coupon->coupon;
        }
        $prj->coupon=$coupon;
        $prj->bidding = $bidding;
        $prj->nav = $this->navHtml($id,3,$prj->prj_status);
        //dd($prj);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.directchoicepay',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$prj,'title'=>'Pay for build']);

    }
    public function choicepay($id = 0){
        if($id<=0) exit;
        $prj = DB::table('projects')->select('prj_status')->where(['prj_id'=>$id])->first();
        if($prj->prj_status==3){
            return  redirect('/projects/directpay/'.$id);

        }

        $trial = DB::table('trial')->leftJoin('projects','trial.trial_pid','=','projects.prj_id')->select('trial.*','projects.prj_id','projects.prj_name','projects.prj_industry','projects.prj_status')->where(['trial.trial_pid'=>$id])->first();

        $trial_modlers = explode("," ,$trial->trial_modlers);

        $trial_modlers_info = DB::table('user')->select('user_name','user_lastname','user_id')->whereIn('user_id',$trial_modlers)->get();

        foreach($trial_modlers_info AS $k=>$v){
            $nick_name = DB::table('biddings')->select("bid_name")->where(['bid_uid'=>$v->user_id,'bid_pid'=>$id])->first();
            $trial_modlers_info[$k]->name=$nick_name->bid_name;
            $trial_submit = DB::table('trialsubmit')->where(['ts_uid'=>$v->user_id,'ts_pid'=>$id])->first();
            $_m_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$v->user_id])->first();
            if($_m_icon->user_icon==0){
                $trial_modlers_info[$k]->icon = '/images/defaultuser.png';
            }else{
                $_oss_path = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_m_icon->user_icon])->first();
                $trial_modlers_info[$k]->icon = ApiConf::IMG_URI.$_oss_path->oss_path.'@0o_0l_100w_90q.src';
            }
            //$nick_name = DB::table('biddings')->select("bid_name")->where(['bid_uid'=>$v->user_id,'bid_pid'=>$id])->first();
            //$trial_modlers_info[$k]->name=$nick_name->bid_name;
            $trial_submit = DB::table('trialsubmit')->where(['ts_uid'=>$v->user_id,'ts_pid'=>$id])->first();
            if($trial_submit){

                $ps_arr = explode(',',$trial_submit->ts_photos);

                $ps_img = DB::table('oss_item')->whereIn('oss_item_id',$ps_arr)->get();

                $_photos =[];
                foreach($ps_img AS $ki=>$vi){
                    $_photos[]=ApiConf::IMG_URI.$vi->oss_path;
                }
                $_photos=implode(";",$_photos);
                $trial_submit->photos =$_photos;

                $trial_submit->cover = ApiConf::IMG_URI.$ps_img[0]->oss_path.'@0o_0l_300w_90q.src';
                $bidinfo = DB::table('biddings')->where(['bid_pid'=>$id,'bid_uid'=>$v->user_id])->first();
                $trial_submit->bidinfo=$bidinfo;
                $trial_submit->finishtime=time()+$bidinfo->bid_cost_time*3600;

                $trial_modlers_info[$k]->submit=$trial_submit;
            }else{
                $trial_modlers_info[$k]->submit=[];
            }
            $trial_modlers_info[$k]->album='/images/logo.jpg';
        }


        $trial->trial_modlers_info=$trial_modlers_info;

        $coupon = 0;
        $_coupon = DB::table('wallet')->select('coupon')->where(['uid'=>$this->info->user_id])->first();
        if($_coupon && $_coupon->coupon>0){
            $coupon=$_coupon->coupon;
        }
        $trial->coupon=$coupon;
        $_ck_pay = DB::table('build_pay')->where(['pid'=>$id])->first();

        if($_ck_pay && $_ck_pay->has_pay){
            $_paid = 1;
        }else{
            $_paid = 0;

        }



        $trial->paid=$_paid;
        $trial->isview = $_paid;
        $trial->nav = $this->navHtml($id,3,$trial->prj_status);
        //print_r($trial_modlers_info);

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.choicepay',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$trial,'title'=>'Project Detail']);

    }
    public function all($id = 0){


        $condation = ['prj_status>0 AND prj_status<10',
                        'prj_status=10',
                        'prj_status=0'
        ];

        $sql="SELECT * FROM projects WHERE prj_uid=".$this->info->user_id." AND ".$condation[$id]." ORDER BY prj_id DESC";
        $lists = DB::select($sql);
        foreach($lists AS $k=>$v){
            $photos = explode(",",$v->prj_photos);
            $cover =$photos[0];
            $cover_img = DB::table('oss_item')->where(['oss_item_id'=>$cover])->first();


            $lists[$k]->cover = ApiConf::IMG_URI.$cover_img->oss_path;
            if($lists[$k]->prj_status==2)//有报价
            {
                $biddings = DB::table('biddings')->where(['biddings.bid_pid'=>$v->prj_id])->count();
                $lists[$k]->biddings=$biddings;
            }
            if($lists[$k]->prj_status==3)//测试中
            {
                $biddings = DB::table('biddings')->leftJoin('user','user.user_id','=','biddings.bid_uid')->select('user.user_name','user.user_lastname')->where(['biddings.bid_pid'=>$v->prj_id,'biddings.bid_accept'=>1])->get();
                $lists[$k]->bid_user=$biddings;
            }
            if($lists[$k]->prj_status==5){

                $pay_days = DB::table('build_pay')->select('pay_time')->where(['pid'=>$v->prj_id])->first();
                if($pay_days){
                     $lists[$k]->proj_days= ceil((time()-$pay_days->pay_time)/(3600*24));
                }else{
                    $lists[$k]->proj_days=1;
                }
                //echo $lists[$k]->proj_days;


            }
            $lists[$k]->prj_lefttime=Tools::leftTime($v->prj_end_time);
        }


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.lists',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$lists,'cate'=>$id,'title'=>'Project Detail']);

    }
    public function build($id=0){
        if($id<=0) exit;

        $bid_info = DB::table('biddings')->leftJoin('projects','biddings.bid_pid','=','projects.prj_id')->select('projects.prj_name','projects.prj_status','projects.prj_final_modler','biddings.*')->where(['biddings.bid_pid'=>$id])->first();
        $pay_info = DB::table('build_pay')->where(['pid'=>$id])->first();
        $due_time = $pay_info->pay_time+$bid_info->bid_cost_time*3600;
        $due_day = date('Y-m-d H:i:s',$due_time);
        $start_time=$pay_info->pay_time;

        $today= time();
        $lists = [];
        $yesterday = date('Y-m-d',$pay_info->op_time);
        //echo date('Y-m-d H:i:s',$start_time);
        //echo $due_day;
        $thismonth_days = date("t");
        $this_day=intval(date('d',$start_time));
        //echo $this_day;
        //echo $thismonth_days;


        $due_days =intval(date('d',$due_time));
        //echo $this_day;
        //echo "<br/>";
        //echo $due_days;
        $_mstart = date("m",$start_time);
        $_mend = date("m",$due_time);
        if($_mstart==$_mend){
            $_time1 = $start_time;
            $_time2 = $due_time;
        }else{
            $_time1 = time();
            $_time2 = $due_time;
        }
        $range_days = range($this_day,$due_days);
        $range_days = [];

        $curr = $_time1;

        while($curr <= $_time2){
            //echo $_d = date('Y-m-d H:i:s',$curr);
            //echo "-------\n".date("d",$curr);

           $range_days[]=date("d",$curr);
            $curr += 3600*24;
        }

        //while()

        //print_r($range_days);
        $range_days = Tools::days($start_time,$due_time,date('Y-m'));
        $rdays='[';
        //dd(implode(",",$range_days));
        $rdays .= implode(",",$range_days);
        $rdays.=']';



        $tmp = $start_time;
        $i=0;
        /*
        $bid_info = DB::table('biddings')->leftJoin('projects','biddings.bid_pid','=','projects.prj_id')->select('projects.prj_name','projects.prj_uid','projects.prj_final_modler','biddings.*')->where(['biddings.bid_pid'=>$id,'biddings.bid_uid'=>$this->info->user_id])->first();
        //$bid_info = DB::table('biddings')->leftJoin('projects','biddings.bid_pid','=','projects.prj_id')->select('projects.prj_name','biddings.*')->where(['biddings.bid_pid'=>$id])->first();

        $pay_info = DB::table('build_pay')->where(['pid'=>$id])->first();
        $due_time = $pay_info->pay_time+$bid_info->bid_cost_time*3600;
        $due_day = date('Y-m-d H:i:s',$due_time);
        $start_time=$pay_info->pay_time;
        $today= time();
        $lists = [];
        $yesterday = date('Y-m-d',$pay_info->op_time);
        //echo date('Y-m-d H:i:s',$start_time);
        //echo $due_day;
        $thismonth_days = date("t");
        $this_day=intval(date('d',$start_time));
        $range_days = range($this_day,$thismonth_days);
        $rdays='[';
        //dd(implode(",",$range_days));
        $rdays .= implode(",",$range_days);
        $rdays.=']';

        $tmp = $start_time;
        $i=0;
        */
        $final = 0;
        $final_url = '';
        $final_time='';
        $rlists=[];
        while($tmp<=$today){
            $i++;
            $_endday=$start_time+(3600*24*$i);
            $prj_photos=[];
            $this_day_works = [];
            $this_day_data_sql = "SELECT * FROM builddaly WHERE bd_pid=$id AND bd_pubtime>'".$tmp."' AND bd_pubtime<='".$_endday."'";
            if($i==1){
                $this_day_works['day']='start day';
            }else{
                $this_day_works['day']=$i.' day';
            }
            $att_name= 'the '.$i.' day\'s wrok.';
            $_data = DB::select($this_day_data_sql);

            if($_data){

                $_photo_ids =explode(",",$_data[0]->bd_photos);
                $_photos = DB::table('oss_item')->whereIN('oss_item_id',$_photo_ids)->get();
                foreach($_photos AS $k1=>$v1){
                    $prj_photos[]=ApiConf::IMG_URI.$v1->oss_path;
                }
                $_data[0]->photos =$prj_photos;
                $_attach = DB::table('oss_item','size')->where('oss_item_id',$_data[0]->bd_attachment)->first();
                $_attach_info = explode('.',$_attach->oss_path);
                $_data[0]->works=[
                    'url'=>ApiConf::IMG_URI.'/'.$_attach->oss_path,
                    'name'=>$att_name.$_attach_info[count($_attach_info)-1],
                    'size'=>number_format($_attach->size/1024/1024,2),

                ];
                if($_data[0]->bd_final==1){
                    $final=1;
                    $final_url=ApiConf::IMG_URI.'/'.$_attach->oss_path;
                    $final_time=$_data[0]->bd_pubtime;
                }
                $this_day_works['data']=$_data[0];
            }else{
                $this_day_works['data']=[];
            }
            $tmp=$start_time+(3600*24*$i);
            $lists[]=$this_day_works;
            $rlists = array_reverse($lists);



        }

        $data = (object)[];
        $data->lists=$rlists;
        $data->endtime = $due_time;
        $data->starttime = $start_time;

        $data->lefttime=Tools::leftTime($due_time,$start_time);
        $data->prj_id=$id;
        $data->prj_name=$bid_info->prj_name;
        $data->modler = $bid_info->bid_name;
        $chat = DB::table('build_chat')->where(['prj_id'=>$id])->get();
        $user_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$this->info->user_id])->first();
        if($user_icon->user_icon==0){
            $icon = '/images/defaultuser.png';
        }else{
            $_icon=DB::table('oss_item')->where(['oss_item_id'=>$user_icon->user_icon])->first();
            $icon = ApiConf::IMG_URI.$_icon->oss_path;
        }
        $user_icon_1 = DB::table('user')->select('user_icon')->where(['user_id'=>$bid_info->prj_final_modler])->first();

        if($user_icon_1->user_icon==0){
            $icon_1 = '/images/defaultuser.png';
        }else{
            $_icon_1=DB::table('oss_item')->where(['oss_item_id'=>$user_icon_1->user_icon])->first();
            $icon_1 = ApiConf::IMG_URI.$_icon_1->oss_path.'@0o_0l_300w_90q.src';
        }

        foreach($chat AS $k=>$v){
            if($v->flag==0){
                $chat[$k]->icon=$icon;
            }else{
                $chat[$k]->icon=$icon_1;
            }
        }
        $data->chat = $chat;
        $data->start_str = date('M Y',$data->starttime);
        $data->start_str1 = date('Y-m',$data->starttime);

        $data->range_days= $rdays;

        $data->has_final = $final;
        $data->final_attach = $final_url;
        $data->final_time= $final_time;
        $_user = DB::table('user')->select('user_icon')->where(['user_id'=>$this->info->user_id])->first();
        if($_user->user_icon==0){
            $_icon = 'images/logo.jpg';
        }else{
            $_icon_path =DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_user->user_icon])->first();
            $_icon = ApiConf::IMG_URI.$_icon_path->oss_path.'@0o_0l_300w_90q.src';
        }
        $data->user_icon = $_icon;
        $data->nav = $this->navHtml($id,4,$bid_info->prj_status);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.build',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'Project trial']);

    }
    private function time_differ($start,$end){
        $d1 = strtotime($start);
        $d2 = strtotime($end);
        $days = round(($d2-$d1)/3600/24);
        return $days;
    }
    public function edit(Request $req){
        $id = intval($req->get('id'));
        $data = DB::table('projects')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        $photo_ids = explode(',', $data->prj_photos);
        $photos = DB::table('oss_item')->whereIN('oss_item_id',$photo_ids)->get();

        foreach($photos AS $K=>$v){

            $arr['size'] = ['width'=>$v->width, 'height'=>$v->height];
            $arr['name'] = $v->oss_path;
            $arr['id'] = $v->oss_item_id;
            $serverImages[] = $arr;
            $prj_photos[]=$arr;
        }
        $data->prj_photos =$prj_photos;
        return response()->json(['code'=>'200','data'=>$data,'error'=>0]);
    }
    public function getTrialInfo(Request $req){
        $id= $req->get('id');
        if($id<=0) exit;
        $data = DB::table('trial')->where(['trial_pid'=>$id])->first();
        if($data){
            $choose_molder = explode(",",$data->trial_modlers);
            $data->trial_modlers=$choose_molder;
            $attch = DB::table('oss_item')->where(['oss_item_id'=>$data->trial_attch])->first();
            $file_names= explode("/",$attch->oss_path);
            $file_name = $file_names[count($file_names)-1];
            $data->attch=['name'=>$file_name,'size'=>$attch->size,'id'=>$attch->oss_item_id];


            return response()->json(['code'=>200,'error'=>0,'data'=>$data]);
        }
    }
    public function pubsuccess($id=0){

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.pubsuccess',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'prjid'=>$id,'title'=>'Project publish successfully']);

    }
    public function skip($id=0){
        if($id<=0) exit;
        $data = DB::table('projects')->where(['prj_id'=>$id])->first();
        $data->nav = $this->navHtml($id,3,$data->prj_status);

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.skip',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'prjid'=>$id,'title'=>'Project skip','data'=>$data]);

    }
    public function payforbuild(Request $req)
    {

        $pid = $req->get('pid');
        $user_id = $req->get('uid');
        $pay_method = $req->get('pay_method');
        $ck_data = DB::table('build_pay')->where(['pid' => $pid])->first();
        $_price = DB::table('biddings')->select('bid_price')->where(['bid_uid' => $user_id, 'bid_pid' => $pid])->first();

        $tot = $_price->bid_price;
        $user_mail = DB::table('user')->select('user_email')->where(['user_id'=>$user_id])->first();
        $user_mail=$user_mail->user_email;
        $pm = $pay_method;
        if ($pm == 2) {//没有验证邮箱，不能使用coupon
            $ck_account_valid = DB::table('user')->select('user_isvalidate')->where(['user_id' => $this->info->user_id])->first();
            if (!$ck_account_valid || $ck_account_valid->user_isvalidate != 1) {
                return response()->json(['code' => 200, 'data' => -2]);
            }
            $coupon = 0;
            $_coupon = DB::table('wallet')->select('coupon')->where(['uid' => $this->info->user_id])->first();
            if ($_coupon && $_coupon->coupon > 0) {
                $coupon = $_coupon->coupon;
            }
            if ($tot > $coupon) {
                return response()->json(['code' => 200, 'data' => -1]);
                exit;
            }

        }
        if ($pm == 1){


            $_data = [
                'pid' => $pid,
                'uid' => $user_id,
                'pay_method' => $pay_method,
                'op_time' => time(),
            ];
        $flag = 0;
        if ($ck_data) {

            $res = DB::table('build_pay')->where(['id' => $ck_data->id])->update($_data);

            if ($res) {
                echo $pid;
            }
        } else {
            $res = DB::table('build_pay')->insertGetId($_data);
            if ($res) {
                echo $pid;
            }
        }
    }elseif($pm==2){
            $_data = [
                'pid' => $pid,
                'uid' => $user_id,
                'pay_method' => 2,
                'op_time' => time(),
                'has_pay'=>1,
                'pay_time'=>time(),
            ];
            DB::table('build_pay')->insertGetId($_data);
            DB::table('wallet')->where(['uid'=>$this->info->user_id])->decrement('coupon',$tot);
            $_wallet_log_data=[
                'type'=>6,
                'income'=>0,
                'amount'=>$tot,
                'method'=>1,
                'uid'=>$this->info->user_id,
                'ctime'=>time(),
            ];
            DB::table('wallet_logs')->insert($_wallet_log_data);
            DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>5,'prj_final_modler'=>$user_id]);
            $email = $user_mail;
            $invite_url = 'https://'.$_SERVER['HTTP_HOST'].'/task/trialresult/'+$pid;
            $subject = 'Novaby甲方邀请您参加模型开发';
            $res1 = Mail::send('emailtpl.invite_modler1',['url'=>$invite_url, 'name'=>'Novaby','prj_name'=>'','price'=>''],function($message) use ($email,$subject){
                $to = $email;
                $message ->to($to)->subject($subject);
            });
            echo 1;


}
    }
    public function buildpay($id){
        if($id==0) exit;
        $pay_info = DB::table('build_pay')->where(['pid'=>$id])->first();
        if($pay_info->pay_method==1){

            return response()->redirectToRoute('buildpay_paypal',$id);
        }
    }
    public function reivew($id = 0){
        if($id<=0) exit;

        $prj = DB::table('projects')->select('prj_name','prj_id')->where(['prj_id'=>$id])->first();
        $prj->nav = $this->navHtml($id,5,$prj->prj_status);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.waitreivew',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$prj,'title'=>'Project publish successfully']);

    }
    public function failed($id=0){
        if($id<=0) exit;
        $prj = DB::table('projects')->select('prj_name','prj_id')->where(['prj_id'=>$id])->first();
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.failed',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$prj,'title'=>'Project publish successfully']);

    }
    public function progress($id=0){
        if($id==0) exit;
        echo $status = $this->prj_status($id);
        switch($status){
            case 0:
                return response()->redirectToRoute('projectNew',$id);
                break;
            case 1:
                return response()->redirectToRoute('projectDetail',$id);
                break;
            case 2:
                return response()->redirectToRoute('projectDetail',$id);
                break;
            case 3:
                return response()->redirectToRoute('projectDetail',$id);
                break;
            case 4:
                return response()->redirectToRoute('trialtask',$id);
                break;
            case 5:
                return response()->redirectToRoute('projectBuild',$id);
                break;
            case 6:
                return response()->redirectToRoute('projectBuild',$id);
                break;
            case 7:
                return response()->redirectToRoute('projectDetail',$id);
                break;
            case 8:
                return response()->redirectToRoute('projectDetail',$id);
                break;
            case 9:
                return response()->redirectToRoute('projectDetail',$id);
                break;
            case 10:
                return response()->redirectToRoute('projectDetail',$id);
                break;

        }

    }
    private function prj_status($id){
        $data = DB::table('projects')->where(['prj_id'=>$id])->first();
        return $data->prj_status;
    }
    public function chat(Request $req){

        $con = $req->get('con');
        $pid = $req->get('prj');
        $_f = $req->get('_f');
        if($_f){
            $_flag = 0;
        }else{
            $_flag = 1;
        }
        $_data=[
            'prj_id'=>$pid,
            'content'=>$con,
            'pubtime'=>time(),
            'flag'=>$_flag,
        ];
        $res = DB::table('build_chat')->insertGetId($_data);
        echo $res;

    }
    public function prjdays(Request $req){
        $id = $req->get('pid');
        if($id<=0) exit;
        $req_month=$req->get('time');
        $f=$req->get('f');

        $_req_months=explode("-",$req_month);
        $_y=$_req_months[0];
        if($f==1){
        $_m=intval($_req_months[1])+1;
        }
        elseif($f==2){
            $_m=intval($_req_months[1])-1;
        }


        $newstart=mktime(0,0,0,$_m, 1 ,$_y);
        $req_m = date('Y-m',$newstart);



        $bid_info = DB::table('biddings')->where(['bid_pid'=>$id])->first();
        $pay_info = DB::table('build_pay')->where(['pid'=>$id])->first();
        $due_time = $pay_info->pay_time+$bid_info->bid_cost_time*3600;
        $due_day = date('Y-m-d H:i:s',$due_time);


        $start_time=$newstart;
        $real_start = $pay_info->pay_time;

        //$this_day=intval(date('d',$start_time));

        $thismonth_days = date("t",$start_time);
        $this_day=1;

        if(strtotime($due_day)>=$newstart ){
            if($real_start<$newstart){
                $range_days = range($this_day,$thismonth_days);
            }else{
                if(date('Ym',$real_start)==date('Ym',$newstart)){
                    $thismonth_days=date('d',$due_time);

                    $range_days = range(date('d',$real_start),$thismonth_days);
                }else{
                    $range_days=[];
                }
            }
        }else{
            $range_days =[];
        }
        $range_days = Tools::days($real_start,$due_time,$req_m);
        $rdays='[';

        $rdays .= implode(",",$range_days);
        $rdays.=']';
        $ret = ['date_str'=>date('M Y',$newstart),'date'=>date('Y-m',$newstart),'data'=>$rdays,'newstart'=>date('Y-m-d H:i:s',$newstart),'real_start'=>date('Y-m-d H:i:s',$real_start),'req'=>$req_month];
        return response()->json(['code'=>'200','data'=>$ret]);

    }
    public function review($id=0){

        if($id<1) exit;
        $prj = DB::table('projects')->where(['prj_id'=>$id])->first();
        $prj->rate = (object)[];
        $prj->report = (object)[];
        if($prj->prj_status==8){
            $rate = DB::table('project_rate')->where(['r_pid'=>$id])->first();
            $prj->rate = $rate;

        }
        if($prj->prj_status==7){
            $report = DB::table('project_report')->where(['pid'=>$id])->first();
            $prj->report = $report;

        }
        //dd($prj);
        //$prj->prj_status = 1;
        $prj->nav = $this->navHtml($id,6,$prj->prj_status);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        return view('projects.review',['user'=>$this->info,'data'=>$prj,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Project publish successfully']);


    }
    public function trialstar(Request $req){
        $id = $req->get("id");
        $stars = $req->get("stars");
        $res = DB::table('trialsubmit')->where(['ts_id'=>$id])->update(['ts_rate'=>$stars]);
        if($res) echo 1;
    }
    public function phpinfo(){
        echo date_default_timezone_get();
        echo "<br/>";
        echo date('Y-m-d H:i:s');
        phpinfo();
    }
    public function dosKip(Request $req){
        $pid = $req->get('pid');
        $res = DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>3,'prj_isskip'=>2]);
        echo 1;


    }
    public function payresult($id){
        if($id<1) exit;
        $prj = DB::table('projects')->leftJoin('build_pay','projects.prj_id','=','build_pay.pid')->leftJoin('user','projects.prj_uid','=','user.user_id')->select('projects.*','build_pay.*','user.user_name','user.user_lastname')->where(['projects.prj_id'=>$id])->first();
        $bid_info = DB::table('biddings')->leftJoin('user','biddings.bid_uid','=','user.user_id')->select('biddings.*','user.user_name','user.user_lastname')->where(['biddings.bid_pid'=>$id,'biddings.bid_uid'=>$prj->prj_final_modler])->first();
        $prj->due_time = $prj->pay_time + $bid_info->bid_cost_time*3600;

        $prj->rname = $bid_info->user_name." ".$bid_info->user_lastname;
        $prj->fee = $bid_info->bid_price;
        $prj->nav = $this->navHtml($id,5,$prj->prj_status);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('projects.payresult',['user'=>$this->info,'data'=>$prj,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Project publish successfully']);

    }
    public function workrate(Request $req){
       // dd($req->all());
        $s1 = $req->get('s1');
        $s2 = $req->get('s2');
        $s3 = $req->get('s3');
        $pid = $req->get('pid');
        $_data = [
            'r_pid'=>$pid,
            'r_time'=>$s1,
            'r_quality'=>$s2,
            'r_other'=>$s3,
            'r_catetime'=>time(),
        ];
        $res = DB::table('project_rate')->insert($_data);
        DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>8]);
        if($res) echo 1;

    }
    public function workreport(Request $req){
        // dd($req->all());
        $con = $req->get('con');
        $pid = $req->get('pid');
        $_data = [
            'pid'=>$pid,
            'content'=>$con,

            'createtime'=>time(),
        ];
        $res = DB::table('project_report')->insert($_data);
        DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>7]);
        if($res) echo 1;

    }
    private function navHtml($id,$opstatus,$realstatus){
        //echo $opstatus.":".$realstatus;
        $html = '';
        if($opstatus==1){
            if($realstatus==1){
                $html = '<li class="selected"><a href="/projects/detail/' . $id . '">Inquiry</a></li>
            <li>Trial</li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
            }elseif($realstatus==2) {
                $html = '<li class="selected"><a href="/projects/detail/' . $id . '">Inquiry</a></li>
            <li><a href="/projects/trial/' . $id . '">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus<=4) {
                $html = '<li class="selected"><a href="/projects/detail/' . $id . '">Inquiry</a></li>
            <li><a href="/projects/trial/' . $id . '">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus==5){
                $html = '<li class="selected"><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li>Review</li>';
            }elseif($realstatus>5){
                $html = '<li class="selected"><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/projects/review/'.$id.'">Review</a></li>';

            }

        }elseif($opstatus==2){
            if($realstatus==1){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/' . $id . '">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus==2){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
            }
            elseif($realstatus<4){

            }elseif($realstatus==4){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus==5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/projects/build/'.$id.'">Build</a></li>
            <li>Review</li>';

            }elseif($realstatus>5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/payresult/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/projects/review/'.$id.'">Review</a></li>';
            }

        }elseif($opstatus==3){
            if($realstatus<4){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus==4){

                $html = '<li class="selected"><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li class="selected"><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus==5){
                $html = '<li class="selected"><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li>Review</li>';

            }elseif($realstatus>5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li class="selected"><a href="/projects/payresult/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/projects/review/'.$id.'">Review</a></li>';
            }

        }elseif($opstatus==4){
            if($realstatus<4){

            }elseif($realstatus==4){

            }elseif($realstatus==5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li class="selected"><a href="/projects/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/projects/build/'.$id.'">Build</a></li>
            <li>Review</li>';

            }elseif($realstatus>5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/projects/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/projects/review/'.$id.'">Review</a></li>';
            }

        }elseif($opstatus==5){
            if($realstatus<4){

            }elseif($realstatus==4){

            }elseif($realstatus==5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li class="selected"><a href="/projects/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/projects/build/'.$id.'">Build</a></li>
            <li>Review</li>';
            }elseif($realstatus>5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/projects/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/projects/review/'.$id.'">Review</a></li>';
            }

        }elseif($opstatus>5){
            if($realstatus<4){

            }elseif($realstatus==4){

            }elseif($realstatus==5){

            }elseif($realstatus>5){
                $html = '<li><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/projects/review/'.$id.'">Review</a></li>';
            }

        }
        $html1 = '<li class="selected"><a href="/projects/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/projects/trial/'.$id.'">Trial</a></li>
            <li><a href="/projects/pay/'.$id.'">Pay</a></li>
            <li><a href="/projects/build/'.$id.'">Build</a></li>
            <li><a href="/projects/review/'.$id.'">Review</a></li>';

        return $html;
    }


}
