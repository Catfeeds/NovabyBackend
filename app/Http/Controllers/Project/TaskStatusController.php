<?php

namespace App\Http\Controllers\Project;

use App\libs\OSSManager;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Session;
use DB;
use App\Services\CreateProgress;
use App\libs\ApiConf;
use App\libs\Tools;

class TaskStatusController extends Controller
{
    private $info;
    private $__fileds;

    public function __construct(){
        $this->info=Session::get('userInfo',null);
        $this->__fileds = [1=>'Game',2=>'Film',3=>'AR/VR',4=>'3Dprinting'];
    }

    private function ckstatus($data,$status){
        if(!$data || $data->prj_process_status<$status){
            abort(404);
            exit;
        }
        $ck_apply = DB::table('prj_apply')->where(['prj_id'=>$data->prj_id,'user_id'=>$this->info->user_id])->count();
        if(!$ck_apply){
            abort(404);
            exit;
        }
    }
    private function progress($pid){
        $res = DB::table('prj_apply')->where(['prj_id'=>$pid,'user_id'=>$this->info->user_id])->first();
        return $res->prj_status;
    }
    private function setProgress($pid,$status){
        DB::table('prj_apply')->where(['prj_id'=>$pid,'user_id'=>$this->info->user_id])->update(['prj_uptime'=>time(),'prj_status'=>$status]);
    }
    public function requirement($id = 0 ){

        $project = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($project,1);
        $project->nav = CreateProgress::url3($this->progress($id),1,$id);
        $prj_photos = [];
        $photo_ids = explode(',',$project->prj_photos);
        $photos = DB::table('oss_item')->whereIN('oss_item_id',$photo_ids)->get();
        foreach($photos AS $K=>$v){
            $prj_photos[]=ApiConf::IMG_URI.'/'.$v->oss_path;
        }
        $project->prj_photos =$prj_photos;

        $industry=DB::table('category')->where('cate_id',$project->prj_industry)->first();
        $cate=DB::table('category')->where('cate_id',$project->prj_cate)->first();
        $format=DB::table('category')->select('cate_name')->whereIn('cate_id',explode(",",$project->prj_format))->get();
        $_format = '';
        foreach($format AS $k=>$v){
            $_format.=$v->cate_name." ";
        }

        $project->prj_industry =$industry->cate_name;
        $project->prj_cate =$cate->cate_name;
        $project->prj_format =$_format;
        $_email = DB::table('user')->select('user_email')->where(['user_id'=>$this->info->user_id])->first();

        $project->email =$_email->user_email;
        $accs = ['Default','Hight','Low'];
        $project->prj_acc = $accs[$project->prj_acc-1];
        $project->email = $_email->user_email;
        $project->lefttime = ($project->prj_pubtime+$project->prj_period*3600*24+$project->prj_period_h*3600)-time();

        return view('v1.tasks.requirement',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project]);

    }

    public function proposal($id=0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($data,2);
        $data->nav = CreateProgress::url3($this->progress($id),2,$id);
        $ck_bid = DB::table('bidding')->where(['bid_pid'=>$id,'bid_uid'=>$this->info->user_id])->first();

        if(!$ck_bid){
            //没有竞价显示竞价表单
            $data->values = Tools::timeZoneShow();
            return view('v1.tasks.proposal_bid_form',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);

        }
        $data->bid_time = $ck_bid->bid_time;
        $data->bid_price = $ck_bid->bid_price;
        $data->start_date = $ck_bid->bid_start_time;
        $data->lefttime = ($data->prj_pubtime+$data->prj_period*3600*24+$data->prj_period_h*3600)-time();
        $data->lefttime = Tools::leftTime1($data->lefttime);

        $has_op = DB::table('bidding')->where(['bid_pid'=>$id,'bid_accept'=>1])->count();
        //0 甲方未处理 1选择我 2 未选择我
        if(!$has_op){
            $_status = ['code'=>0,'txt'=>'no update'];

        }else{
           $select_me = DB::table('bidding')->where(['bid_pid'=>$id,'bid_accept'=>1,'bid_uid'=>$this->info->user_id])->count();
           if($select_me){
               $_status = ['code'=>1,'txt'=>'congratulations! the buyer invites you to take a trial'];
           }else{
               $_status = ['code'=>2,'txt'=>'sorry! the buyer does not  select you proposal'];
           }
        }
        $data->_status = $_status;
        $data->bid_time_day = $ck_bid->bid_cost_time;
        $data->bid_time_hour = $ck_bid->bid_cost_time_h;
        $data->bid_start_type = $ck_bid->bid_start_type;
        $data->bid_start_time = $ck_bid->bid_start_time;

        return view('v1.tasks.proposal',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);





    }
    public function trial($id = 0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($data,3);
        $data->nav = CreateProgress::url3($this->progress($id),1,$id);
        if($data->prj_skip){

            return view('v1.tasks.skip',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);
        }
        $taken = 0;
        $ck_my_apply = DB::table('bidding')->where(['bid_pid'=>$id,'bid_uid'=>$this->info->user_id])->first();
        if($ck_my_apply && $ck_my_apply->bid_apply==1){
            $taken = 1;
        }
        $trial = DB::table('trial_works')->where(['trial_pid'=>$id])->first();

        if(!$trial->trial_payway){

            return view('v1.tasks.trial_waitting_pay',['data'=>$data,'user'=>$this->info,'user_info'=>$this->info,'trial_info'=>$trial]);
        }
        $_ps = explode(",",$trial->trial_attachment);
        $attach = DB::table('oss_item')->select('oss_path')->whereIn('oss_item_id',$_ps)->get();
        foreach($attach AS $k=>$v){
            $attach[$k]->src = ApiConf::IMG_URI.$v->oss_path;
        }
        $trial->trial_attach = $attach;
        $_choose_other = DB::table('bidding')->where(['bid_pid'=>$id,'bid_apply'=>1])->count();
        if($_choose_other && $taken==0){
            $taken = 2;
        }
        //taken 0 未回应 1 选择了我 2 选择了其他人
        //dd($taken);
        if($taken==1){
            $ck_mytrial = DB::table('trialsubmits')->where(['ts_pid'=>$id,'ts_uid'=>$this->info->user_id])->first();
            if($ck_mytrial){

                if($trial->trial_haspay){
                    if(!$ck_mytrial->ts_accept){
                        $ck_mytrial->ts_rate = $ck_mytrial->ts_rate==null?0:$ck_mytrial->ts_rate;

                        return view('v1.tasks.trial_waitting',['data'=>$data,'user'=>$this->info,'user_info'=>$this->info,'trial_info'=>$trial,'my_work'=>$ck_mytrial]);
                    }else{
                        return view('v1.tasks.invite_contract',['data'=>$data,'user'=>$this->info,'user_info'=>$this->info,'trial_info'=>$trial,'my_work'=>$ck_mytrial]);
                    }
                    //return view('v1.tasks.trial_paid',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial,'my_work'=>$ck_mytrial]);
                }



                return view('v1.tasks.trial_waitting',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial,'my_work'=>$ck_mytrial]);
            }else{
                echo "not select me";
            }

            if(!$trial->trial_haspay){


                return view('v1.tasks.trial_waitting_pay',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial,'my_work'=>$ck_mytrial]);
            }
            $left = $trial->trial_paytime+$trial->trial_cost_time*3600-time();

            if($trial->trial_attach_zip){
                $att_zip =DB::table('oss_item')->where(['oss_item_id'=>$trial->trial_attach_zip])->first();

                $_name = explode("/",$att_zip->oss_path);
                $att_zip->name =$_name[count($_name)-1];
                $att_zip->url = ApiConf::IMG_URI.$att_zip->oss_path;
                $att_zip->size= Tools::sizeConvert($att_zip->size);
                $trial->trial_attach_zip = $att_zip;
            }
            //dd($trial->trial_attach_zip);


            return view('v1.tasks.trial_submit',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial,'my_work'=>$ck_mytrial,'left'=>$left]);
        }elseif($taken==0){
           // dd($trial);
            $trial->duetime = $trial->trial_pubtime+$trial->trial_cost_time*3600;
            return view('v1.tasks.trial_view',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial,'left'=>100]);
            //return view('v1.tasks.trial_waitting',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial]);
        }elseif($taken==2){
            return view('v1.tasks.trial_end',['data'=>$data,'user_info'=>$this->info,'trial_info'=>$trial]);
        }
    }
    public function contract($id){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($data,4);
        $data->nav = CreateProgress::url3($this->progress($id),4,$id);
        $ck_pay =DB::table('build_pays')->where(['pid'=>$id,'has_pay'=>1])->first();

        //$ck_pay = null;

        if(!$ck_pay){

            return view('v1.tasks.waitting_pay',['data'=>$data,'user_info'=>$this->info]);
        }else{
            //if(!$data->prj_signed){
                return view('v1.tasks.contract_agree',['data'=>$data,'user_info'=>$this->info]);
            //}else{
              //  return view('v1.tasks.contract_pay_result',['data'=>$data,'user_info'=>$this->info]);
            //}
            //return view('v1.tasks.trial_pay_result',['data'=>$data,'user_info'=>$this->info]);
        }
    }
    public function payment($id= 0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($data,5);
        $data->nav = CreateProgress::url3($this->progress($id),5,$id);
        $pay_info = DB::table('build_pays')->where(['pid'=>$data->prj_id])->first();
        $bid_info = DB::table('bidding')->leftJoin('user','bidding.bid_uid','=','user.user_id')->select('bidding.*','user.user_name','user.user_lastname')->where(['bid_pid'=>$data->prj_id])->first();

        $paid_info = [
            'prj_name'=>$data->prj_name,
            'buyer_type'=>1,
            'buyer_name'=>$this->info->user_name,
            'modeler'=>$bid_info->user_name." ".$bid_info->user_lastname,
            'due'=>$pay_info->pay_time+$bid_info->bid_cost_time*24*3600+$bid_info->bid_cost_time_h*3600,
            'cost'=>$bid_info->bid_cost_time,
            'tot'=>$bid_info->bid_price,
            'pay_method'=>$pay_info->pay_method,
            'pay_time'=>$pay_info->pay_time,
            'receipt'=>$pay_info->receipt,
            'agreement'=>'',
            'contract'=>'',
        ];
        $paid_info=(object)$paid_info;
        return view('v1.tasks.pay_result',['data'=>$data,'user_info'=>$this->info,'paid_info' => $paid_info]);

    }
    public function building($id = 0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($data,6);
        $pay_info = DB::table('build_pays')->where(['pid'=>$id])->first();
        $bidding = DB::table('bidding')->where(['bid_pid'=>$id])->first();
        $data->start = $pay_info->pay_time;
        $data->end = $pay_info->pay_time+$bidding->bid_cost_time_h*3600+$bidding->bid_cost_time*3600*24;
        $data->nav = CreateProgress::url2($this->progress($id),6,$id);
        $last_work = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        if($last_work){
            $final_attach = DB::table('oss_item')->where(['oss_item_id'=>$last_work->bd_attachment])->first();
            $last_work->final_work = $final_attach;


        }
        $data->final_work = $last_work;

        //echo "start:".date('Y-m-d H:i:s', $data->start);
        //echo "<br/>";
        //echo "end:".date('Y-m-d H:i:s', $data->end);

        $data->lefttime = $data->end-time();


        //echo "<br/>";
        //echo "left time ".$data->lefttime;
        $_icon = DB::table('user')->select('user_icon','user_name','user_lastname')->where(['user_id'=>$data->prj_uid])->first();
        $_oss = $this->getOssPath($_icon->user_icon);
        $data->ouser = [
            'icon'=>ApiConf::IMG_URI.$_oss->oss_path,
            'name'=>$_icon->user_name.' '.$_icon->user_lastname,
        ];


        $data->chatlist = $this->chatlist($id);
        $final_submit = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        //dd($final_submit);




        $data->nav = CreateProgress::url3($this->progress($id),6,$id);
        $data->start_str = date('Y-m-d',$data->start);
        $data->end_str = date('Y-m-d',$data->end);
        $data->today_str = date('Y-m-d');
        $_last_submit = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_uid'=>$this->info->user_id])->first();
        $this_day_submit = 0;

        if($_last_submit && $_last_submit->bd_pubtime ){
            if(date('Ymd')==date('Ymd',$_last_submit->bd_pubtime)){
                $this_day_submit = 1;
            }
        }
        $data->has_submit = $this_day_submit;

        return view('v1.tasks.building',['data'=>$data,'user_info'=>$this->info]);
    }
    public function subBidding(Request $req){

        $bid_time_day = $req->get('bid_time_day');
        $bid_time_hour = $req->get('bid_time_hour');
        $bid_cost = $req->get('bid_cost');
        $start_time = $req->get('startDate');
        $bid_start_type = $req->get('start_type');
        $pid = $req->get('pid');
        $has_bid = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_uid'=>$this->info->user_id])->count();
        $_data = [
            'bid_cost_time'=>$bid_time_day,
            'bid_cost_time_h'=>$bid_time_hour,
            'bid_price'=>$bid_cost,
            'bid_start_time'=>strtotime($start_time),
            'bid_time'=>time(),
            'bid_start_type'=>$bid_start_type,


        ];
        if($has_bid){
            $res = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_uid'=>$this->info->user_id])->update($_data);
        }else{
            $_data['bid_pid']=$pid;
            $_data['bid_uid']=$this->info->user_id;
            $res = DB::table('bidding')->insertGetId($_data);
        }

        if($res){
            return response()->json(['code'=>200,'err'=>'ok','msg'=>'update successfully','data'=>'']);
        }else{
            return response()->json(['code'=>200,'err'=>'err','msg'=>'update failed!','data'=>'']);
        }
    }

    public function pubtrial(Request $req){
        $works = $req->get('works');
        $imgs = $req->get('imgs');
        $photos_ids = [];
        $attach_ids=[];
        foreach($imgs AS $k=>$v){
            if(strlen($v) > 11 && !is_numeric($v)) {
                $_v = explode('@',$v);
                $insert_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'elements',
                        'oss_path' => $_v[0],
                        'oss_item_uid' => $this->info->user_id,
                        'width' => $_v[1],
                        'height' => $_v[2],
                    ]
                );

                $photos_ids[]=$insert_id;
            }else{
                $photos_ids[]=$v;

            }


        }
        $attach_ids = implode(",",$photos_ids);

        $pid = $req->get('pid');
        $_data = [
            'ts_tid'=>0,
            'ts_pid'=>$pid,
            'ts_uid'=>$this->info->user_id,
            'ts_photos'=>$attach_ids,
            'ts_pubtime'=>time(),
        ];

        $res = DB::table('trialsubmits')->insertGetId($_data);
        if($res){
            return response()->json(['code'=>200,'err'=>'ok','msg'=>'update successfully','data'=>'']);
        }else{
            return response()->json(['code'=>200,'err'=>'err','msg'=>'update failed!','data'=>'']);
        }

    }

    public function taketrial(Request $req){

        $pid = $req->get('pid',0);
        $res = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_uid'=>$this->info->user_id])->update(['bid_apply'=>1]);
        if($res){
            return response()->json(['code'=>200,'err'=>'ok','msg'=>'update successfully','data'=>'']);
        }else{
            return response()->json(['code'=>200,'err'=>'err','msg'=>'update failed!','data'=>'']);
        }
    }
    public function toContract(Request $req){
        $pid = $req->get('pid');
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_process_status1'=>4,'prj_uptime'=>time()]);
        if($res){
            $old_status = $this->progress($pid);
            $new_status = 4;
            if($old_status>$new_status){
                $new_status = $old_status;
            }

            $this->setProgress($pid,$new_status);
            return response()->json(['code'=>200,'err'=>'ok','msg'=>'update successfully','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['code'=>200,'err'=>'err','msg'=>'update failed!','data'=>'']);
        }

    }

    public function chatlist($id = 0){
        $bid_info =  DB::table('trialsubmits')->select('ts_uid')->where(['ts_pid'=>$id,'ts_accept'=>1])->first();
        $user_id = DB::table('project')->select('prj_final_uid')->where(['prj_id'=>$id])->first();
        //dd($bid_info);
        $chat = DB::table('build_chats')->where(['prj_id'=>$id])->get();
        $user_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$this->info->user_id])->first();
        if($user_icon->user_icon==0){
            $icon = '/images/defaultuser.png';
        }else{
            $_icon=DB::table('oss_item')->where(['oss_item_id'=>$user_icon->user_icon])->first();
            $icon = ApiConf::IMG_URI.$_icon->oss_path;
        }


        $user_icon_1 = DB::table('user')->select('user_icon')->where(['user_id'=>$user_id->prj_final_uid])->first();

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

        return $chat;
        //return response()->json(['err'=>'ok','msg'=>'','data'=>$chat]);

    }
    public function submission($id = 0){

        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id])->first();
        $this->ckstatus($data,6);
        $pay_info = DB::table('build_pays')->where(['pid'=>$id])->first();
        $bidding = DB::table('bidding')->where(['bid_pid'=>$id])->first();
        $data->start = $pay_info->pay_time;
        $data->end = $pay_info->pay_time+$bidding->bid_cost_time_h*3600+$bidding->bid_cost_time*3600*24;
        $data->nav = CreateProgress::url2($data->prj_process_status,6,$id);
        $last_work = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        if($last_work){
            $final_attach = DB::table('oss_item')->where(['oss_item_id'=>$last_work->bd_attachment])->first();
            $last_work->final_work = $final_attach;


        }

        $data->final_work = $last_work;
        //dd($last_work);
        //dd($data->final_work);

        //echo "start:".date('Y-m-d H:i:s', $data->start);
        //echo "<br/>";
        //echo "end:".date('Y-m-d H:i:s', $data->end);

        $data->lefttime = $data->end-time();


        //echo "<br/>";
        //echo "left time ".$data->lefttime;

        $data->chatlist = $this->chatlist($id);
        $final_submit = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        //dd($final_submit);



        $pay_info = DB::table('bidding')->where(['bid_pid'=>$id,'bid_uid'=>$data->prj_final_uid])->first();
        $data->bid_info = $pay_info;
        if($data->prj_result == 2){
            $report = DB::table('project_report')->where(['pid'=>$id])->first();
            $data->report = $report;
        }elseif($data->prj_result == 1){
            $rate = DB::table('project_rate')->where(['r_pid'=>$id])->first();
            $data->rate = $rate;
        }
        //dd($data);
        $data->nav = CreateProgress::url3($this->progress($id),6,$id);
        //dd($data);
        return view('v1.tasks.review',['data'=>$data,'user_info'=>$this->info]);
        //return view('v1.tasks.review_fail',['data'=>$data,'user_info'=>$this->info]);
        //return view('v1.tasks.review_pass',['data'=>$data,'user_info'=>$this->info]);


    }
    public function buildpub(Request $req){
        //dd($req->all());
        $pid = $req->get('pid',0);
        if($pid<1) exit;
        $attach = $req->get('attach','');
        $imgs   = $req->get('imgs','');
        $final  = $req->get('final',0);
        if(!$attach || !is_array($imgs)){exit;}
        $photos_ids = [];
        foreach($imgs AS $k=>$v){
            if(strlen($v) > 11 && !is_numeric($v)) {
                $_v = explode('@',$v);
                $insert_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'elements',
                        'oss_path' => $_v[0],
                        'oss_item_uid' => $this->info->user_id,
                        'width' => $_v[1],
                        'height' => $_v[2],
                    ]
                );

                $photos_ids[]=$insert_id;
            }else{
                $photos_ids[]=$v;

            }


        }
        $photos_ids = implode(",",$photos_ids);
        //dd($photos_ids);
        $_attach = explode('@',$attach);

        $_att_insert_id = DB::table('oss_item')->insertGetId(
            [
                'oss_key' => 'targets',
                'oss_path' => $_attach[0],
                'oss_item_uid' => $this->info->user_id,
                'size'=>$_attach[1],
            ]
        );
        $_data= [
            'bd_pid'=>$pid,
            'bd_uid'=>$this->info->user_id,
            'bd_photos'=>$photos_ids,
            'bd_attachment'=>$_att_insert_id,
            'bd_pubtime'=>time(),

        ];
        if($final==1){
            $_data['bd_final']=1;
        }
        $res= DB::table('builddalys')->insertGetId($_data);
        if($res){
            return response()->json(['code'=>200,'err'=>'ok','msg'=>'update successfully','data'=>'']);
        }else{
            return response()->json(['code'=>200,'err'=>'err','msg'=>'update failed!','data'=>'']);
        }

    }

    public function prjnext(Request $req){
        $pid = $req->get('pid',0);
        if($pid == 0) exit;
        $status = 2;
        $ck_data = DB::table('project')->where(['prj_id'=>$pid])->first();

        $reciver_status = 2;

        if($ck_data->prj_process_status1>2){
            $reciver_status=$ck_data->prj_process_status1;
        }
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_process_status1'=>$reciver_status,'prj_uptime'=>time()]);
        $old_status = $this->progress($pid);
        $new_status = 2;
        if($old_status>$new_status){
            $new_status = $old_status;
        }

        $this->setProgress($pid,$new_status);
        if($res){
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }
    }

    public function toTrial(Request $req){
        $pid = $req->get('pid');
        $ck_data = DB::table('project')->where(['prj_id'=>$pid])->first();
        $_ck_data = DB::table('prj_apply')->select('prj_status')->where(['prj_id'=>$pid,'user_id'=>$this->info->user_id])->first();
        $reciver_status = 3;
        if($ck_data->prj_process_status1>3){
            $reciver_status=$ck_data->prj_process_status1;
        }
        if($ck_data->prj_skip==1){
            if($_ck_data->prj_status>=4){
                $reciver_status=$_ck_data->prj_status;
            }
        }
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_uptime'=>time()]);

        if($res){
            DB::table('prj_apply')->where(['prj_id'=>$pid,'user_id'=>$this->info->user_id])->update(['prj_status'=>$reciver_status,'prj_uptime'=>time()]);
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }

    }

    public function doContract(Request $req){

        $Agreement = $req->get('Agreement',0);
        $Contract = $req->get('Contract',0);
        if(!$Agreement || !$Contract) exit;
        $pid = $req->get('pid',0);
        $ck_data = DB::table('project')->where(['prj_id'=>$pid])->first();
        if(!$ck_data) exit;
        if($ck_data->prj_signed==1) exit;
        if($ck_data->prj_final_uid!=$this->info->user_id) exit;
        $prj_status = 6;
        $prj_status_1 = 5;
        if($ck_data->prj_process_status>6){
            $prj_status = $ck_data->prj_process_status;
        }
        if($ck_data->prj_process_status1>5){
            $prj_status_1 = $ck_data->prj_process_status1;
        }
        $this->setProgress($pid,$prj_status_1);
        $res = DB::table('project')->where(['prj_id'=>$pid])->update([
            'prj_process_status'=>$prj_status,
            'prj_process_status1'=>$prj_status_1,
            'prj_signed'=>1,
            'prj_signed_seller'=>1,
            'prj_uptime'=>time(),
        ]);
        if($res){
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }

    }

    public function toBuild(Request $req){

        $pid = $req->get('pid',0);
        $ck_data = DB::table('project')->where(['prj_id'=>$pid])->first();
        $reciver_status = 6;
        if($ck_data->prj_process_status1>6){
            $reciver_status=$ck_data->prj_process_status1;
        }
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_process_status1'=>$reciver_status,'prj_uptime'=>time()]);

        if($res){
            DB::table('prj_apply')->where(['prj_id'=>$pid,'user_id'=>$ck_data->prj_final_uid])->update(['prj_status'=>$reciver_status]);
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }
    }

    public function upload(){

        return view('dev.test',['title'=>'Home','user_info'=>$this->info]);
    }
    public function fbtest(){
        return view('dev.fbtest',['title'=>'Home','user_info'=>$this->info]);
    }
    public function doupload(Request $req){
        set_time_limit(0);
        $uid = 10000;
       // dd($req->all('file'));
        $maxSize = 1024*1024*100;
        $file = $req->file('file');
        $fileSize=$file->getSize();
        if ($file->isValid()) {
            if($file->getSize()>$maxSize){
                return response()->json(['code'=>-1,'msg'=>"file too large"]);
            }
            if($file->getClientMimeType()!='application/zip'){
                return response()->json(['code'=>-1,'msg'=>"wrong foramt"]);
            }
            $ext = $file->getClientOriginalExtension();
            $realPath = $file->getRealPath();
            $filename_base_top = date('YmdHis');
            $filename_base = $filename_base_top . '/' . uniqid() ;
            $filename = $filename_base. '.' . $ext;

            $bool = \Storage::disk('tmp')->put($filename, file_get_contents($realPath));
            if($bool){
                $zip_path = \Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename;
                $extrato_path =\Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base;
                $zip = new \ZipArchive;
                $zipres = $zip->open($zip_path);
                $imgexts = ['jpg','png','gif','jpeg'];
                $objs = ['obj','fbx','stl','3dx'];
                if($zipres){
                    $zip->extractTo($extrato_path);
                    $files = Tools::read_dir_queue($extrato_path);
                    $has_model = 0;
                    foreach($files AS $k=>$v){
                        $ck_file = str_replace(\Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base.'/',"",$v);
                        $ck_fileinfo = explode('/',$ck_file);
                        $ck_filename = $ck_fileinfo[count($ck_fileinfo)-1];
                        $ck_exts = explode(".",$ck_filename);
                        if(in_array($ck_exts[count($ck_exts)-1],$objs)){
                            $has_model++;
                        }
                    }
                    if($has_model==0){
                        return response()->json(['code'=>-1,'msg'=>"parse error!"]);
                    }

                    $ossmgr = new OSSManager();
                    $oss_base_path = date('YmdHis/');
                    $target_id    = Tools::guid();
                    $oss_file   = $target_id.'.'.$ext;
                    $oss_zip_path =$oss_base_path.$oss_file;
                    $upres = $ossmgr->upload($oss_zip_path,'targets',$zip_path);
                    if($upres){
                        $_tid = DB::table('oss_item')->insertGetId([
                            'oss_key'=>'targets',
                            'oss_path'=>$oss_zip_path,
                            'oss_item_uid'=>$uid,
                            'size'=>$fileSize
                        ]);
                        $work_model_id = $_tid;
                    }

                    $work_obj_id=[];
                    $work_mets_id=[];
                    foreach($files AS $k=>$v){
                        if(strpos($v,'.')===false){
                            continue;
                        }

                        $rfile = str_replace(\Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base.'/',"",$v);
                        $oss_file_path =$oss_base_path.$rfile;
                        $upres = $ossmgr->upload($oss_file_path,'elements',$v);
                        if($upres){
                            $cap = filesize($v);
                            $_fs = explode("/",$v);
                            $_fname = $_fs[count($_fs)-1];
                            $_exts = explode(".",$_fname);
                            $_ext = $_exts[count($_exts)-1];
                            if(in_array($_ext,$imgexts)){
                                $size = getimagesize($v);
                            }else{
                                $size = [0,0];
                            }
                            $_tid = DB::table('oss_item')->insertGetId([
                                'oss_key'=>'elements',
                                'oss_path'=>$oss_file_path,
                                'oss_item_uid'=>$uid,
                                'width'=>$size[0],
                                'height'=>$size[1],
                                'size'=>$cap,
                            ]);
                            if(in_array($_ext,$objs)){
                                $work_obj_id[]=$_tid;
                            }else{
                                $work_mets_id[]=$_tid;
                            }
                        }
                    }
                    $work_obj_id = implode(",",$work_obj_id);
                    $work_mets_id = implode(",",$work_mets_id);
                    DB::table('works')->where(['work_id'=>1])->update(
                        [
                            'work_model'=>$work_model_id,
                            'work_objs'=>$work_obj_id,
                            'work_mets'=>$work_mets_id,
                        ]
                    );
                    $zip->close();
                    $res = Tools::deleteDir(\Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base_top);
                    rmdir(\Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base_top);
                    return response()->json(['code'=>200,'msg'=>"successfully"]);

                }
            }
            //dd($file);
        }
    }


}
