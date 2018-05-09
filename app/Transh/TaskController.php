<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use App\libs\Calendar;
use App\libs\Tools;
use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
use DB;

class TaskController extends Controller
{
    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    public function all($id=0){
        if($id==0){
            $my_prjs = DB::table('invite_modlers')->select('pid')->where(['uid'=>$this->info->user_id,'ignore'=>0])->get();
            $my_prjs_ids=[];
            foreach($my_prjs AS $k=>$v){
                $my_prjs_ids[]=$v->pid;
            }

            $prjs_ids = implode(",",$my_prjs_ids);

            if(!$my_prjs_ids){
                $prjs_ids='0';
            }
            $list_sql="SELECT * FROM projects WHERE  prj_id IN (".$prjs_ids.")ORDER BY prj_id DESC";

        }elseif($id==1){
            $list_sql="SELECT * FROM projects WHERE prj_status=5 AND prj_final_modler=".$this->info->user_id;

        }
        elseif($id==2){
            $list_sql="SELECT * FROM projects WHERE prj_status>4 AND prj_status<7 AND prj_final_modler=".$this->info->user_id;

        }
        elseif($id==3){
            $ids = DB::table('biddings')->where(['bid_uid'=>$this->info->user_id,'bid_accept'=>1])->get();
            $ids_arr= [];
            foreach($ids AS $k=>$v){
                $ids_arr[]=$v->bid_pid;
            }
            $ids = implode(",",$ids_arr);
            echo $list_sql="SELECT * FROM projects WHERE prj_status=4 AND prj_id in(".$ids.")";

        }else{
            exit;
        }

        $lists = DB::select($list_sql);
        foreach($lists AS $k=>$v){
            $photos = explode(",",$v->prj_photos);
            $cover =$photos[0];
            $cover_img = DB::table('oss_item')->where(['oss_item_id'=>$cover])->first();

            $imgurl = 'http://elements.img-cn-hongkong.aliyuncs.com';
            $lists[$k]->cover = $imgurl.'/'.$cover_img->oss_path;

            $lists[$k]->lefttime = Tools::leftTime($v->prj_end_time);
            if($id==0){
                $bid_num = DB::table('biddings')->where(['bid_pid'=>$v->prj_id])->count();

                $lists[$k]->bid_num=$bid_num;

            }
        }


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('task.lists',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$lists,'channel'=>$id,'title'=>'Project trial']);

    }
    public function bid($id=0){

        if($id<=0){
            abort(404);
            return;
        }
        $ch_invite = DB::table('invite_modlers')->where(['uid'=>$this->info->user_id,'pid'=>$id])->first();

        if(!$ch_invite){
            //abort(404);
            //return;
        }
        $ck_bid = DB::table('biddings')->where(['bid_pid'=>$id,'bid_uid'=>$this->info->user_id])->count();
        $bids = DB::table('prj_apply')->where(['user_id'=>$this->info->user_id,'prj_id'=>$id])->first();
        if(!$bids){
            abort(404);
        }
        if(!$bids->apply_cost_time || !$bids->apply_price){
            $bids=[];
        }else{

        }

        if($ck_bid){
            return response()->redirectToRoute('taskdetail',$id);
        }
        $proj=DB::table('projects')->where(['prj_id'=>$id])->first();
        $prj_photos = [];
        $photo_ids = explode(',',$proj->prj_photos);
        $photos = DB::table('oss_item')->whereIN('oss_item_id',$photo_ids)->get();
        $imgurl = 'http://elements.img-cn-hongkong.aliyuncs.com';
        foreach($photos AS $K=>$v){
            $prj_photos[]=$imgurl.'/'.$v->oss_path;
        }
        $proj->prj_photos =$prj_photos;

        $industry=DB::table('category')->where('cate_id',$proj->prj_industry)->first();
        $cate=DB::table('category')->where('cate_id',$proj->prj_cate)->first();
        $format=DB::table('category')->where('cate_id',$proj->prj_format)->first();

        $proj->prj_industry =$industry->cate_name;
        $proj->prj_cate =$cate->cate_name;
        $proj->prj_format =$format->cate_name;
        $proj->hasBids=[];
       // $bids = DB::table('biddings')->leftJoin('user','biddings.bid_uid','=','user.user_id')->select('biddings.*','user.user_name','user.user_lastname')->where(['biddings.bid_pid'=>$id])->get();
       // foreach($bids AS $k=>$v){

        //}

        $proj->duetime=$proj->prj_pubtime+$proj->prj_period;

        //$proj->duetime1 = $proj->duetime-time();
        $proj->duetime = Tools::leftTime($proj->prj_end_time);


        $proj->hasBids = $bids;
        $proj->nav = $this->navHtml($id,1,$proj->prj_status);


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        return view('task.bid',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$proj,'title'=>'Project trial']);

    }
    public function detail($id=0){

        if($id<=0)
        {
            abort(404);
            return;
        }

        $ck_bid = DB::table('biddings')->where(['bid_uid'=>$this->info->user_id,'bid_pid'=>$id])->first();

        if(!$ck_bid){
            return response()->redirectToRoute('bid',$id);
            exit;
        }

        $proj=DB::table('projects')->where(['prj_id'=>$id])->first();
        //dd($proj);
        if($proj->prj_status==4){
            //return response()->redirectToRoute('tasktrial', $id);
        }
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
        $bids = DB::table('biddings')->where(['bid_pid'=>$id,'bid_uid'=>$this->info->user_id])->first();

        $proj->duetime=$proj->prj_pubtime+$proj->prj_period;
        $proj->duetime = Tools::leftTime($proj->prj_end_time);
        $proj->bids = $bids;
        $proj->isview = 1;
        $proj->nav = $this->navHtml($id,1,$proj->prj_status,$proj->prj_final_modler==$this->info->user_id);

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('task.detail',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$proj,'title'=>'Project trial']);

    }
    public function trial($id=0){

        $ch_invite = DB::table('invite_modlers')->where(['uid'=>$this->info->user_id,'pid'=>$id])->first();

        if(!$ch_invite){
            abort(404);
            return;
        }
       // $data = DB::table('trial')->leftJoin('projects','trial.trial_pid','=','projects.prj_id')->select('*')->where(['trial.trial_pid'=>$id])->first();
        $data = DB::table('projects')->leftJoin('trial','trial.trial_pid','=','projects.prj_id')->select('*')->where(['projects.prj_id'=>$id])->first();

        if(!$data) exit;
        $ck_submit = DB::table('trialsubmit')->where(['ts_pid'=>$id,'ts_uid'=>$this->info->user_id])->first();
        if($ck_submit){
            return redirect('/task/trialresult/'.$id);
        }
        if($data->prj_isskip!==2){
        $t_endtime = $data->trial_pubtime+$data->trial_time*3600;
        $data->duetime = $t_endtime;
        $_attch = DB::table('oss_item')->where(['oss_item_id'=>$data->trial_attch])->first();
        $_attchs = explode('/',$_attch->oss_path);

        $data->attch_name=$_attchs[count($_attchs)-1];
        $data->attch_url=ApiConf::IMG_URI.'/'.$_attch->oss_path;

        $data->duetime = Tools::leftTime($t_endtime);

        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $_cme = 1;
        if($data->prj_isskip!==2){
        $ck_trial_modlers = DB::table('trial')->select('trial_modlers')->where(['trial_pid'=>$id])->first();
        if($ck_trial_modlers && $ck_trial_modlers->trial_modlers){
            $_ck_trial_modlers = explode(",", $ck_trial_modlers->trial_modlers);
        }
        $_cme = 0;
        if(in_array($this->info->user_id,$_ck_trial_modlers)){
            $_cme = 1;
        }

        }
        $data->_cme = $_cme;
        //var_dump($data->prj_final_modler==$this->info->user_id);


        $data->nav = $this->navHtml($id,2,$data->prj_status,$_cme);

        return view('task.trial',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'Project trial']);

    }
    public function trialresult($id=0){
        $data = DB::table('trial')->leftJoin('projects','trial.trial_pid','=','projects.prj_id')->select('*')->where(['trial.trial_pid'=>$id])->first();
        $isme = 0;
        if($data->prj_final_modler==$this->info->user_id){
            $isme = 1;
        }
        //$submit=DB::table('trialsubmit')->where(['ts_pid'=>$id])->get();
        //echo $data->prj_final_modler
        $choose=0;
        if($data->prj_status==5){
            if($data->prj_final_modler &&  $data->prj_final_modler==$this->info->user_id){
                $choose=2;
            }else{

                $choose=1;
            }
        }


        $data->choose=$choose;
        $t_endtime = $data->trial_pubtime+$data->trial_time*24;
        $data->duetime = $t_endtime;
        $_attch = DB::table('oss_item')->where(['oss_item_id'=>$data->trial_attch])->first();

        $_attchs = explode('/',$_attch->oss_path);
        $data->attch_url=ApiConf::IMG_URI.'/'.$_attch->oss_path;
        $data->attch_name=$_attchs[count($_attchs)-1];
        $rate_info = DB::table('trialsubmit')->where(['ts_pid'=>$id,'ts_uid'=>$this->info->user_id])->first();
        
        $rate = 0 ;
        if($rate_info && $rate_info->ts_rate){
            $rate= $rate_info->ts_rate;
        }
        $data->rate = $rate;
        $ck_trial_modlers = DB::table('trial')->select('trial_modlers')->where(['trial_pid'=>$id])->first();
        $_ck_trial_modlers = explode(",", $ck_trial_modlers->trial_modlers);

        $_cme = 0;
        if(in_array($this->info->user_id,$_ck_trial_modlers)){
            $_cme = 1;
        }
        $data->_cme = $_cme;
        $data->nav = $this->navHtml($id,2,$data->prj_status,$_cme);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        return view('task.trialresult',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'Project trial']);

    }
    public function trialsubmit($id=0){
        if($id<1) exit;

        $data = DB::table('trial')->leftJoin('projects','trial.trial_pid','=','projects.prj_id')->select('*')->where(['trial.trial_pid'=>$id])->first();
        //dd($data);
        $t_endtime = $data->trial_pubtime+$data->trial_time*3600;
        $data->duetime = $t_endtime;
        $_attch = DB::table('oss_item')->where(['oss_item_id'=>$data->trial_attch])->first();
        $_attchs = explode('/',$_attch->oss_path);

        $data->attch_name=$_attchs[count($_attchs)-1];
        
        $data->attch_url=ApiConf::IMG_URI.'/'.$_attch->oss_path;
        $data->duetime = Tools::leftTime($t_endtime);


        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $ck_submit = DB::table('trialsubmit')->where(['ts_pid'=>$id,'ts_uid'=>$this->info->user_id])->first();
        $ts_photos = [];
        $_isview = 0;
        if($ck_submit){
            $_photos_ids = explode(",", $ck_submit->ts_photos);
            $_photos = DB::table('oss_item')->whereIn('oss_item_id',$_photos_ids)->get();
            foreach($_photos AS $k=>$v){
                $ts_photos[]=ApiConf::IMG_URI.$v->oss_path;
            }
            $_isview = 1;

        }
        $ck_trial_modlers = DB::table('trial')->select('trial_modlers')->where(['trial_pid'=>$id])->first();
        $_ck_trial_modlers = explode(",", $ck_trial_modlers->trial_modlers);

        $_cme = 0;
        if(in_array($this->info->user_id,$_ck_trial_modlers)){
            $_cme = 1;
        }
        $data->nav = $this->navHtml($id,2,$data->prj_status,$_cme);
        echo $data->_cme = $_cme;
        $data->submit_photos = $ts_photos;
        $data->isview = $_isview;
        return view('task.trialsubmit',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'Project trial']);

    }
    public function days($time1,$time2){
        $tmp = $time1;
        $i=0;
        while($tmp<=$time2){
            echo $i++;
            echo "<br/>";
            $tmp=$time1+3600*24;
        };
    }
    public function build($id=0){
        if($id<=0) exit;

        /*
        $bid_info = DB::table('biddings')->leftJoin('projects','biddings.bid_pid','=','projects.prj_id')->select('projects.prj_name','projects.prj_uid','biddings.*')->where(['biddings.bid_pid'=>$id,'biddings.bid_uid'=>$this->info->user_id])->first();
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
        $bid_info = DB::table('biddings')->leftJoin('projects','biddings.bid_pid','=','projects.prj_id')->select('projects.prj_name','projects.prj_uid','projects.prj_final_modler','projects.prj_status','biddings.*')->where(['biddings.bid_pid'=>$id])->first();
        if($bid_info->prj_final_modler!=$this->info->user_id){
            abort(404);
            return;
        }
        $pay_info = DB::table('build_pay')->where(['pid'=>$id])->first();
        $due_time = $pay_info->pay_time+$bid_info->bid_cost_time*3600;
        $due_day = date('Y-m-d H:i:s',$due_time);
        $start_time=$pay_info->pay_time;
        //echo date('Y-m-d H:i:s',$start_time);

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
        $range_days = range($this_day,$due_days);
        //print_r($range_days);
        $rdays='[';
        //dd(implode(",",$range_days));
        $rdays .= implode(",",$range_days);
        $rdays.=']';



        $tmp = $start_time;
        $i=0;
        $final = 0;
        $final_url = '';
        $final_time='';
        $rlists=[];
        while($tmp<=$today){
            $i++;
            //echo date('Y-m-d H:i:s',$tmp);
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
        $chat = DB::table('build_chat')->where(['prj_id'=>$id])->get();

        $user_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$this->info->user_id])->first();

        if($user_icon->user_icon==0){
            $icon = '/images/logo.jpg';
        }else{
            $_icon=DB::table('oss_item')->where(['oss_item_id'=>$user_icon->user_icon])->first();
            $icon = ApiConf::IMG_URI.$_icon->oss_path.'@0o_0l_300w_90q.src';
        }

        $user_icon_1 = DB::table('user')->select('user_icon')->where(['user_id'=>$bid_info->prj_uid])->first();

        if($user_icon_1->user_icon==0){
            $icon_1 = '/images/logo.jpg';
        }else{
            $_icon_1=DB::table('oss_item')->where(['oss_item_id'=>$user_icon_1->user_icon])->first();
            $icon_1 = ApiConf::IMG_URI.$_icon_1->oss_path.'@0o_0l_300w_90q.src';
        }

        foreach($chat AS $k=>$v){
            if($v->flag==1){
                $chat[$k]->icon=$icon;
            }else{
                $chat[$k]->icon=$icon_1;
            }
        }
        $data->chat = $chat;
        $data->start_str = date('M Y',$data->starttime);
        $data->start_str1 = date('Y-m',$data->starttime);
        $last_submit = DB::table('builddaly')->select('bd_pubtime','bd_final')->where(['bd_pid'=>$id,'bd_uid'=>$this->info->user_id])->orderBy('bd_id','DESC')->first();
        $cansub = 1;
        $data->final=0;
        if($last_submit){
        $cansub=time()-$last_submit->bd_pubtime>24*3600 ? 1 : 0;
            $data->final=$last_submit->bd_final;
        }

        $data->range_days= $rdays;
        $data->cansub=$cansub;

        $data->user_icon = $icon;

        $data->nav = $this->navHtml($id,4,$bid_info->prj_status,$bid_info->prj_final_modler==$this->info->user_id);

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('task.build',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'Project trial']);

    }
    public function pubbid(Request $req){

        $pid = $req->get('pid');
        $time = $req->get('time');
        $price = $req->get('price');
        $ck_apply = DB::table('prj_apply')->where(['user_id'=>$this->info->user_id,'prj_id'=>$pid])->count();
        if($ck_apply!=1){
            exit;
        }
        $update_apply = DB::table('prj_apply')->where(['user_id'=>$this->info->user_id,'prj_id'=>$pid])->update(['apply_cost_time'=>$time,'apply_price'=>$price]);
        if($update_apply){
            echo 1;
        }
        exit;
        $ck_bid = DB::table('biddings')->where(['bid_uid'=>$this->info->user_id,'bid_pid'=>$pid])->first();
        if($ck_bid){
            echo "has bidded";
            return;
        }
        $ck_name = DB::table('biddings')->where(['bid_pid'=>$pid])->orderBy('bid_id','DESC')->first();
        $name = ord('A');
        if($ck_name){
            $name = $name+1;
        }
        $name = chr($name);
        $data = [
            'bid_uid'=>$this->info->user_id,
            'bid_pid'=>$pid,
            'bid_time'=>time(),
            'bid_cost_time'=>$time,
            'bid_price'=>$price,
            'bid_name'=>$name,

        ];
        $res = DB::table('biddings')->insertGetId($data);
        if($res){
            $ck_status=DB::table('projects')->select('prj_status')->where(['prj_id'=>$pid])->first();
            if($ck_status->prj_status==1){
                DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>2]);
            }
        }
        echo 1;
    }
    public function submittrial(Request $req){

        $id = $req->get('pid');
        $tid = DB::table('trial')->where(['trial_pid'=>$id])->first();
        $tid = $tid->trial_id;
        $ref_photos = $req->get('data');
        $photos_ids=[];
        foreach($ref_photos AS $k=>$v){

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



        }
        $photo_ids = implode(",",$photos_ids);
        $_data=[
            'ts_tid'=>$tid,
            'ts_pid'=>$id,
            'ts_uid'=>$this->info->user_id,
            'ts_photos'=>$photo_ids,
            'ts_attachment'=>0,
            'ts_pubtime'=>time(),
        ];
        $res = DB::table('trialsubmit')->insertGetId($_data);
        echo $res;

    }
    private function time_differ($start,$end){
        $d1 = strtotime($start);
        $d2 = strtotime($end);
        $days = round(($d2-$d1)/3600/24);
        return $days;
    }
    public function review($id=0){
        if($id<=0) exit;
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
        $last_submit = DB::table('builddaly')->select('bd_pubtime','bd_final')->where(['bd_pid'=>$id,'bd_uid'=>$this->info->user_id])->orderBy('bd_id','DESC')->first();

        if(!$last_submit){
            abort(404);
            exit;
        }
        $waittime=time()-$last_submit->bd_pubtime;
        $waittime = Tools::leftTime1($waittime);



        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $prj->nav = $this->navHtml($id,5,$prj->prj_status);
        $prj->waittime = $waittime;
        //dd($proj);
        return view('task.review',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$prj,'title'=>'Project trial']);

    }
    public function calender(){
        $params = array(
            'year' => 2017,
            'month' => 01,
        );
        $params['url']  = 'demo.php';
        $cal = new Calendar($params);
        $cal->display();
    }
    public function ignore(Request $req){
        $id = $req->get('pid');
        $res = DB::table('invite_modlers')->where(['pid'=>$id,'uid'=>$this->info->user_id])->update(['ignore'=>1]);
        if($res)  echo 1;
    }
    public function taken(Request $req){

        $id = $req->get('pid');
        $res = DB::table('invite_modlers')->where(['pid'=>$id,'uid'=>$this->info->user_id])->update(['ignore'=>2]);
        echo 1;
    }
    public function builddalypub(Request $req){
        //dd($req->all());
        $photos = $req->get('build_photos');
        $attach = $req->get('build_attach');
        $final = $req->get('final');
        $pid = $req->get('pid');
        $uid = $this->info->user_id;
        $photos_ids = [];
        foreach($photos AS $k=>$v){

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
        }
        $photos_ids= implode(",",$photos_ids);
        $attach_id = DB::table('oss_item')->insertGetId(
            [
                'oss_key' => 'attachment',
                'oss_path' => $attach['name'],
                'oss_item_uid' => $this->info->user_id,
                'size' => $attach['size'],

            ]
        );
        $_data=[
            'bd_pid' => $pid,
            'bd_uid' => $uid,
            'bd_photos' => $photos_ids,
            'bd_attachment' => $attach_id,
            'bd_pubtime' => time(),
            'bd_final'=>$final,
        ];
        $res = DB::table('builddaly')->insertGetId($_data);
        if($final){
            DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>6]);
        }
        echo $res;

    }
    public function taskprogress($id=0){

        $prj = DB::table('projects')->select('prj_status')->where(['prj_id'=>$id])->first();
        echo $prj->prj_status;
        switch($prj->prj_status){
            case 1:
            case 2:
                return response()->redirectToRoute('bid',$id);
                break;
            case 3:
                break;
            case 4:
                return response()->redirectToRoute('tasktrial',$id);
                break;
            case 5:
                return response()->redirectToRoute('tasktrial',$id);
                break;
            case 6:
                return response()->redirectToRoute('tasktrial',$id);
                break;
            case 7:
                break;
            case 8:
                break;
            case 9:
                break;
            case 10:
                break;
        }
    }
    public function payresult($id){
        if($id<1) exit;
        $prj = DB::table('projects')->leftJoin('build_pay','projects.prj_id','=','build_pay.pid')->leftJoin('user','projects.prj_uid','=','user.user_id')->select('projects.*','build_pay.*','user.user_name','user.user_lastname')->where(['projects.prj_id'=>$id])->first();
        $isme = 1;
        if($prj->prj_final_modler!=$this->info->user_id){
            $isme = 0;
        }
        $bid_info = DB::table('biddings')->leftJoin('user','biddings.bid_uid','=','user.user_id')->select('biddings.*','user.user_name','user.user_lastname')->where(['biddings.bid_pid'=>$id,'biddings.bid_uid'=>$prj->prj_final_modler])->first();
        $prj->due_time = $prj->pay_time + $bid_info->bid_cost_time*3600;
        $prj->rname = $bid_info->user_name." ".$bid_info->user_lastname;
        $prj->fee = $bid_info->bid_price;
        $prj->nav = $this->navHtml($id,3,$prj->prj_status,$isme);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('task.payresult',['user'=>$this->info,'data'=>$prj,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Project publish successfully']);

    }
    private function navHtml($id,$opstatus,$realstatus,$chooseme=0){
        echo $opstatus."#".$realstatus."#".$chooseme;
        if($opstatus==1){
            if($realstatus==1){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li>Trial</li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';

            }elseif($realstatus==2){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li>Trial</li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
            }elseif($realstatus==4){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
            }elseif($realstatus==5){
                if($chooseme){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/task/build/'.$id.'">Build</a></li>
            <li>Review</li>';
                }else{
                    $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li>Trial</li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                }
            }elseif($realstatus>5){

                if($chooseme){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/task/review/'.$id.'">Review</a></li>';
                }else{
                    $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                }
            }

        }elseif($opstatus==2){

            if($realstatus==1){

                }elseif($realstatus==2){

            }elseif($realstatus==4) {

                    $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
            }elseif($realstatus==5) {
                    if($chooseme){
                $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/task/payresult/'.$id.'">Build</a></li>
            <li>Review</li>';
                    }else{
                        $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                    }
            }elseif($realstatus>5){
                    if($chooseme){
                $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/task/review/'.$id.'">Review</a></li>';
                    }else{
                        $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                    }
            }


        }elseif($opstatus==3){
            if($realstatus==1){

            }elseif($realstatus==2){

            }elseif($realstatus==3){
                $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
            }elseif($realstatus==5){

                if($chooseme){
                $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li class="selected"><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/task/build/'.$id.'">Build</a></li>
            <li>Review</li>';
                }else{
                    $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                }

            }elseif($realstatus>5){
                if($chooseme){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li><a href="/task/review/'.$id.'">Review</a></li>';
                }else{
                    $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                }
            }

        }elseif($opstatus==4){

            if($realstatus==1){

            }elseif($realstatus==2){

            }elseif($realstatus==5){
                $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li class="selected"><a href="/task/build/'.$id.'">Build</a></li>
            <li>Review</li>';

            }elseif($realstatus>5){
                if($chooseme){
                $html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li><a href="/task/review/'.$id.'">Review</a></li>';
                }else{
                    $html = '<li><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li class="selected"><a href="/task/trial/'.$id.'">Trial</a></li>
            <li>Pay</li>
            <li>Build</li>
            <li>Review</li>';
                }
            }

        }elseif($opstatus==5){

if($realstatus==1){

}elseif($realstatus==2){

}elseif($realstatus==5){
$html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li>Review</li>';

            }elseif($realstatus>5){
$html = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li class="selected"><a href="/task/review/'.$id.'">Review</a></li>';
            }

        }

        $html1 = '<li class="selected"><a href="/task/detail/'.$id.'">Inquiry</a></li>
            <li><a href="/task/trial/'.$id.'">Trial</a></li>
            <li><a href="/task/payresult/'.$id.'">Pay</a></li>
            <li><a href="/task/build/'.$id.'">Build</a></li>
            <li><a href="/task/review/'.$id.'">Review</a></li>';
        return $html;
    }

}
