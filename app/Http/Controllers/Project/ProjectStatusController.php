<?php

namespace App\Http\Controllers\Project;

use App\libs\ApiConf;
use App\Services\CreateProgress;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Session;
use DB;
use App\libs\Tools;

class ProjectStatusController extends Controller
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
    }
    public function requirement($id = 0 ){
        /*/
            if($id == 0 ) abort(404);
            $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
            $this->ckstatus($data,1);
            $data->nav = CreateProgress::url2($data->prj_process_status,1,$id);
            $photo_ids = explode(',', $data->prj_photos);
            $photos = DB::table('oss_item')->whereIN('oss_item_id',$photo_ids)->get();
            foreach($photos AS $K=>$v){
                //$arr['size'] = ['width'=>$v->width, 'height'=>$v->height];
                //$arr['name'] = $v->oss_path;
                //$arr['id'] = $v->oss_item_id;
                //$serverImages[] = $arr;
                $prj_photos[]= ApiConf::IMG_URI.$v->oss_item_id;
            }

            $data->prj_photos =$prj_photos;
            $industry=DB::table('category')->where('cate_id',$data->prj_industry)->first();
            $cate=DB::table('category')->where('cate_id',$data->prj_cate)->first();
            $format=DB::table('category')->where('cate_id',$data->prj_format)->first();

            $data->prj_industry =$industry->cate_name;
            $data->prj_cate =$cate->cate_name;
            $data->prj_format =$format->cate_name;
            $_email = DB::table('user')->select('user_email')->where(['user_id'=>$this->info->user_id])->first();
            $data->email =$_email->user_email;


        return view('v2/projects/progress/requirement',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);
        */
        $project = DB::table('project')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();
        $this->ckstatus($project,1);
        $project->nav = CreateProgress::url2($project->prj_process_status,1,$id);
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
        $accs = ['Default','High','Low'];
        $project->prj_acc = $accs[$project->prj_acc-1];
        $project->email = $_email->user_email;
        $project->readonly = 0;
        if($project->prj_process_status>1){
            $project->readonly = 1;
        }
        $project->readonly;


        return view('v1.progress.requirement',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project]);

    }
    public function proposal($id=0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();

        $this->ckstatus($data,2);
        $data->nav = CreateProgress::url2($data->prj_process_status,2,$id);
        $data->lefttime = ($data->prj_pubtime+$data->prj_period*3600*24+$data->prj_period_h*3600)-time();
        //$data->lefttime = Tools::leftTime1($data->lefttime);
        $bids = $this->biddinglist($id,$this->info->user_id);

        $data->bids = $bids;
        if(!$data->bids){
            //有竞价显示列表，无竞价显示倒计时
            return view('v1.progress.noreply',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);
        }

        //dd($data);

        return view('v1.progress.proposal',['user'=>$this->info,'user_info'=>$this->info, 'data'=>$data]);
    }
    public function trial($id=0){


        if($id==0) abort(404);
        //默认显示发布测试附件界面
        $step = Input::get('step',0);
        if($step==2){

        }

        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();

        $this->ckstatus($data,3);
        $data->nav = CreateProgress::url2($data->prj_process_status,3,$id);
        if($data->prj_skip){

            return view('v1.progress.skip',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);
        }
        $trial_work = DB::table('trial_works')->where(['trial_pid'=>$id])->first();

        $bids = $this->biddinglist($id,$this->info->user_id);

        $trial_info = DB::table('trial_works')->where(['trial_pid'=>$id,'trial_uid'=>$this->info->user_id])->first();
        //显示上传附件，选择乙方界面
        if($trial_info){



            $size = '300';
            $_attach = $this->photos($trial_info->trial_attachment, $size);
            //dd($trial_info);

            $trial_info->attach = $_attach;
            $img_show_html='';
            $img_form_val='';
            foreach ($trial_info->attach AS $k => $v) {
                $v = (object)$v;
                $img_show_html .= '<li class="img-items" id="file-' . $v->id . '">
                            <span class="delete-icon" id="d-' . $v->id . '"></span>
                            <img src="' . $v->url . '" alt="">
                        </li>';
                $img_form_val .= '<input type="hidden" id="data-' . $v->id . '" name="imgs" value="' . $v->id . '">';
            }
            $trial_info->img_show_html=$img_show_html;
            $trial_info->img_form_val=$img_form_val;
        }

        if($trial_work->trial_ispub!=1){

            $trial_info->trial_type = $trial_info->trial_type==0?1:$trial_info->trial_type;

            return view('v1.progress.trial_pub',['user'=>$this->info,'user_info'=>$this->info,'trial'=>$trial_info,'data'=>$data,'bids'=>$bids]);
        }
        if($trial_info->trial_haspay!=1){

            $trial_pay_info=[];
            $trial_pay_info['trial_time']=$trial_info->trial_cost_time;
            $trial_pay_info['trial_fee']=$trial_info->trial_cost_price;
            $trial_pay_info['buyer_type']=$trial_info->trial_type==1?'Personal':'Company';
            $trial_pay_info['buyer_name']=$this->info->user_name;
            $trial_pay_info['pay_tot']=0;


            $trial_pay_info['pay_nums']=0;

            foreach($bids AS $k=>$v){
                if($v->bid_accept==1){
                    $trial_pay_info['pay_tot'] += $trial_info->trial_cost_price;
                    $trial_pay_info['pay_nums'] += 1;
                }
            }

            $bill_info = DB::table('billing_address')->where(['uid'=>$this->info->user_id])->first();

            return view('v1.progress.trial_pay',['user'=>$this->info,'user_info'=>$this->info,'trial'=>$trial_info,'data'=>$data,'bids'=>$bids,'trial_pay_info'=>(object)$trial_pay_info,'bill_info'=>$bill_info]);
            exit;

        };

        $bids = DB::table('bidding')->where(['bid_pid'=>$id,'bid_accept'=>1])->get();
        $trial_info = DB::table('trial_works')->where(['trial_pid'=>$id,'trial_uid'=>$this->info->user_id])->first();


        $trial_info->candidate=count($bids);
        $trial_info->paytot =$trial_info->candidate*$trial_info->trial_cost_price;

        $project = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();

        $bids = $this->biddinglist($id,$this->info->user_id);

        $inperiod = time()>=$project->prj_set_period_time+$project->prj_period*3600 ? 0 :1;
        $trial_submits = DB::table('trialsubmits')->where(['ts_pid'=>$id,'ts_uid'=>$this->info->user_id])->get();

        $has_submit = 0;
        foreach($trial_submits AS $k1=>$v1){
            $trial_submits[$k1]->covers =  $this->photos($v1->ts_photos,'');
            if($v1->ts_attachment){
                $has_submit++;
            }
        }
        if($has_submit){

            //已支付，没有提交前显示支付结果界面
            //dd($trial_info->trial_attachment);
            $pic = $this->photos($trial_info->trial_attachment,'');
            /*
            $_pics = '';
            foreach($pic AS $k=>$v){
                $_pics .=  $v->url.";";
            }
            */

            $trial_info->attach = $pic;
           // dd($trial_info->attach);
            $due = $trial_info->trial_paytime+$trial_info->trial_cost_time*3600;
            $trial_info->due = date('Y-m-d H:i:s',$due);
            foreach($bids AS $k=>$v){

                if($v->bid_accept!=1){
                    unset($bids[$k]);
                }
            }
            $trial_info->pay_tot = $trial_info->trial_cost_price * count($bids);
            $trial_info->pay_method = $trial_info->trial_payway==1?'CreditCard':'Paypal';
            $trial_info->paytime = date('Y-m-d H:i:s',$trial_info->trial_paytime);

           //dd($bids);


            return view('v1.progress.trial_pay_success',['user'=>$this->info,'user_info'=>$this->info,'trial'=>$trial_info,'data'=>$data,'bids'=>$bids]);
            exit;
        }
        //dd($this->photos($trial_submits->ts_photos),'');
        //dd($trial_submits);

        $_ts = DB::table('bidding')->leftJoin('user','bidding.bid_uid','=','user.user_id')->select('bidding.*','user.user_icon')->where(['bidding.bid_accept'=>1,'bidding.bid_pid'=>$id])->get();
        $has_submit =0;
        foreach ($_ts AS $k=>$v){
            $__ts = DB::table('trialsubmits')->where(['ts_pid'=>$v->bid_pid,'ts_uid'=>$v->bid_uid])->first();
            if($__ts && $__ts->ts_photos){
                $has_submit++;
                $_pss = explode(",",$__ts->ts_photos);
                $_psarr = [];
                foreach($_pss AS $k1=>$v1){
                    $_ps = DB::table('oss_item')->where(['oss_item_id'=>$v1])->first();
                    $_psarr[]=ApiConf::IMG_URI.$_ps->oss_path;

                }

                $_ts[$k]->work_imgs = $_psarr[0];
                $_ts[$k]->work_imgs_str = implode(",",$_psarr);
                $_ts[$k]->work_imgs_tot = count($_psarr);
                $_ts[$k]->tral_sub_time = $__ts->ts_pubtime;
                $_ts[$k]->tid = $__ts->ts_id;
                $_ts[$k]->rate = $__ts->ts_rate?$__ts->ts_rate:0;
            }else{
                $_ts[$k]->work_imgs = '';

            }
            if($v->user_icon){
                $_icon = DB::table('oss_item')->where(['oss_item_id'=>$v->user_icon])->first();
                $_ts[$k]->icon = ApiConf::IMG_URI.$_icon->oss_path;

            }else{
                $_ts[$k]->icon ='';
            }

        }
        $left = $trial_info->trial_cost_time*3600+$trial_info->trial_uptime-time();

        //$left = $project->prj_period*24*3600+$project->prj_period_h*3600-time();
        $left = ($trial_info->trial_cost_time*24*3600+$trial_info->trial_paytime)-time();


        return view('v1.progress.trial_result',['user'=>$this->info,'user_info'=>$this->info,'project'=>$project,'data'=>$data,'ts'=>$_ts,'has_submit'=>$has_submit,'left'=>$left]);
    }
    private function biddinglist($pid,$uid){


        $bids = DB::table('bidding')->leftJoin('user','bidding.bid_uid','=','user.user_id')
            ->select('bidding.*','user.user_fileds','user.user_icon')
            ->where(['bid_pid'=>$pid])->get();
        foreach($bids AS $k=>$v){

            $portforlios = DB::table('element')->select('element_cover_id')->where(['user_id'=>$v->bid_uid])->orderBy('element_id','DESC')->limit(10)->get();
            $portforlio = $portforlios[0];
            if($portforlio){
                $_icon = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$v->user_icon])->first();
                $_icon = ApiConf::IMG_URI.$_icon->oss_path.'@0o_0l_100w_90q.src';
                $bids[$k]->_cover = $_icon;
            }else{
                $bids[$k]->_cover = '';
            }
            $_recent_works = '';
            $_recent_work = '';

            foreach($portforlios AS $k1=>$v1){
                $_p = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$v1->element_cover_id])->first();
                if($k1==0){
                    $_recent_work =  ApiConf::IMG_URI.$_p->oss_path.'@0o_0l_100w_90q.src';
                }
                $_recent_works .= ApiConf::IMG_URI.$_p->oss_path.",";
            }
            $_recent_works = rtrim($_recent_works,',');
            $bids[$k]->images = $_recent_works;
            $bids[$k]->image = $_recent_work;
            $bids[$k]->images_tot = count($portforlios);
            $_fileds = [];
            $_css = ['yello-note','blue-note','green-note'];
            if($v->user_fileds){
                $fileds = explode(",",$v->user_fileds);

                foreach($fileds AS $k1=>$v1){
                    $_fileds[]=['_css'=>$_css[$k1],'val'=>$this->__fileds[$v1]];
                }
            }
            $bids[$k]->fileds = (object)$_fileds;
            $bids[$k]->ami = '89';
        }
        return $bids;
    }
    public function trialpay($id = 0){
        $bids = DB::table('bidding')->where(['bid_pid'=>$id,'bid_accept'=>1])->get();
        $trial_info = DB::table('trial_works')->where(['trial_pid'=>$id,'trial_uid'=>$this->info->user_id])->first();
        $trial_info->candidate=count($bids);
        $trial_info->paytot =$trial_info->candidate*$trial_info->trial_cost_price;
        dd($trial_info);



    }
    public function contract($id= 0){

        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        $this->ckstatus($data,4);
        $data->nav = CreateProgress::url2($data->prj_process_status,4,$id);
        $trial_submit = DB::table('trialsubmits')->leftJoin('user','trialsubmits.ts_uid','=','user.user_id')
            ->select('trialsubmits.*','user.user_fileds','user.user_icon')
            ->where(['ts_pid'=>$id])->get();

        foreach($trial_submit AS $k=>$v) {
            if ($v->user_icon) {
                $_icon = DB::table('oss_item')->select('oss_path')->where(['oss_item_id' => $v->user_icon])->first();
                $_icon = ApiConf::IMG_URI . $_icon->oss_path . '@0o_0l_100w_90q.src';
                $trial_submit[$k]->_cover = $_icon;
            } else {
                $trial_submit[$k]->_cover = '';
            }
            $_fileds = [];


            $_css = ['yello-note', 'blue-note', 'green-note'];
            if ($v->user_fileds) {
                $fileds = explode(",", $v->user_fileds);

                foreach ($fileds AS $k1 => $v1) {
                    $_fileds[] = ['_css' => $_css[$k1], 'val' => $this->__fileds[$v1]];
                }
            }
            $trial_submit[$k]->fileds = (object)$_fileds;
            $trial_submit[$k]->ami = '89';
            //$trial_submit[$k]->photos = $this->photos($v->ts_photos,'');

            $_parr = explode(",", $v->ts_photos);
            $_pimgs = DB::table('oss_item')->select('oss_path')->whereIn('oss_item_id', $_parr)->get();
            $att_cover = '';
            $att_pics = '';
            foreach ($_pimgs AS $k1 => $v1) {
                if ($k1 == 0) {
                    $att_cover = ApiConf::IMG_URI . $v1->oss_path . '@0o_0l_100w_90q.src';
                }
                $att_pics .= ApiConf::IMG_URI . $v1->oss_path . '@0o_0l_100w_90q.src' . ",";
            }
            $trial_submit[$k]->photo = $att_cover;
            $trial_submit[$k]->photos = rtrim($att_pics, ",");
            $trial_submit[$k]->photos_tot=count($_pimgs);
            $_cost = DB::table('bidding')->where(['bid_pid'=>$id,'bid_uid'=>$v->ts_uid])->first();
            $trial_submit[$k]->cost_hours=$_cost->bid_cost_time*24+$_cost->bid_cost_time_h;
            $trial_submit[$k]->cost=$_cost->bid_price;
            $trial_submit[$k]->start=$_cost->bid_start_type==1?'Anytime':date('Y-m-d H:i:s',$_cost->bid_start_time);

        }
        if($data->prj_skip==1){
            $bids = $this->biddinglist($id,$this->info->user_id);
            $data->bids = $bids;
        }
        $trial_work_info = DB::table('trial_works')->where(['trial_pid'=>$id])->first();
        //dd($trial_work_info);
        //dd($trial_submit);
        return view('v1.progress.contract',['user'=>$this->info,'user_info'=>$this->info,'trial_submit'=>$trial_submit,'data'=>$data,'trial_work'=>$trial_work_info]);

    }
    public function payment($id){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        $this->ckstatus($data,5);
        $data->nav = CreateProgress::url2($data->prj_process_status,5,$id);
        $ts_data = DB::table('build_pays')->where(['pid'=>$id,'uid'=>$this->info->user_id])->first();

        //dd($ts_data);
        if(!$ts_data){
            //显示支付表单
            $bid_info = DB::table('bidding')->where(['bid_pid'=>$id,'bid_uid'=>$data->prj_final_uid])->first();
            $bid_info->buyername = $this->info->user_name;
            $bill_info = DB::table('billing_address')->where(['uid'=>$this->info->user_id])->first();
            $trial_type = DB::table('trial_works')->select('trial_type')->where(['trial_pid'=>$id])->first();
            $bid_info->trial_type = $trial_type->trial_type;

            return view('v1.progress.contract_pay',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data,'bid_info'=>$bid_info,'bill_info'=>$bill_info]);

        }

        //dd($data);
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
        //dd($data);
        $paid_info=(object)$paid_info;
        if($data->prj_signed_seller) {

            return view('v1.progress.contract_pay_result', ['user' => $this->info, 'user_info' => $this->info, 'data' => $data, 'paid_info' => $paid_info]);
        }else{
            return view('v1.progress.wait_seller_sign', ['user' => $this->info, 'user_info' => $this->info, 'data' => $data, 'paid_info' => $paid_info]);
        }
    }
    public function building($id = 0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        $this->ckstatus($data,6);
        $pay_info = DB::table('build_pays')->where(['pid'=>$id])->first();
        $bidding = DB::table('bidding')->where(['bid_pid'=>$id])->first();
        $data->start = $pay_info->pay_time;
        $data->end = $pay_info->pay_time+$bidding->bid_cost_time_h*3600+$bidding->bid_cost_time*3600*24;
        $data->nav = CreateProgress::url2($data->prj_process_status,6,$id);
        $last_work = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        if($last_work){
            $final_attach = DB::table('oss_item')->where(['oss_item_id'=>$last_work->bd_attachment])->first();
            $_attach = explode("/",$final_attach->oss_path);
            $final_attach->name = $_attach[count($_attach)-1];
            $final_attach->size = Tools::sizeConvert($final_attach->size);
            $final_attach->url =ApiConf::IMG_URI.$final_attach->oss_path;
            $last_work->final_work = $final_attach;




        }
        $_icon = DB::table('user')->select('user_icon','user_name','user_lastname')->where(['user_id'=>$data->prj_final_uid])->first();
        $_oss = $this->getOssPath($_icon->user_icon);
        $data->ouser = [
            'icon'=>ApiConf::IMG_URI.$_oss->oss_path,
            'name'=>$_icon->user_name.' '.$_icon->user_lastname,
            ];

        //dd($data->ouser);
        $data->final_work = $last_work;

        //echo "start:".date('Y-m-d H:i:s', $data->start);
        //echo "<br/>";
        //echo "end:".date('Y-m-d H:i:s', $data->end);

        $data->lefttime = $data->end-time();


        //echo "<br/>";
        //echo "left time ".$data->lefttime;

        $data->chatlist = $this->chatlist($id);
        $final_submit = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        //dd($final_submit);
        



        $data->nav = CreateProgress::url2($data->prj_process_status,6,$id);
        //dd($data);
        //dd($data);
        $data->start_str = date('Y-m-d',$data->start);
        $data->end_str = date('Y-m-d',$data->end);
        $data->today_str = date('Y-m-d');
        return view('v1.progress.building',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);

    }
    public function submission($id = 0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        $this->ckstatus($data,7);
        $data->nav = CreateProgress::url2($data->prj_process_status,7,$id);
        $pay_info = DB::table('build_pays')->where(['pid'=>$id])->first();
        $bidding = DB::table('bidding')->where(['bid_pid'=>$id])->first();
        $data->start = $pay_info->pay_time;
        $data->end = $pay_info->pay_time+$bidding->bid_cost_time_h*3600+$bidding->bid_cost_time*3600*24;
        $ck_rate = DB::table('project_rate')->where(['r_pid'=>$id])->first();
        $last_work = DB::table('builddalys')->where(['bd_pid'=>$id,'bd_final'=>1])->first();
        if($last_work){
            $final_attach = DB::table('oss_item')->where(['oss_item_id'=>$last_work->bd_attachment])->first();
            $_attach = explode("/",$final_attach->oss_path);
            $final_attach->name = $_attach[count($_attach)-1];
            $final_attach->size = Tools::sizeConvert($final_attach->size);
            $final_attach->url =ApiConf::IMG_URI.$final_attach->oss_path;
            $last_work->final_work = $final_attach;


        }
        $data->final_work = $last_work;

        if($ck_rate){

        }else{

        }


        return view('v1.progress.submission',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);

    }
    public function submissionDeny($id = 0){
        if($id==0) abort(404);
        $data = DB::table('project')->where(['prj_id'=>$id,'prj_uid'=>$this->info->user_id])->first();
        $this->ckstatus($data,7);
        $data->nav = CreateProgress::url2($data->prj_process_status,7,$id);
        $ck_rate = DB::table('project_rate')->where(['r_pid'=>$id])->first();
        if($ck_rate){

        }else{

        }
        return view('v1.progress.submissiondeny',['user'=>$this->info,'user_info'=>$this->info,'data'=>$data]);

    }
    private function photos($str, $size){
        $arr = explode(",", $str);
        $_ps = [];
        $_imgs = DB::table('oss_item')->whereIn('oss_item_id',$arr)->get();

        foreach($_imgs AS $k=>$v){
            $_tpm=[];
            $_tmp['id']  = $v->oss_item_id;
            $_tmp['url'] = ApiConf::IMG_URI.$v->oss_path.$size;
            $_ps[]=$_tmp;

        }
        return (object)$_ps;
    }
    public function builddaly($pid,$uid,$time){
        DB::table('build_pays')->where(['pid'=>$pid,'uid'=>$uid])->first();

    }
    public function chatlist($id = 0){
        $bid_info =  DB::table('trialsubmits')->select('ts_uid')->where(['ts_pid'=>$id,'ts_accept'=>1])->first();
        $user_id = DB::table('project')->select('prj_final_uid')->where(['prj_id'=>$id])->first();
        //dd($user_id);
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
    public function chat(){}
    public function pubrequirement(Request $req){
        $pid = $req->get("pid",0);
        if($pid==0){exit;}
        $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update(['prj_process_status'=>2]);
        if($res){

            return response()->json(['msg'=>'update successfully','err'=>'ok','data'=>'']);
        }else{
            return response()->json(['msg'=>'error','err'=>'error','data'=>'']);
        }
    }
    public function pubproposal(Request $req){
        $pid = $req->get("pid");
        if($pid==0) exit;
        $ck_prj = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        if(!$ck_prj) exit;
        $status = $ck_prj->prj_process_status<3?3:$ck_prj->prj_process_status;
        $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update(['prj_process_status'=>$status,'prj_uptime'=>time()]);
        if($res){
            return response()->json(['msg'=>'update successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'error','err'=>'error','data'=>'']);
        }
    }
    public function pubtrial(Request $req){
        //dd($req->all());

        $pid = $req->get('pid',0);
        $ck_trial = DB::table('trial_works')->where(['trial_pid'=>$pid])->count();
        $desc = $req->get('desc',NULL);
        $fee = $req->get('fee',0);
        //$ClientType = $req->get('ClientType',1);
        $attach = $req->get('imgs',[]);
        $userids = $req->get('userids',[]);
        $trial_time=$req->get('trial_time',0);
        //$att_file = $req->get('attach','');
        if(count($userids)<1) exit;
        $attach_ids=[];

        foreach($attach AS $k=>$v){
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

                $attach_ids[]=$insert_id;
            }else{
                $attach_ids[]=$v;

            }


        }
        $attach_ids = implode(",",$attach_ids);

        /*
        if(strlen($att_file) > 11 && !is_numeric($att_file)){
            $_att_files = explode("@",$att_file);
            $insert_id = DB::table('oss_item')->insertGetId(
                [
                    'oss_key' => 'attachment',
                    'oss_path' => $_att_files[0],
                    'oss_item_uid' => $this->info->user_id,
                    'size'=>$_att_files[1]
                ]
            );
            $att_file = $insert_id;
        }
        */
        $_data = [
            'trial_desc'=>$desc,
            'trial_attachment'=>$attach_ids,
            'trial_cost_time'=>$trial_time,
            'trial_cost_price'=>$fee,
            //'trial_type'=>$ClientType,
            'trial_uptime'=>time(),
        ];
        /*
        if($ClientType==1){
            $firstname = $req->get('firstname','');
            $lastname = $req->get('lastname','');
            $_data['buyer_firstname']=$firstname;
            $data['buyer_lastname']=$lastname;
            $_data['trial_ispub']=1;

        }elseif($ClientType==2){
            $companyname = $req->get('companyname','');
            $companycode = $req->get('companycode','');
            $vatnumber = $req->get('vatnumber','');
            $country = $req->get('country','');
            $city = $req->get('city','');
            $address = $req->get('address','');
            $_data['company_name'] = $companyname;
            $_data['company_code'] = $companycode;
            $_data['vat_number'] = $vatnumber;
            $_data['com_country'] = $country;
            $_data['com_city'] = $city;
            $_data['com_address'] = $address;
            $_data['trial_ispub']=1;

        }
        */

        if($ck_trial){
            $_data['trial_ispub']=1;
           $res = DB::table('trial_works')->where(['trial_pid'=>$pid])->update($_data);
        }else{
            $_data['trial_pubtime']=time();
            $_data['trial_pid']=$pid;
            $_data['trial_uid']=$this->info->user_id;
            $_data['trial_ispub']=1;
           $res =  DB::table('trial_works')->insertGetId($_data);
        }
        if($res){
            foreach($userids AS $v){
                DB::table('bidding')->where(['bid_pid'=>$pid,'bid_uid'=>$v])->update(['bid_accept'=>1]);
            }
            return response()->json(['err'=>'ok','msg'=>'publish successfully!','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['err'=>'ok','msg'=>'publish failed!','data'=>'']);
        }



    }
    public function tocontract(Request $req){
        $pid = $req->get('pid',0);
        $rates = $req->get('rates',[]);
        $data =DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        if(!$data) exit;
        $ck_sub = DB::table('trialsubmits')->where(['ts_pid'=>$pid])->count();
        if(!$ck_sub) exit;

        $status = $data->prj_process_status<=3?4:$data->prj_process_status;
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_process_status'=>$status,'prj_uptime'=>time()]);
        if($res){
            if($rates){
                foreach($rates AS $k=>$v){
                    $_rate = explode('-',$v);
                    DB::table('trialsubmits')->where(['ts_id'=>$_rate[1]])->update(['ts_rate'=>$_rate[2]]);
                }
            }

            return response()->json(['err'=>'ok','msg'=>'publish successfully!','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['err'=>'error','msg'=>'publish failed!','data'=>'']);
        }
    }
    public function pubcontract(Request $req){

        $uid = $req->get('uid',0);
        $pid = $req->get('pid',0);
        $skip = $req->get('skip',0);
        if($pid==0 || $uid == 0) exit;
        $client_type = $req->get('ClientType',0);
        $_data = [
            'trial_pid'=>$pid,
            'trial_type'=>$client_type,
            'trial_uptime'=>time(),
        ];
        if($client_type == 1) {
            $firstname = $req->get('firstname','');
            $lastname = $req->get('lastname','');
            $_data['buyer_firstname']  = $firstname;
            $_data['buyer_lastname']  = $lastname;
        }elseif($client_type==2){
            $companyname = $req->get('companyname','');
            $companycode = $req->get('companycode','');
            $vatnumber = $req->get('vatnumber','');
            $country = $req->get('country','');
            $city = $req->get('city','');
            $address = $req->get('address','');
            $_data['company_name']      =   $companyname;
            $_data['company_code']      =   $companycode;
            $_data['vat_number']        =   $vatnumber;
            $_data['com_country']       =   $country;
            $_data['com_city']          =   $city;
            $_data['com_address']       =   $address;
        }

        $res = DB::table('trial_works')->where(['trial_pid'=>$pid])->update($_data);

        if($res){
            $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update(['prj_process_status'=>5,'prj_uptime'=>time(),'prj_final_uid'=>$uid]);
            if($res){
                if($skip==1){
                    DB::table('prj_apply')->where(['prj_id'=>$pid,'user_id'=>$uid])->update(['prj_status'=>4]);
                    DB::table('bidding')->where(['bid_pid'=>$pid,'bid_uid'=>$uid])->update(['bid_accept'=>1]);
                }
                return response()->json(['err'=>'ok','msg'=>'publish successfully!','data'=>'','pid'=>$pid]);
            }else{
                return response()->json(['err'=>'error','msg'=>'publish failed!','data'=>'']);
            }
        }






    }

    public function pubpayment(Request $req){
        $pid = $req->get('pid',0);
        if($pid==0) exit;
        $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update(['prj_process_status'=>6]);
        if($res){
            echo 1;
        }

    }
    public function pubsubmission(Request $req){
        $pid = $req->get('pid',0);
        if($pid==0) exit;
        $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update(['prj_process_status'=>7,'prj_uptime'=>time()]);
        if($res){
            echo 1;
        }
    }
    public function trialRate(Request $req){
        $pid = $req->get('pid');
        $tid = $req->get('tid');
        $score = $req->get('score');
        $ck_score =DB::table('trialsubmits')->where(['ts_pid'=>$pid,'ts_id'=>$tid])->first();
        if(!$ck_score->ts_rate){
            $res = DB::table('trialsubmits')->where(['ts_pid'=>$pid,'ts_id'=>$tid])->update(['ts_rate'=>$score]);
            if($res){
                echo 1;
            }
        }

    }
    public function rate(Request $req){
        $pid = $req->get('pid');
        $time_score = $req->get('Time');
        $quality_score = $req->get('Quality');
        $other_score = $req->get('Commucation');
        $c_final = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        if(!$c_final || $c_final->prj_result!==null) exit;
        $final_uid = $c_final->prj_final_uid;
        $res = DB::table('project_rate')->insertGetId(
            [
                'r_time'=>$time_score,
                'r_quality'=>$quality_score,
                'r_other'=>$other_score,
                'r_pid'=>$pid,

            ]
        );
        if($res){
            DB::table('project')->where(['prj_id'=>$pid])->update(['prj_result'=>1,'prj_process_status1'=>7]);
            DB::table('prj_apply')->where(['prj_id'=>$pid,'user_id'=>$final_uid])->update(['prj_status'=>7]);
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }


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
        //DB::table('projects')->where(['prj_id'=>$pid])->update(['prj_status'=>7]);
        if($res){
            DB::table('project')->where(['prj_id'=>$pid])->update(['prj_result'=>2,'prj_process_status1'=>7,'prj_status'=>7]);
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }

    }
    public function sendchat(Request $req){
        $con = $req->get('content');
        $pid = $req->get('pid');
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
        $res = DB::table('build_chats')->insertGetId($_data);
        if($res){
            $_data = DB::table('build_chats')->select('content','pubtime')->where(['id'=>$res])->first();
            $_data->pubtime = date('Y-m-d H:i:s');
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>$_data,'pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }

    }
    public function daywork(Request $req){
        $pid = $req->get('pid','1');
        $day = $req->get('day');
        $_day = explode("-",$day);
        $day_start = strtotime($day);
        $day_end = $day_start+3600*24;
        $work = DB::table('builddalys')->where(['bd_pid'=>$pid])->whereRAW("bd_pubtime>=".$day_start." AND bd_pubtime <".$day_end)->first();

        $result = [];
        if($work){
            $works =$this->photos($work->bd_photos,'');
            $worksnum = count((array)$works);
            $_attach = DB::table('oss_item')->select('oss_path','size')->where(['oss_item_id'=>$work->bd_attachment])->first();
            $_attach->size = Tools::sizeConvert($_attach->size);
            $__attach = explode("/",$_attach->oss_path);
            $_attach->oss_path= $__attach[count($__attach)-1];
            $img_src='';
            $cover = '';
            foreach ($works AS $k=>$v){
                if($k==0){
                    $cover = $v['url'];
                }
                $img_src .=$v['url'].",";
            }
            $img_src =rtrim($img_src,',');
            $result['attach']=$_attach;
            //var_dump($works);
            $result['photos']=['photos'=>$img_src,'cover'=>$cover,'pic_tot'=>count((array)$works)];



        }
        if($result){
            return response()->json(['err'=>'ok','msg'=>'','data'=>$result]);
        }else{
            return response()->json(['err'=>'error','msg'=>'no data','data'=>'']);
        }
    }

    public function selectTrial(Request $req){
        $pid = $req->get('pid',0);
        $trial_name = $req->get('trial_name','');
        $trial_file = $req->get('trial_file','');
        $trial_desc = $req->get('trial_desc',null);
        $trial_fee = $req->get('trial_fee','');
        $trial_time = $req->get('trial_time','');
        $trial_modler = $req->get('modeler','');
        $client_type = $req->get('client_type',1);
        $personal = $req->get('client_info','');
        $company = $req->get('company','');
        $trial_file_size = $req->get('trial_file_size',0);
        $condation = [
            'trial_pid'=>$pid,
            'trial_uid'=>$this->info->user_id,


        ];
        $ck_trial_work = DB::table('trial_works')->where($condation)->first();
        $_data=[
            'trial_name'=>$trial_name,
            'trial_desc'=>$trial_desc,
            'trial_pubtime'=>time(),
            'trial_draft'=>1,
            'trial_uptime'=>time(),
            'trial_cost_time'=>$trial_time,
            'trial_cost_price'=>$trial_fee,
            'trial_type'=>$client_type,

        ];
        if($client_type==1){
            $_data['buyer_firstname']=$personal['firstname'];
            $_data['buyer_lastname']=$personal['lastname'];

        }elseif($client_type==2){
            $_data['company_name']      =   $company['company_name'];
            $_data['company_code']      =   $company['company_code'];
            $_data['vat_number']        =   $company['vat_number'];
            $_data['com_country']       =   $company['com_country'];
            $_data['com_city']          =   $company['com_city'];
            $_data['com_address']       =   $company['com_address'];


        }
        if(is_numeric($trial_file)){
            $_data['trial_attachment'] = $trial_file;
        }else{
            $trial_file_id = DB::table('oss_item')->insertGetId(
                [
                    'oss_key' => 'attachment',
                    'oss_path' => $trial_file,
                    'oss_item_uid' => $this->info->user_id,
                    'size' => $trial_file_size,

                ]
            );
            if($trial_file_id){
                $_data['trial_attachment'] = $trial_file_id;
            }
        }
        if($ck_trial_work){
            $res = DB::table('trial_works')->where($condation)->update($_data);
            if($res){
                echo 1;
            }
        }else{
            $res = DB::table('trial_works')->insertGetId($_data);
            if($res){
                echo 1;
            }
        }
        foreach($trial_modler AS $k=>$v){
            echo DB::table('biddings')->where(['bid_uid'=>$v,'bid_pid'=>$pid])->update(['bid_accept'=>1]);
        }

    }
    public function prjdays(Request $req){
        $pid = $req->get('pid');
        if($pid<=0) exit;
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



        $bid_info = DB::table('bidding')->where(['bid_pid'=>$pid])->first();
        //dd($bid_info);
        $pay_info = DB::table('build_pay')->where(['pid'=>$pid])->first();

        $due_time = $pay_info->pay_time+$bid_info->bid_cost_time*3600;
        //echo $due_day = date('Y-m-d H:i:s',$due_time);
        $real_start = $pay_info->pay_time;
        $range_days = Tools::days($real_start,$due_time,$req_m);
        $rdays='[';

        $rdays .= implode(",",$range_days);
        $rdays.=']';
        $day_start = 1490868700;
        $day_end = 1490869723;
        $work = DB::table('builddaly')->where(['bd_pid'=>$pid])->whereRAW("bd_pubtime>=".$day_start." AND bd_pubtime <".$day_end)->first();
        $result = (object)[];
        if($work){
            $result->works =$this->photos($work->bd_photos,'');
            $result->worksnum= count((array)$result->works);
        }
        $ret = ['date_str'=>date('M Y',$newstart),'date'=>date('Y-m',$newstart),'days'=>$range_days,'newstart'=>date('Y-m-d H:i:s',$newstart),'real_start'=>date('Y-m-d H:i:s',$real_start),'req'=>$req_month,'work'=>$result];
        return response()->json(['code'=>'200','data'=>$ret]);

    }
    public function prjnext(Request $req){
        $pid = $req->get('pid',0);
        if($pid == 0) exit;
        $status = 2;
        $ck_data = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        if($ck_data->prj_process_status>2){
            $status=$ck_data->prj_process_status;
        }
        $reciver_status = 1;
        if($ck_data->prj_process_status1>1){
            $reciver_status=$ck_data->prj_process_status1;
        }
        $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update(['prj_process_status'=>$status,'prj_process_status1'=>$reciver_status,'prj_uptime'=>time(),'prj_ispub'=>1]);

        if($res){
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }
    }

    public function contractbyBuyer(Request $req){
        $pid = $req->get('pid');

        //$res =  DB::table('trialsubmits')->where(['ts_pid'=>$pid])->update(['ts_accept'=>1]);
        if(1){
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }
    }
    public function skip(Request $req){
        $pid = $req->get('pid');
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_skip'=>1,'prj_uptime'=>time(),'prj_process_status'=>4]);
        if($res){
            //DB::table();
            return response()->json(['msg'=>'successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'failed','err'=>'error','data'=>'']);
        }
    }

}
