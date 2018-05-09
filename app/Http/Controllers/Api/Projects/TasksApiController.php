<?php

namespace App\Http\Controllers\Api\Projects;

use App\Events\BuildingEvent;
use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Http\Controllers\Api\BaseApiController;
use App\Jobs\Job;
use App\Jobs\ProjectTiming;
use App\libs\StaticConf;
use App\Model\ApplyCash;
use App\Model\BuildDaily;
use App\Model\BuildMark;
use App\Model\BuildPay;
use App\Model\Cate;
use App\Model\Country;
use App\Model\MarkResponse;
use App\Model\Notify;
use App\Model\Ossitem;
use App\Model\PrjApply;
use App\Model\PrjInvite;
use App\Model\Project;
use App\Model\ProjectRate;
use App\Model\Rai;
use App\Model\User;
use App\Model\Wallet;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class TasksApiController extends BaseApiController
{
    public function lists(){
        $_w =500;
        $_q= 90;
        if($this->Mobile()){
            $_w =1020;
            $_q= 70;
        }
        $cate = Input::get('act',0);
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',10);
        $offset = ($page-1)*$page_size;
        $where = 'prj_progress=1 AND prj_type =0 ';
        if($cate){
            $where .= ' AND prj_category='.$cate;
        }
        $tot = Project::whereRAW($where)->count();
        $projects = Project::select('prj_uid','prj_id','prj_name','prj_photos','prj_industry','prj_views','created_at','prj_time_day')
            ->whereRAW($where)
            ->where('prj_industry','!=',0)
            ->where('prj_delete','!=',1)
            ->with('user')
            ->take($page_size)
            ->skip($offset)
            ->orderBy('prj_id','DESC')
            ->get();
        foreach($projects AS $k=>$v){
            $bid_count = PrjApply::where('prj_id',$v->prj_id)->count();
            $projects[$k]->prj_cover = $this->getOssPath($v->prj_photos,'500');
            $projects[$k]->prj_views = $v->prj_views;
            $projects[$k]->prj_bids  = $bid_count;
            $projects[$k]->user_avator = $this->getAvatar($v->user->user_icon,'100');
            $projects[$k]->user_type = $v->user->user_type;
            $projects[$k]->user_name = $this->getName($v->user);
            $_industry = $v->industry;
            $projects[$k]->prj_industry  = $this->lang=='zh'?$_industry->cate_name_cn:$_industry->cate_name;
            unset($projects[$k]->industry);
            unset($projects[$k]->prj_photos);
            unset($projects[$k]->user,$projects[$k]->prj_time_day);
            $redirect = 0;//跳转到详情界面中
            if($this->_user){
                $user_id = $this->_user->user_id;
                if($projects[$k]->prj_uid==$user_id){
                    $redirect = 1;//跳转到甲方进度中
                }else{
//                    $ck_myapply = PrjApply::where('prj_id',$v->prj_id)->where('user_id',$user_id)->count();
//                    $redirect=$ck_myapply==0?0:2;
                      $redirect = 0;//跳转到详情界面中
                }
            }
            $projects[$k]->redirect  = $redirect;
        }
        if(count($projects)>0){
            return $this->jsonOk('ok',['tasks'=>$projects,'pages'=>ceil($tot/$page_size)]);
        }else{
            return $this->jsonErr("No More Data");
        }
    }

    public function detail(){
        $id = Input::get('id');
        $project = Project::with('apply')->find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $project->prj_views+=1;
        $project->save();
        $status = 0;
        if($this->_user){
            if($this->_user->user_id==$project->prj_uid){
                $status =1;
            }else{
                $apply = PrjApply::where(['prj_id'=>$id,'user_id'=>$this->_user->user_id])->first();
                if($apply){
                    if($apply->is_apply>=1){
                        $status = 1;
                    }else{
                        $status = 0;
                    }
                }else{
                    $status = 0;
                }
            }
        }
        $category = explode(",",$project->prj_category);
        $cates =  Cate::whereIn('cate_id',$category)->get();
        $cate = [];
        switch ($this->lang) {
            case 'zh':
                $industry = $project->industry->cate_name_cn;
                foreach($cates AS $k=>$v){
                        $cate[]=$v->cate_name_cn;
                }
                $resolution = StaticConf::$resolution_zh[$project->prj_accuracy]['name'];
                $budget = StaticConf::$budget_zh[$project->prj_budget]['name'];
                $expect = StaticConf::$expect_times_zh[$project->prj_expect]['name'];
                break;
            default:
                $industry = $project->industry->cate_name;
                foreach($cates AS $k=>$v){
                    $cate[]=$v->cate_name;
                }
                $resolution = StaticConf::$resolution[$project->prj_accuracy]['name'];
                $budget = StaticConf::$budget[$project->prj_budget]['name'];
                $expect = StaticConf::$expect_times[$project->prj_expect]['name'];
                break;
        }
        $photo = $this->getOssPath($project->prj_photos,'500');
        $data['project'] =[
            'prj_id'=>$project->prj_id,
            'prj_name'=>$project->prj_name,
            'prj_photos'=>$photo,
            'prj_industry'=>$industry,
            'prj_category'=>implode(",",$cate),
            'prj_tags'=>explode(",",$project->prj_tags)?explode(",",$project->prj_tags):'',
            'prj_resolution'=>$resolution,
            'prj_model_number'=>$project->prj_models_tot,
            'prj_budget'=>$project->prj_budget? $budget : '',
            'prj_duration'=>$project->prj_expect? $expect :'',
            'prj_view'=>$project->prj_views,
            'prj_apply'=>count($project->apply),
            'status'=>$status
        ];
        return $this->jsonOk("ok",['detail'=>$data]);

    }


    /**
     * 报价
     * @param Request $request
     * @return mixed
     */
    public function offer(Request $request)
    {
        $user = $this->_user;
        if($user->user_id < 3){
            return $this->jsonErr("you have no permission");
    }
        $project = Project::where('prj_id',$request->get('id'))->first();
        if($project->prj_progress>1){
            return $this->jsonErr('Project can`t apply');
        }else{
            $this->validate($request,[
                'time'=>'required|numeric|min:1',
                'price'=>'required|numeric|min:0.01'
            ]);
                $apply = new PrjApply();
                $apply->prj_id = $request->get('id');
                $apply->user_id = $user['user_id'];
                $apply->apply_time = time();
                $apply->apply_cost_time = $request->get('time');
                $apply->apply_price = $request->get('price');
                $apply->apply_status = 1;
                $user->save();
                if($apply->save()){
                    \Event::fire(new NotifyEvent(6,$project->prj_uid));
                    $url = env('CLIENT_BASE').'proposal-a/'.$project->prj_id;
//                    Mail::send('emailtpl.apply', ['user' =>$project->user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($project){
//                        $message->to($project->user->user_email)->subject('Good news! You\'ve received a bid for'.$project->prj_name.'!');
//                    });
                    return $this->jsonOk('ok','Offer successful');
                }else{
                    return $this->jsonErr('Offer error');
                }
            }

    }

    /**
     * 乙方上传
     * @param Request $request
     * @return mixed
     */
    public function uploadDaily(Request $request)
    {
        $prj_id = $request->get('id');
        $prj = Project::find($prj_id);
        if($this->_user->user_id !=$prj->prj_modeler){
            return $this->jsonErr("access forbiden");
        }
        $day = BuildDaily::where('bd_pid',$request->get('id'))->orderBy('bd_pubtime','desc')->first();
        if(empty($day)) {
            $dailys = new BuildDaily();
        }else {
            $dailys = $day;
        }
        $photos = $request->get('photos');
        $project_id = $request->get('id');
        $zip_id = $request->get('zip_id');
        $model_id = $request->get('model_id');
        $dailys->bd_pid = $project_id;
        $dailys->bd_uid = $this->_user->user_id;
        foreach ($photos as $photo) {
            $photo = (object)$photo;
            $oss = new Ossitem();
            $oss->oss_key = 'elements';
            $oss->oss_path = $photo->src;
            $oss->oss_item_uid = $this->_user->user_id;
            $oss->size = 0;
            $oss->save();
            $dailys->bd_photos .= $oss->oss_item_id . ',';
        }
        $dailys->bd_photos = rtrim($dailys->bd_photos, ',');
        $dailys->bd_attachment_trans = $model_id;
        $dailys->bd_pub = 1;
        $dailys->bd_attachment = $zip_id;
        $dailys->bd_pubtime = time();
        $dailys->bd_final = $request->get('isFinal');
        $dailys->save();
        $project = Project::find($project_id);
        if ($request->get('final') == 1) {
            $rate = new ProjectRate();
            $rate->r_time=0;
            $rate->r_quality = 0;
            $rate->r_other = 0;
            $rate->r_pid=$project_id;
            $rate->r_catetime=time();
            $rate->r_result=0;
            $rate->save();
            $project->prj_progress = 3;
            $project->save();
            $job = (new ProjectTiming($project_id))->delay(60*60*24*7);
            $this->dispatch($job);
        }
        return $this->jsonOk('ok','upload successful');
    }

    public function porosoal(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $has_pay = BuildPay::where('pid',$project->prj_id)->first();
        if($has_pay){
            
        }
    }

    private function proposalduetime($id){

    }

    public function myBidding(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $due = strtotime($project->created_at)+$project->prj_time_hour*3600+$project->prj_time_day*3600*24;



        //任务已经开始
        if($project->prj_progress>2){
            return $this->jsonErr("error");
        }


        $myBid = PrjApply::where('prj_id',$id)->where('user_id',$this->_user->user_id)->where('apply_status',1)->first();

        if($project->prj_progress==1){
            if($myBid){
                //等待中
                $state = 1;
            }else{
                if($due<time()){
                    //报价时间已结束
                    return $this->jsonErr("outdate");
                }else{
                    //可以报价
                    $state = 2;
                }

            }
            return $this->jsonOk("ok",['state'=>$state]);

        }elseif($project->prj_progress==2){
            if(!$myBid){
                return ;
            }
            if($project->prj_modeler==$myBid->user_id){

            }else{
                return $this->jsonOk("ok",['state'=>3]);
            }
        }

    }

    /**
     * @param $id
     */
    public function getPdf($id)
    {
        $project = Project::with(['user','modeler'])->find($id);
        $user_name = $project->user->user_type==4?$project->user->company_name:$project->user->user_name." ".$project->user->user_lastname;
        $modeler_name = $project->modeler->user_type==4?$project->modeler->company_name:$project->modeler->user_name." ".$project->modeler->user_lastname;
        $payment = BuildPay::where('pid',$id)->first();
        $payment_type = $payment->pay_method==2?'PayPal':'CriedCard';
        $dompdf = new Dompdf();
        $dompdf->loadHtml('
        <div style="font-size: 12px;border:1px solid whitesmoke;width: 500px;padding: 20px 20px;">
            <p>Company Name: '.$user_name.'</p>
            <p>User Name: '.$modeler_name.'</p>
            <p>Order No: '.$payment->receipt.'</p>
            <p>Payment method: '.$payment_type.'</p>
            <p>Payment time: '.date("Y-m-d H:i:s",$payment->pay_time).'</p>
            <p>Payment amount: '.$project->prj_price.'</p>
        </div>');
        $dompdf->render();
        $dompdf->stream('Payment.pdf');
    }

    public function testFormRequest(){

    }
    public function ckbid(){
        $id = Input::get('id',0);
        $prj = Project::find($id);
        if($prj && $prj->prj_progress>1){
            return $this->jsonErr("wrong operation",'');
        }
        if($prj->prj_uid==$this->_user->user_id){
            return $this->jsonErr("wrong operation",'');
        }
        $count = PrjApply::where('prj_id',$id)->where('user_id',$this->_user->user_id)->count();
        if($count>0){
            return $this->jsonErr("wrong operation");
        }
        return $this->jsonOk("ok","");

    }

    /**
     * 上传发消息
     * @param $id
     * @param $project
     */
    public function Notify($id,$project)
    {
            $notify_template = Notify::where('type',8)->first();
            $notify = new Notify();
            $redirectUrl = $_SERVER['REDIRECT_URL'];
            $notify->content = $notify_template->content.'<a href='.$redirectUrl.'/proposal-a/'.$project->prj_id.'>'.$project->prj_name.'</a>';
            $notify->type = 5;
            $notify->title = $id;
            $notify->save();
            $value  = null;
            $value = '(0,'.$id.',3,'.$notify->id.','.time().'),';
            $sql2 = 'insert into messages(msg_from_uid,msg_to_uid,msg_action,msg_rid,msg_time)value'.rtrim($value,',');
            DB::statement($sql2);
    }

    /**
     * 申请提现
     * @param Request $request
     * @return mixed
     */
    public function withdraw(Request $request)
    {
        $user_id = $this->_user->user_id;
        $amount = Wallet::where('uid',$user_id)->first();
        $this->validate($request,[
            'price'=>'required|numeric|between:1,'.$amount->USD
        ]);
        $cash = new ApplyCash();
        $cash->u_id = $user_id;
        $cash->amount = $request->get('price');
        $cash->paypal_email = $request->get('paypal_email');
        $cash->paypal_name = $request->get('paypal_name');
        $cash->status = 0;
        $cash->transaction_no = $this->payment($user_id);
        $cash->apply_time = time();
        if($cash->save()){
            $amount->USD = $amount->USD-$cash->amount;
            $amount->save();
            return $this->jsonOk('successful',['data'=>$cash]);
        }else{
            return $this->jsonErr('error');
        }


    }

    /**
     * 提现记录
     */
    public function withdrawHistory()
    {
        $page_size = Input::get('page_size');
        $offset = Input::get('offset');
        $offset = ($offset-1);
        $cashes = ApplyCash::where('u_id',$this->_user->user_id)
                    ->take($page_size)
                    ->skip($offset)
                    ->orderBy('id','DESC')
                    ->get();
        $count = ApplyCash::where('u_id',$this->_user->user_id)->count();
        if($page_size == $count)
        {
            $count = $count/$page_size;
        }else{
            $count = ceil($count/$page_size);
        }
        if ($cashes)
        {
            return $this->jsonOk('ok',['cashes'=>$cashes,'count'=>$count]);
        }else
        {
            return $this->jsonErr('error');
        }

    }

    /**
     * 生成交易号
     */
    private function payment($id)
    {
        $payment = time().$id;
        $chars = '0123456789';
        for ( $i = 0; $i <6; $i++ )
        {
            $payment.= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $payment;
    }

    public function markresponse(Request $req){
       $mid = $req->get('mid',0);
       $pic = $req->get('pic','');
       if(!$mid || !$pic){
           return $this->jsonErr("missing parameter");
       }
        $oss = new Ossitem();
        $oss->oss_key       =   'elements';
        $oss->oss_path      =   $pic;
        $oss->oss_item_uid  =   $this->_user->user_id;
        $oss->oss_item_eid  =   0;
        $oss->size          =   0;
        $oss->save();
        $response = MarkResponse::where('mid',$mid)->first();
        if(!$response)
        {
            $response = new MarkResponse();
        }
       $response->mid = $mid;
       $response->uid = $this->_user->user_id;
       $response->attachment=$oss->oss_item_id;
       $response->create_time = time();
       if($response->save()){
           $project = $response->mark->build->project;
           $mark = $response->mark;
           $content = 'Your modeler has completed the requested revisions on:'.$mark->title.'';
           $url = $_SERVER['REDIRECT_URL'].'/proposal-a/'.$project->prj_id;
           //\Event::fire(new BuildingEvent($project->prj_id,$project->user,$content,$url));
           $_mark = BuildMark::find($mid);
           $_mark->status_jia = 1;
           $_mark->status_yi = 1;
           $_mark->save();
           return $this->jsonOk("ok",['url'=>$this->getOssPath($oss->oss_item_id,'500')]);
       }

    }

}
