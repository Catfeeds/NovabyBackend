<?php

namespace App\Http\Controllers\Api\Projects;

use App\Events\BuildingEvent;
use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\CreateProject;
use App\Jobs\projectQueue;
use App\libs\ApiConf;
use App\libs\QiNiuManager;
use App\libs\StaticConf;
use App\libs\Tools;
use App\Model\BuildDaily;
use App\Model\BuildMark;
use App\Model\BuildPay;
use App\Model\Cate;
use App\Model\MarkResponse;
use App\Model\Message;
use App\Model\Ossitem;
use App\Model\PrjApply;
use App\Model\PrjInvite;
use App\Model\Project;
use App\Model\ProjectRate;
use App\Model\Rai;
use App\Model\Tag;
use App\Model\User;
use App\Model\Work;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Validator;
use Illuminate\Http\Request;
use DB;
use Storage;
use App\libs\OSSManager;

class ProjectListsApiController extends BaseApiController
{
    public function flush_test(){
        $str = str_pad("#",4096);
        for($i=0;$i<10;$i++){
            echo $i.$str."<br/>";

            ob_flush();
            flush();
            sleep(1);
            if($i>5){
                echo 123;
            }
        }
        exit;
    }
    /**
     * 甲乙方的任务列表
     * @return mixed
     */
    public function lists(){
        $role = Input::get('role',0);
        $act = Input::get('act',0);
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',10);
        $offset = ($page-1)*$page_size;
        if($role==0){
            //甲方
            $data = $this->prjLists($page_size,$offset,$act);
            $tot = Project::where('prj_uid',$this->_user->user_id)->count();
        }
        if($role==1){

            $tot = PrjApply::where('apply_status',1)->where('user_id',$this->_user->user_id)->count();

            $data = $this->applyLists($page_size,$offset,$act);
        }
        if(count($data)>0){
            $_c = ($page-1)*$page_size+count($data);
            if($_c<0){
                $hasMore=0;
            }else{
            $hasMore = intval($tot!=($page-1)*$page_size+count($data));
            }

            return $this->jsonOk('ok',['projects'=>$data,'pages'=>$tot,'has_more'=>$hasMore,'tot'=>$tot]);
        }else{
            return $this->jsonErr("No more data");
        }


    }

    /**
     * 甲方发布的任务列表
     * @param $page_size 分页
     * @param $offset
     * @param $act 完成状态 1 进行中 0 已完成
     * @return mixed 甲方项目列表
     */
    private function prjLists($page_size,$offset,$act){
        $where = '';
        if($act==0){
            $where = ' prj_progress >3 ';
        }elseif($act==1){
            $where = ' prj_progress <=3 ';
        }

        $projects = Project::select('prj_id','prj_name','created_at','prj_photos','prj_progress')
            ->whereRAW($where)
            ->where('prj_uid',$this->_user->user_id)
            ->with('apply')
            ->take($page_size)
            ->skip($offset)
            ->orderBy('prj_id','DESC')
            ->get();

        foreach($projects AS $k=>$v){
            $_covers = explode(',',$v->prj_photos);
            $projects[$k]->prj_cover = $this->getOssPath($_covers[0],'500');
            $t_carbon=new Carbon($v->created_at);
            $t_int=$t_carbon->timestamp;
            $projects[$k]->prj_created_at=$t_int;
            unset($projects[$k]->prj_photos);
            unset($projects[$k]->created_at);

            if($projects[$k]->prj_progress > 3)
            {
                $result = ProjectRate::where('r_pid',$projects[$k]->prj_id)->first()->r_result;
                if($result ==1) {
                    $projects[$k]->prj_progress = 5;  //fail
                }elseif($result==0){
                    $projects[$k]->prj_progress = 1;
                }else{
                    $projects[$k]->prj_progress = 4;  //pass
                }
            }
            if($v->apply->count()==0)
            {
                $projects[$k]->is_delete = 1; //无人报价,可以删除
            }else{
                $projects[$k]->is_delete = 0; //有人报价,不能删除
            }
            unset($v->apply);
        }
        return $projects;
    }

    /**
     * 删除项目
     * @param Request $request
     * @return mixed
     */
    public function delete(Request $request)
    {
        $pid = $request->get('pid');
        $project = Project::with('apply')->find($pid);
        if(!$project){
            return $this->jsonErr('project not found');
        }
        elseif($project->progress>1 || $project->apply->count()>0 || $project->prj_uid!=$this->_user->user_id){
            return $this->jsonErr('you can\'t delete this project');
        }
        else{
            Project::destroy($pid);
            Message::where(['msg_action'=>5,'msg_rid'=>$pid])->delete();

            return $this->jsonOk('ok','delete successful');
        }

    }
    /**
     * 乙方接收的项目列表
     * @param $page_size
     * @param $offset
     * @param $act 完成状态 1 进行中 0 已完成
     * @return array
     */
    private function applyLists($page_size,$offset,$act)
    {
        $where = ' 1 = 1';
        if($act==0) {
            $where = ' prj_status = 3 ';
        }
        elseif($act==1){
            $where = ' prj_status = 2';
        }
        $applies = PrjApply::whereRAW($where)
            ->where('apply_status',1)
            ->where('user_id',$this->_user->user_id)
            ->take($page_size)
            ->skip($offset)
            ->orderBy('id','DESC')
            ->get();
        $projects=[];
        foreach ($applies AS $k=>$v){
            $prj = $v->project;
            $_covers = explode(',',$prj->prj_photos);
            $t_carbon=new Carbon($prj->created_at);
            $t_int=$t_carbon->timestamp;
            if($applies[$k]->prj_status == 3)
            {
                $result = ProjectRate::where('r_pid',$applies[$k]->prj_id)->first()->r_result;
                if($result ==1) {
                    $prj->prj_progress = 5;
                }elseif($result ==0){
                    $prj->prj_progress = 1;
                }else{
                    $prj->prj_progress = 4;
                }
            }
            if($prj->prj_modeler  == null )
            {
                $prj->prj_progress = 1;
            }

            if($prj->prj_modeler  != null && $prj->prj_modeler!=$this->_user->user_id){
            }
            else{
                $projects[]=[
                    'prj_id'=>$prj->prj_id,
                    'prj_name'=>$prj->prj_name,

                    'prj_created_at'=>$t_int,
                    'prj_progress'=>$prj->prj_progress,
                    'prj_cover'=>$this->getOssPath($_covers[0],'500'),
                    'is_delete'=>0,
                ];
            }
        }
        return $projects;
    }

    /**
     * 分类字典表
     * @return mixed
     */
    public function cates(){
        $cates = Cate::where('cate_pid',1)->select('cate_id','cate_name')->get();
        return $this->jsonOk('ok',['cates'=>$cates]);
    }

    /**
     * 甲方发布需求的属性字典
     * @return mixed
     */
    public function pubattr(){
        $lang = $this->lang;
        switch ($lang){
            case 'zh':
                $industry = Cate::where('cate_pid',41)
                    ->select('cate_id','cate_name_cn')
                    ->orderBy('cate_order','ASC')
                    ->where('cate_active',0)
                    ->get()
                    ->map(function ($item){
                        $item->id = $item->cate_id;
                        $item->name = $item->cate_name_cn;
                        unset($item->cate_id,$item->cate_name_cn);
                        return $item;
                    });
                $category = Cate::where('cate_pid',1)
                    ->select('cate_id','cate_name_cn')
                    ->orderBy('cate_order','ASC')
                    ->where('cate_active',0)
                    ->where('cate_isgame','=',null)
                    ->get()
                    ->map(function ($item){
                        $item->id = $item->cate_id;
                        $item->name = $item->cate_name_cn;
                        unset($item->cate_id,$item->cate_name_cn);
                        return $item;
                    });
                sort(StaticConf::$project_visibility_zh);
                sort(StaticConf::$resolution_zh);
                sort(StaticConf::$budget_zh);
                sort(StaticConf::$expect_times_zh);
                $_data = [
                    'project_visibility'=>StaticConf::$project_visibility_zh,
                    'resolution'=>StaticConf::$resolution_zh,
                    'industry'=>$industry,
                    'category'=>$category,
                    'budgets'=>StaticConf::$budget_zh,
                    'duration'=>StaticConf::$expect_times_zh,
                    'gameid'=>43,
                ];
                break;
            default:
                $industry = Cate::where('cate_pid',41)
                    ->select('cate_id','cate_name')
                    ->orderBy('cate_order','ASC')
                    ->where('cate_active',0)
                    ->get()
                    ->map(function ($item){
                        $item->id = $item->cate_id;
                        $item->name = $item->cate_name;
                        unset($item->cate_id,$item->cate_name);
                        return $item;
                    });
                $category = Cate::where('cate_pid',1)
                    ->select('cate_id','cate_name')
                    ->orderBy('cate_order','ASC')
                    ->where('cate_active',0)
                    ->where('cate_isgame','=',null)
                    ->get()
                    ->map(function ($item){
                        $item->id = $item->cate_id;
                        $item->name = $item->cate_name;
                        unset($item->cate_id,$item->cate_name);
                        return $item;
                    });
                sort(StaticConf::$project_visibility);
                sort(StaticConf::$resolution);
                sort(StaticConf::$budget);
                sort(StaticConf::$expect_times);
                $_data = [
                    'project_visibility'=>StaticConf::$project_visibility,
                    'resolution'=>StaticConf::$resolution,
                    'industry'=>$industry,
                    'category'=>$category,
                    'budgets'=>StaticConf::$budget,
                    'duration'=>StaticConf::$expect_times,
                    'gameid'=>43,
                ];
                break;
        }
        //$category_game = Cate::where('cate_pid',1)->select('cate_id','cate_name')->orderBy('cate_order','ASC')->where('cate_active',0)->where('cate_isgame','=',1)->get();
        //$format = Cate::where('cate_pid',4)->select('cate_id','cate_name')->orderBy('cate_order','ASC')->where('cate_active',0)->get();
        //$tag = Tag::get();
        return $this->jsonOk('ok',['attr'=>$_data]);

    }
    /**
     * 甲方创建任务
     * @param  $req
     * @return mixed
     */
    public function create(Request $req){

        $rules = array(
            'title' => 'required|max:255',
            'industry' => 'required',
            'category' => 'required',
            'accuracy' => 'required',
            'budget'=>'required',
            'bidding_time'=>'required',
            'expect_time'=>'required',
            'permission'=>'required',
            //'nums' => 'required|numeric|min:1',
            'photos'=>'required',
        );
        $messages = [
            'same'    => 'The :attribute and :other must match.',
            'size'    => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in'      => 'The :attribute must be one of the following types: :values',
        ];


        $validator = Validator::make($req->all(),$rules, $messages);
        if ($validator->fails()) {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return $this->jsonErr($show_warning);
        }
        $industry = $req->get('industry',0);
        $engine = $req->get('engine',NULL);
        if($industry==0){
            return $this->jsonErr("industry can not be empty!");
        }
        if($industry==43){
            if(!$engine){
                return $this->jsonErr("engine must not be empty");
            }
        }
        $permission = $req->get('permission',0);

        $uids = $req->get('uids',[]);
        if($permission<1 || $permission>3 ){
            return $this->jsonErr("permission error");
        }
        if($permission==3){
            if(count($uids)<1){
                return $this->jsonErr("you must choose users");
            }
        }
        $photos = $req->get('photos',[]);
        if(count($photos)<1){
            return $this->jsonErr("photos must not be empty");
        }
        $tags = $req->get('tags',[]);
        $tags= implode(",",$tags);
        if(count($tags)<1){
           $tags = NULL;
        }
        $_rphotos = [];
        foreach($photos AS $k=>$v){
            $rec = new Ossitem();
            $rec->oss_key       =   'elements';
            $rec->oss_path      =   $v['src'];
            $rec->oss_item_uid  =   $this->_user->user_id;
            $rec->oss_item_eid  =   0;
            $rec->size          =   $v['size'];
            if($rec->save()){
                $_rphotos[]=$rec->oss_item_id;
            }
        }

        $rphotos = implode(",",$_rphotos);
        $project = new Project();
        $project->prj_uid       =   $this->_user->user_id;
        $project->prj_name      =   $req->get('title');
        $project->prj_industry  =   $req->get('industry');
        $project->prj_engine    =   $req->get('engine');
        $project->prj_category  =   $req->get('category');
        $project->prj_accuracy  =   $req->get('accuracy');

        $project->prj_photos    =   $rphotos;
        $project->prj_desc      =   $req->get('desc');
        $project->prj_time_day  =   $req->get('bidding_time');
        $project->prj_models_tot = 1;
        $project->prj_tags  =   $tags;
        $project->prj_budget  =   $req->get('budget');
        $project->prj_permission  =   $permission;
        $document  =   $req->get('doc_attachment',NULL);
        if($document!=null)
        {
            $did=[];
            foreach($document AS $k=>$v){
                $rec = new Ossitem();
                $rec->oss_key       =   'targets';
                $rec->oss_path      =   $v;
                $rec->oss_item_uid  =   $this->_user->user_id;
                $rec->oss_item_eid  =   0;
                $rec->size          =   0;
                if($rec->save()){
                    $did[]=$rec->oss_item_id;
                }
            }
            $project->prj_document = implode(",",$did);
        }
        $project->prj_expect  =   $req->get('expect_time',NULL);
        if($project->save()){
            if(!empty($uids)){
                foreach($uids AS $v){
                    $inv = new PrjInvite();
                    $inv->prj_id = $project->prj_id;
                    $inv->user_id=$v;
                    $user = User::find($v);
                    $url = env('CLIENT_BASE').'project-private/'.$project->prj_id;
                    Mail::later(10,'emailtpl.invite', ['user' =>$user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($user){
                        $message->to($user->user_email)->subject('Someone released a new project to invite you to apply!');
                    });
                    if($inv->save()){
                        if($v!=$this->_user->user_id){
                            $msg = new Message();
                            $msg->msg_from_uid=$this->_user->user_id;
                            $msg->msg_to_uid=$v;
                            $msg->msg_action=5;
                            $msg->msg_rid=$project->prj_id;
                            $msg->msg_time = time();
                            $msg->save();
                        }
                    }

                }
            }
            $url = env('CLIENT_BASE').'proposal-a/'.$project->prj_id;
//            Mail::send('emailtpl.project', ['user' =>$project->user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($project){
//                $message->to($project->user->user_email)->subject('You have released a new project!');
//            });

            return $this->jsonOk("ok",['prj_id'=>$project->prj_id]);
        }else{
            return $this->jsonErr("publish failed!");
        }

    }

    /**
     * 甲方proposal的状态
     * @return mixed
     */
    public function proposal(){
        $id = Input::get('id',0);
        $page = Input::get('page',1);
        $page_size =Input::get('pagesize',10);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $t_carbon=new Carbon($project->created_at);
        $t_int=$t_carbon->timestamp;
        $_end = $t_int+3600*24*$project->prj_time_day;
        $_lefttime = $_end-time();
        if($_lefttime<=0){
            $_lefttime=0;
        }
        $_data = [
            'lefttime'=>$_lefttime,
            'proposalLists'=>$this->proposalLists($id,$page,$page_size)
        ];
        return $this->jsonOk("ok",['proposal'=>$_data]);


    }

    /**
     * 单独的甲方查看报价列表
     * @return mixed
     */
    public function proposal_lists(){
        $id = Input::get('id',0);
        $page = Input::get('page',1);
        $page_size =Input::get('pagesize',10);

        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        return $this->jsonOk("ok",['list'=>$this->proposalLists($id,$page,$page_size)]);
    }

    /**
     * 报价列表的具体实现方法
     * @param $id
     * @param $page
     * @param $page_size
     * @return array
     */
    private function proposalLists($id,$page,$page_size){

        $offset = ($page-1)*$page_size;
        $tot = PrjApply::where('prj_id',$id)->where('apply_status',1)->count();
        $user = PrjApply::where('prj_id',$id)->with('user','project')->get();
        $ai = new Rai();
        $applies = $user->map(function ($items){
            return $items;
        })->sortByDesc(function ($item)use($ai){
            $item->rai = $ai->getRai($item->project->prj_category,$item->user_id);
            return $item->rai;
        })->values();
        if(count($applies)>0) {
            $arg = 90-$applies[0]->rai;
        }else {
            $arg= 0;
        }
        $applies = $applies->forPage($page,$page_size);
        if($tot){
            foreach($applies AS $k=>$v){
                $applies[$k]->tot_price=$v->project->prj_models_tot*$v->apply_price;
                $applies[$k]->rai=$v->rai+$arg;
                if(isset($applies[0])) {
                    $applies[0]->rai = 90;
                }
                $applies[$k]->userInfo=$this->modelerWork($v->user_id);
                unset($applies[$k]->user_icon);
                unset($applies[$k]->prj_models_tot);
                unset($applies[$k]->user);
                unset($applies[$k]->project);
            }
            return ['tot'=>$tot,'pages'=>$tot/$page_size,'lists'=>$applies];
        }else{
            return ['tot'=>$tot,'lists'=>[]];
        }

    }

    /**
     * 报价列表中需要的乙方用户的信息
     * @param $uid
     * @return mixed
     */
    private function modelerWork($uid){
        $userInfo = User::select('user_icon','user_name','user_type','user_lastname')
            ->where('user_id',$uid)
            ->first();
        $works = Work::where(['work_uid'=>$uid,'work_privacy'=>0,'work_status'=>1])->count();
        $userInfo->user_works = $works;
        $userInfo->username = $userInfo->user_name.' '.$userInfo->user_lastname;
        $cover = Work::where('work_uid',$uid)
            ->select('work_cover')
            ->where('work_status',1)
            ->orderBy('work_id','DESC')
            ->limit(1)
            ->first();

        $userInfo->avatar = $this->getAvatar($userInfo->user_icon,'100');
        $userInfo->work_cover = $cover?$this->getOssPath($cover->work_cover,'1000'):'';
        $userInfo->work_covers = $this->userWorks($uid);

        unset($userInfo->user_icon);
        unset($userInfo->user_name);
        unset($userInfo->user_lastname);

        return $userInfo;

    }

    /**
     * 甲方选择一个乙方
     * @param Request $req
     * @return mixed
     */
    public function chooseModeler(Request $req){
        $uid = $req->get('uid',0);
        $id =$req->get('id',0);
        if($uid==0){
            return $this->jsonErr("you must select a user");
        }
        if($id==0){
            return $this->jsonErr("ID must not be empty");
        }
        $prj = Project::find($id);
        $prj->prj_modeler= $uid;
        $prj->prj_progress=2;
        $prj_apply = PrjApply::where('prj_id',$id)->where('user_id',$uid)->select('apply_price')->first();
        $prj->prj_price = $prj_apply->apply_price;
        if($prj->save()){

            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("failed");
        }

    }

    /**
     * 检查甲方是否上传过原画资源包
     * @return mixed
     */
    public function checkAttachment(){
        $id = Input::get('id',0);
        $prj = Project::find($id);
        if(!$prj->prj_attachment){
            return $this->jsonOk("no attachment",[]);
        }else{
            $oss = Ossitem::find($prj->prj_attachment);
            $_name =explode("/",$oss->oss_path);

            $attach = [
                'name'=>$_name[count($_name)-1],
                'id'=>$oss->oss_item_id,
                'size'=>Tools::sizeConvert($oss->size),
            ];
            return $this->jsonOk("has attachment",['attachment'=>$attach]);
        }
    }

    /**
     * 甲方上传原画资源包
     * @param Request $req
     * @return mixed
     */
    public function uploadAttachment(Request $req){
        $id = $req->get('id',0);
        $prj = Project::find($id);
        if(!$prj || $prj->prj_progress!=1){
            return $this->jsonErr("not found");
        }
        $attach = $req->get('attachment','');
        if(!$attach){
            return $this->jsonErr("no attachment found");
        }
        $formatstr = "doc docx xls pdf pptx ppt xlsx zip 7z rar jpg jpeg png";
        $formatarr = explode(" ",$formatstr);
        $_att_id=[];
       if(count($attach)<=2){
           $pos = strrpos($attach['src'],'.');
           $ext = substr($attach['src'],$pos+1);
           if(!in_array($ext,$formatarr)){
               return $this->jsonErr("invalid attachment format");
           }
           $oss = new Ossitem();
           $oss->oss_key       =   'targets';
           $oss->oss_path      =   $attach['src'];
           $oss->oss_item_uid  =   $this->_user->user_id;
           $oss->oss_item_eid  =   0;
           $oss->size          =   $attach['size'];
           if($oss->save()) {
               $_att_id = $oss->oss_item_id;
           }
           $prj->prj_attachment=$_att_id;
       }else{
           foreach($attach AS $v){

               $pos = strrpos($v['src'],'.');
               $ext = substr($v['src'],$pos+1);
               if(!in_array($ext,$formatarr)){
                   return $this->jsonErr("invalid attachment format");
               }
               $oss = new Ossitem();
               $oss->oss_key       =   'targets';
               $oss->oss_path      =   $v['src'];
               $oss->oss_item_uid  =   $this->_user->user_id;
               $oss->oss_item_eid  =   0;
               $oss->size          =   $v['size'];
               if($oss->save()) {
                   $_att_id[] = $oss->oss_item_id;
               }
           }
           if(count($_att_id)==0){
               return $this->jsonErr("upload error!");
           }
           $prj->prj_attachment=implode(",",$_att_id);
       }
        if($prj->save()){
            return $this->jsonOk("upload successfully!",[]);
        }else{
            return $this->jsonErr("upload error!");
        }
    }





    /**
     * 项目进度实际数据
     * @return mixed
     */
    public function buildprocess1(){
        $id = Input::get('id',0);
        $pay = DB::table('build_pays')->select('pay_time')->where(['has_pay'=>1,'pid'=>$id])->first();
        if(!$pay){
           return $this->jsonErr("no payment");
        }
        $start = $pay->pay_time;
        $modeler = Project::where('prj_id',$id)->select('prj_modeler')->first();
        if(!$modeler || !$modeler->prj_modeler){
            return $this->jsonErr("not found");
        }
        $apply = PrjApply::where('prj_id',$id)->where('user_id',$modeler->prj_modeler)->where('apply_status',3)->first();
        if(!$apply){
            return $this->jsonErr("not found");
        }
        $rate = ProjectRate::where('r_pid',$id)->first();
        if($rate){
            $end = $rate->r_catetime;
        }else{
            $end   = $start+$apply->apply_cost_time*3600*24;
        }
        $start_time     = mktime(0,0,0,date('m',$start),date('d',$start),date('Y',$start));
        $end_time       = mktime(0,0,0,date('m',$end),date('d',$end),date('Y',$end));
        $current_time   = mktime(0,0,0,date('m'),date('d'),date('Y'));
        if($current_time>=$end_time){
            $current_time = $end_time;
        }
        $this_day_start = mktime(0,0,0,date('m',time()),date('d',time()),date('Y',time()));
        $builds = BuildDaily::select('bd_pubtime')
            ->where('bd_pid',$id)
            ->where('bd_uid',$modeler->prj_modeler)
            ->where('bd_pubtime','<',$this_day_start)
            ->get();
        $submits_time  =[];
        foreach($builds AS $k=>$v){
            $submits_time[]=mktime(0,0,0,date('m',$v->bd_pubtime),date('d',$v->bd_pubtime),date('Y',$v->bd_pubtime));
        }
        $ret = [
            'start_time'=>$start_time,                      //开始时间
            'current_time'=>$current_time,                  //当前时间
            'end_time'=>$end_time,                          //结束时间
            'submits_time'=>$submits_time,                  //提交时间
            'left_time'=>$end-time()>=0?$end-time():0,      //剩余时间

        ];
        return $this->jsonOk("ok",['data'=>$ret]);


    }

    /**
     * 甲乙方查看concepts
     * @return mixed
     */
    public function viewconcepts(){
        $id = Input::get('id',0);

        $prj = Project::find($id);
        if(!$prj){
            return $this->jsonErr("not found");
        }
        if(!$prj->prj_attachment){
            return $this->jsonOk("no attachment",[]);
        }else{
            $oss = Ossitem::find($prj->prj_attachment);
            $_name =explode("/",$oss->oss_path);

            $attach = [
                'name'=>$_name[count($_name)-1],
                'src'=>$this->getOssPath($oss->oss_item_id),
                'size'=>Tools::sizeConvert($oss->size),
                'time'=>time()
            ];
            return $this->jsonOk("ok",['attachment'=>$attach]);
        }


    }

    public function finalattach(){
        $id = Input::get('id',0);

        $attach = BuildDaily::where('bd_pid',$id)->where('bd_final',1)->first();
        if(!$attach){
            return $this->jsonErr("not found");
        }
        if(!$attach->bd_attachment){
            return $this->jsonOk("no attachment",[]);
        }else{
            $oss = Ossitem::find($attach->bd_attachment);
            $_name =explode("/",$oss->oss_path);

            $attach = [
                'name'=>$_name[count($_name)-1],
                'src'=>$this->getOssPath($oss->oss_item_id),
                'size'=>Tools::sizeConvert($oss->size),
                'time'=>time()
            ];
            return $this->jsonOk("ok",['attachment'=>$attach]);
        }


    }

    /**
     * 合同信息
     * @return string
     */
    public function viewcontract(){
        $token = Input::get('token','');
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $apply = PrjApply::where(['prj_id'=>$id,'user_id'=>$project->prj_modeler])->first();
        $prj_time = $project->prj_time_day.' Days '.$project->prj_time_hour .'Hours';
        $_hours = $project->prj_time_day*24+$project->prj_time_hour;
        $price_per_hour = $apply->apply_price.' USD';
        $sub_total = $project->prj_models_tot*$apply->apply_price.' USD';
        if($project->prj_progress<=1){
            if($project->prj_uid==$this->_user->user_id){
                $bill_info =[
                    'prj_time'=>$prj_time,
                    'price_per_hour'=>$price_per_hour,
                    'sub_total'=>$sub_total,
                    'contract'=>$_SERVER['APP_URL'].'/api/task/getPdf/'.$id.'?token='.$token,
                    'days'=>$project->prj_time_day.' Days'
                ];
                return $this->jsonOk("ok",['result'=>0,'bill'=>$bill_info]);

            }
            return $this->jsonErr("not found");
        }
        $_ckpay = BuildPay::where('pid',$project->prj_id)
            ->where('has_pay',1)
            ->select('pay_method','pay_time','receipt')
            ->first();
        if(!$_ckpay){
            return '';
        }

        $pay_method = $_ckpay->pay_method==2?'Paypal':'CreditCard';
        $pay_time = $_ckpay->pay_time;
        $order_no = $_ckpay->receipt;
        $_contract = [
            'prj_time'=>$prj_time,
            'price_per_hour'=>$price_per_hour,
            'sub_total'=>$sub_total,
            'pay_method'=>$pay_method,
            'pay_time'=>$pay_time,
            'order_no'=>$order_no,
            'contract'=>$_SERVER['APP_URL'].'/api/task/getPdf/'.$id.'?token='.$token,
            'days'=>$project->prj_time_day.' Days'

        ];
        return $this->jsonOk('ok',['bill'=>$_contract,'result'=>1]);

    }

    /**
     * 进入标注获取详情
     * @return mixed
     */
    public function modelformark1(){
        $id = Input::get('id',0);
        $type = Input::get('type',1);
        if($type){      //标注3D模型
            $_builddaly = BuildDaily::where('bd_id',$id)->orderBy('bd_id','DESC')->first();
            if($_builddaly->bd_attachment_trans){
                $model = $this->getModelFilesById($_builddaly->bd_attachment_trans);
                return $this->jsonOk("ok",['model'=>$model]);
            }
        }else{          //标注2D图
            $_builddaly = BuildDaily::where('bd_id',$id)->with('images')->orderBy('bd_id','DESC')->first();
            $model = $this->getPhotos($_builddaly->images);
            return $this->jsonOk('ok',['model'=>$model]);
        }
    }
    public function modelformark(){
        $id = Input::get('id',0);
        $works=[

            "model_format"=>"obj",
            "model_url"=>[
                "dir"=> "https://element2.oss-cn-shanghai.aliyuncs.com/20171001094520/",
                "file"=> "IllidanLegion_test.obj"
            ],
            "model_mets"=> [
                "http://elements.oss-cn-hongkong.aliyuncs.com/20170728164635/Interior.png",
                "http://elements.oss-cn-hongkong.aliyuncs.com/20170728164635/glass.png",
                "http://elements.oss-cn-hongkong.aliyuncs.com/20170728164635/Main Body 2.png",
                "http://elements.oss-cn-hongkong.aliyuncs.com/20170728164635/Main Body.png"
            ],
            "mtl"=>[
                'file' => 'IllidanLegion_test.mtl',
                'dir' => 'https://element2.oss-cn-shanghai.aliyuncs.com/20171001094520/'
            ],
        ];
        return $this->jsonOk("ok",['model'=>$works]);

    }

    /**
     * 乙方每天上传的数据
     * @return mixed
     */
    public function dayworks(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project || !$project->prj_modeler){
            return $this->jsonErr("not found");
        }
        //$apply_info =PrjApply::where('prj_id',$id)->where('user_id',$project->prj_prj_modeler)->first();
        $pay = DB::table('build_pays')->select('pay_time')->where(['has_pay'=>1,'pid'=>$id])->first();
        if(!$pay){
            return $this->jsonErr("no payment");
        }
        $start = $pay->pay_time;
        $modeler = Project::where('prj_id',$id)->select('prj_modeler')->first();
        if(!$modeler || !$modeler->prj_modeler){
            return $this->jsonErr("not found1");
        }
        $apply = PrjApply::where('prj_id',$id)->where('user_id',$modeler->prj_modeler)->where('apply_status',1)->first();
        if(!$apply){
            return $this->jsonErr("not found2");
        }
        //$start_time     = mktime(0,0,0,date('m',$start),date('d',$start),date('Y',$start));
        $end            = $start+$apply->apply_cost_time*3600*24;
        $end_time       = mktime(0,0,0,date('m',$end),date('d',$end),date('Y',$end));
        $last_day = (date('ymd',$end_time)==date('ymd'))?1:0;

        $role = $this->judgeCaller($id);
        if($role==1){
            //甲方
            $build = BuildDaily::where('bd_pid',$id)
                ->where('bd_attachment_trans','>',0)
                ->where('bd_pub',2)
                ->orderby('bd_id','desc')
                ->first();
            if(!$build){
                return $this->jsonErr('no update');
            }else{
                $_ps = explode(",",$build->bd_photos);
                $works = [];
                foreach($_ps AS $k1=>$v1){
                    $ps[]=$this->getOssPath($v1,'1000');
                }
                $works['photos']=$ps;
                $works['model']=$this->getModelFilesById($build->bd_attachment_trans);
                return $this->jsonOk("ok",['works'=>$works]);
            }
        }elseif($role==2){
            BuildDaily::where(['bd_pub'=>1,'bd_pid'=>$id])->delete();
            $today = (Carbon::today())->timestamp;
            $build = BuildDaily::where('bd_pid',$id)
                ->where('bd_pubtime','>=',$today)
                ->orderby('bd_id','desc')
                ->first();
            $works = [];
            if(!$build){
                $ret = ['status'=>0];
                $status = 0;

                return $this->jsonOk("ok",['status'=>$status,'works'=>$works,'is_final_day'=>$last_day]);
            }
            $_ps = explode(",",$build->bd_photos);
            foreach($_ps AS $k1=>$v1){
                $ps[]=$this->getOssPath($v1,'1000');
            }
            $works['photos']=$ps;
//            if($build->bd_pub==0){
//                $status = 1;
//                $works['pubtime']=$build->bd_pubtime;
//                $works['zip']=$this->zipinfo($build->bd_attachment);
//            }elseif($build->bd_pub==1){
                $status =2;
                $works['pubtime']=$build->bd_pubtime;
                $works['zip']=$this->zipinfo($build->bd_attachment);
                $works['model']= $this->getModelFilesById($build->bd_attachment_trans);
//            }elseif($build->bd_pub==2){
//                unset($works['photos']);
//                $status =0;
//            }

            return $this->jsonOk("ok",['status'=>$status,'works'=>$works,'is_final_day'=>$last_day]);
        }
    }

    public function convertconfirm(Request $req){
        $pid = $req->get('pid',0);
        if($pid==0){
            return ;
        }
        $lastsubmit = BuildDaily::where('bd_pid',$pid)->orderBy('bd_id','DESC')->first();
        if($lastsubmit && $lastsubmit->bd_pub!=2){
            $lastsubmit->bd_pub=2;
            if($lastsubmit->save()){
                $project = Project::find($pid);
                $url = $_SERVER['REDIRECT_URL'].'/proposal-a/'.$project->prj_id;
                if($lastsubmit->bd_final==1){
                    $content ='The model teacher uploaded the final document ';
                }else{
                    $content = 'The model artist submitted a new update';
                }
//                \Event::fire(new BuildingEvent($project->prj_id,$project->user,$content,$url));
                return $this->jsonOk("ok",['msg'=>'successfully']);
            }
        }

    }


    /**
     * 附件信息
     * @param $id
     * @return array|string
     */
    private function attachInfo($id){
        $item = Ossitem::find($id);
        if(!$item){
            return '';
        }
        $_names = explode("/",$item->oss_path);
        return $ret=[
            'name'=>$_names[count($_names)-1],
            'src'=>ApiConf::TARGET_URI.$item->oss_path,
            'size'=>Tools::sizeConvert($item->size),

        ];
    }

    /**
     * 甲方审核乙方
     * $result：2是通过 1是不通过
     * @param Request $req
     * @return mixed
     */
    public function submission(Request $req){
        $id = $req->get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("error");
        }
        $result = $req->get('action',0);
        if($result==0){
            return $this->jsonErr("missing parameters");
        }
        $ckrate =  ProjectRate::where('r_pid',$id)->count();
        if($ckrate>1){
            return $this->jsonErr("you have submited");
        }
        $rate_time = $req->get('rate_time',0);
        $rate_quality = $req->get('rate_quality',0);
        $rate_commucation = $req->get('rate_commucation',0);
        $comments = $req->get('comment',NULL);
        $rate = ProjectRate::where('r_pid',$id)->first();
        $rate->r_time=$rate_time;
        $rate->r_quality = $rate_quality;
        $rate->r_other = $rate_commucation;
        $rate->r_pid=$id;
        $rate->r_catetime=time();
        $rate->r_result=$result;
        \Event::fire(new NotifyEvent(9,$project->prj_modeler));
        $rate->r_comment=$comments;
        if($result==2)
        {
            $project->prj_success=1;
        }else{
            $project->prj_success=0;
        }
        $project->prj_progress=3.5;
        $project->updated_at =date('Y-m-d H:i:s',time());
        $apply = PrjApply::where('user_id',$project->prj_modeler)->where('prj_id',$id)->first();
        $apply->prj_status = 3;
        if($project->save() && $rate->save() && $apply->save()){
            $content = 'The customer reviewed your work ';
            $url = $_SERVER['REDIRECT_URL'].'/proposal-b/'.$project->prj_id;
           // \Event::fire(new BuildingEvent($project->prj_id,$project->modeler,$content,$url));;
           return $this->jsonOk("submission successfully",[]);
        }else{
            return $this->jsonErr("submission failed");
        }



    }

    /**
     * 审核结果
     * @return mixed
     */
    public function submissionResult(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        if($project->prj_progress>3){
            $rate = ProjectRate::where('pid',$project->prj_id)->first();
            if(!$rate){
                return $this->jsonErr("server error");
            }
            $ret = [
                'prj_id'=>$project->prj_id,
                'prj_progress'=>$project->prj_progress,
                'submission'=>[
                    'rate_time'=>$rate->r_time,
                    'rate_quality'=>$rate->r_quality,
                    'rate_commucation'=>$rate->r_other,
                ],

            ];
            if($project->prj_progress==4){
                $ret['comment']=$rate->r_comment;
            }
        }else{
            return $this->jsonErr("error");
        }
    }

    /**
     * 用户发布的最新10个作品
     * @param $id
     * @return array|string
     */
    public function userWorks($id)
    {
        $_cover= Work::where('work_uid',$id)
            ->where('work_privacy',0)
            ->where('work_status',1)
            ->orderBy('work_id','DESC')
            ->limit(10)
            ->pluck('work_cover')
            ->all();
        if($_cover) {
            $works = $this->getOssPath($_cover,'1000');
        }else{
            $works=null;
        }
        if($works===null) $works="";
        return $works;
    }

    /**
     * 返回图片路径
     * @param $arrs
     * @return array|string
     */
    private function path($arrs,$size='')
    {
        $arr = array();
        if($arrs)
        {
            for($i=0;$i<count($arrs);$i++)
            {
                $arr[] = $this->getOssPath($arrs[$i],$size);
            }
            return $arr;
        }
        else
        {
            return $this->getOssPath($arrs,$size);
        }
    }

    /**
     * 项目进度查询
     * @return mixed
     */
    public function progress(){
        $id= Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $progress = $project->prj_progress;
        if($this->_user->user_id != $project->prj_uid && $this->_user->user_id != $project->prj_modeler)
        {
            return $this->jsonErr('You don\'t have permission to access');
        }elseif ($this->_user->user_id == $project->prj_uid){
            $act = 1;   //甲方
        }else{
            $act = 2;   //乙方
        }
        if($project->prj_progress>=3.5){
            $ck = ProjectRate::where('r_pid',$project->prj_id)->first();
            if($ck->r_result>0){
                $progress=4;
            }

        }
        return $this->jsonOk("ok",['progress'=>$progress,'act'=>$act]);
    }

    /**
     * 项目选中的乙方信息
     * @return mixed
     */
    public function proposalResult(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        if($project->prj_progress<3){
            return $this->jsonErr("error");
        }
        $applies = PrjApply::where('prj_apply.prj_id',$id)
            ->leftJoin('projects','prj_apply.prj_id','=','projects.prj_id')
            ->select('user_id','apply_cost_time','apply_price','prj_models_tot')
            ->where('prj_apply.user_id',$project->prj_modeler)
            ->get();
        foreach($applies AS $k=>$v){
            $applies[$k]->tot_price=$v->apply_price*$v->prj_models_tot;
            $applies[$k]->rai=90;
            $applies[$k]->userInfo=$this->modelerWork($v->user_id);
            unset($applies[$k]->user_icon);
            unset($applies[$k]->prj_models_tot);
        }
        return $this->jsonOk("ok",['list'=>['pages'=>1,'tot'=>1,'lists'=>$applies]]);
    }

    /**
     * 检查是否审核
     * @return mixed
     */
    public function checkSubmission(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $rate = ProjectRate::where('r_pid',$id)->first();
        if(!$rate){
            return $this->jsonOk("200",['result'=>0]);
        }
        $submission = [
            'rate_time'=>$rate->r_time,
            'rate_quality'=>$rate->r_quality,
            'rate_commucation'=>$rate->r_other,
            'rate_comment'=>$rate->r_comment,
        ];
        $res = $rate->r_result;
        if($res == 0)
        {
            $time = $rate->r_catetime+3600*24*7-time();
            return $this->jsonOk("ok",[
                'result'=>$res,
                'time'=>$time
                ]);
        }else{
            return $this->jsonOk("ok",['result'=>$res,'submission'=>$submission]);
        }

    }



    /**
     * 项目回顾
     * @return mixed
     */
    public function review(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }

        $review =[];
        $review['title']            =   $project->prj_name;
        if($project->prj_prgress<=1){
            $review['viewRequirement']  =   $this->requirementStatus($project);
        }
        if($project->prj_prgress<=2){
            $review['proposal']         =   $this->proposalStatus($project);
        }
        if($project->prj_prgress<=3){
            $review['uploadConcepts']   =   $this->conceptsStatus($project);
        }
        if($project->prj_prgress<=4){
            $review['signContract']     =   $this->contractStatus($project);
            $rate = ProjectRate::where('r_pid',$project->prj_id)->first();
            if($rate){
                $review['finalSubmission']  =   $this->submissionStatus($project);
            }else{
                $review['finalSubmission']  = [];
            }
        }
        if($project->prj_prgress>4){

        }

        return $this->jsonOk("ok",['review'=>$review]);
    }

    private function requirementStatus($project){
        $photos = [];
        $_photos = explode(',',$project->prj_photos);

        foreach($_photos AS $k=>$v){
            $photos[]=$this->getOssPath($v,'300');
        }

        $engine = array(1=>'Unity',2=>'UE4');
        $select = array(1=>'Yes',0=>'No');
        $accuracy = array(1=>'High',0=>'Low');
        $data =[
            'prj_name'=>$project->prj_name,
            'prj_photos'=>$photos,
            'prj_industry'=>$project->industry->cate_name,
            'prj_category'=>$project->cate->cate_name,
            'prj_format'=>$project->format->cate_name,
            'prj_accuracy'=>$accuracy[$project->prj_accuracy],
            'prj_texture'=>$select[$project->prj_texture],
            'prj_rigged'=>$select[$project->prj_rigged],
            'prj_engine'=>$project->prj_engine?$engine[$project->prj_engine]:'',
            'prj_desc'=>$project->prj_desc?$project->prj_desc:'',
            'prj_models_tot'=>$project->prj_models_tot,
            'proposal'=>[
                'timezone'=>'(GMT +8) HONGKONG',
                'time'=>$project->prj_time_day.' Day '.$project->prj_time_hour.' Hours'
            ]
        ];


        return $data;
    }
    private function conceptsStatus($project){
        if(!$project->prj_attachment){
            return '';
        }else{
            $oss = Ossitem::find($project->prj_attachment);
            $_name =explode("/",$oss->oss_path);

            $attach = [
                'name'=>$_name[count($_name)-1],
                'src'=>ApiConf::TARGET_URI.$oss->oss_path,
                'size'=>Tools::sizeConvert($oss->size),
            ];
            return $attach;
        }
    }
    private function proposalStatus($project){
        if($project->prj_progress<3){
            return '';
        }

        $applies = PrjApply::where('prj_apply.prj_id',$project->prj_id)
            ->leftJoin('projects','prj_apply.prj_id','=','projects.prj_id')
            ->select('user_id','apply_cost_time','apply_price','prj_models_tot')
            ->where('prj_apply.user_id',$project->prj_modeler)
            ->get();

        foreach($applies AS $k=>$v){
            $applies[$k]->tot_price=$v->prj_models_tot*$v->apply_price;
            $applies[$k]->rai=90;
            $applies[$k]->userInfo=$this->modelerWork($v->user_id);

            unset($applies[$k]->user_icon);
            unset($applies[$k]->prj_models_tot);
        }
        return $applies;
    }
    private function submissionStatus($project){
        if($project->prj_progress==4){
            $rate = ProjectRate::where('r_pid',$project->prj_id)->first();
            if(!$rate){
                return $this->jsonErr("server error");
            }
            $ret = [
                'prj_id'=>$project->prj_id,
                'prj_progress'=>$project->prj_progress,
                'submission'=>[
                    'rate_time'=>$rate->r_time,
                    'rate_quality'=>$rate->r_quality,
                    'rate_commucation'=>$rate->r_other,
                ],

            ];
            if($rate->r_result==2){
                $ret['comment']=$rate->r_comment;
            }

            return $ret;


        }
        return '';

    }
    private function contractStatus($project){

        $prj_time = $project->prj_time_day.' Days '.$project->prj_time_hour .' Hours';
        $_hours = $project->prj_time_day*24+$project->prj_time_hour;
        $_sub_total = $project->prj_price;
        $price_per_hour = ceil($_sub_total/$_hours).' USD';
        $_ckpay = BuildPay::where('pid',$project->prj_id)
            ->where('has_pay',1)
            ->select('pay_method','pay_time','receipt')
            ->first();
        if(!$_ckpay){
            return '';
        }
        $sub_total = $_sub_total.' USD';
        $pay_method = $_ckpay->pay_method==2?'Paypal':'CreditCard';
        $pay_time = $_ckpay->pay_time;
        $order_no = $_ckpay->receipt;
        $contract = [
            'prj_time'=>$prj_time,
            'price_per_hour'=>$price_per_hour,
            'sub_total'=>$sub_total,
            'pay_method'=>$pay_method,
            'pay_time'=>$pay_time,
            'order_no'=>$order_no

        ];
        return $contract;
    }

    /**
     * 检查是否支付过
     * @return mixed
     */
    public function checkPayResult(){
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        $check = BuildPay::where('pid',$id)->where('has_pay',1)->count();
        return $this->jsonOk("ok",['pay_result'=>$check]);
    }
    public function testpdf(){

        $p = new Dompdf();
        $p->loadHtml("hello");
        $p->render();
        $p->stream();
    }

    public function prevContract(){
        $uid = Input::get('uid',0);
        $pid = Input::get('pid',0);
        $apply = PrjApply::where('prj_id',$pid)->where('user_id',$uid)->first();
        if(!$apply){
            return $this->jsonErr("not found");
        }
        $tot_num = Project::select('prj_models_tot','prj_time_day')->find($pid);
        $_data=[
            'delivery_time'=>$tot_num->prj_time_day.' Days',
            'project_time'=>$apply->apply_cost_time.' Days',
            'price_per'=>$apply->apply_price.' USD/Model',
            'subtotal'=>$apply->apply_price*$tot_num->prj_models_tot.' USD',

        ];
        return $this->jsonOk("ok",['contract'=>$_data]);
    }

    public function markperm(){

        return $this->jsonOk("ok",['times'=>10]);

    }
    public function buildmark(Request $req){
        $marks = $req->get('mark',[]);
        $pid = $req->get('pid',0);
        if(count($marks)<1 || $pid ==0){
            return $this->jsonErr("missing parameters");
        }
        $buildDaily = BuildDaily::find($pid);
        foreach($marks AS $k=>$v){
            $save_path=$this->_user->user_id.time().Tools::guid().'.png';
            $pics = explode("base64,",$v['image']);
            $con = base64_decode($pics[1]);
            if($con){
                Storage::disk('tmp')->put($save_path, $con);
                //$ossmgr = new OSSManager();
                $oss_base_path = date('YmdHis/');
                $target_id    = Tools::guid();
                $oss_file   = $target_id.'.png';
                $oss_zip_path =$oss_base_path.$oss_file;
                $zip_path = Storage::disk('tmp')->getAdapter()->getPathPrefix().$save_path;
                $qiNiu = new QiNiuManager(0);
                $upres = $qiNiu->upload($oss_zip_path,$zip_path);
                //$upres = $ossmgr->upload($oss_zip_path,'element2',$zip_path);
                if(!$upres['error']){
                    unset($zip_path);
                    $_tid = DB::table('oss_item')->insertGetId([
                        'oss_key'=>$qiNiu->key,
                        'oss_path'=>$oss_zip_path,
                        'oss_item_uid'=>$this->_user->user_id,
                        'size'=>0
                    ]);
                    if(!$_tid){
                        return 'upload to oss error!';
                    }
                    $mark = new BuildMark();
                    $oss = new Ossitem();
                    $oss->oss_key       =   'elements';
                    $oss->oss_path      =   $oss_zip_path;
                    $oss->oss_item_uid  =   $this->_user->user_id;
                    $oss->oss_item_eid  =   0;
                    $oss->size          =   0;
                    if(!$oss->save()){
                        return 'save pic error';
                    }
                    $_att_id = 0;
                    if($v['attachment']){
                        $oss2 = new Ossitem();
                        $oss2->oss_key       =   'elements';
                        $oss2->oss_path      =   $v['attachment'];
                        $oss2->oss_item_uid  =   $this->_user->user_id;
                        $oss2->oss_item_eid  =   0;
                        $oss2->size          =   0;
                        if(!$oss2->save()){
                            return 'save attachment error';
                        }
                        $_att_id = $oss2->oss_item_id;
                    }
                    $img_id = $oss->oss_item_id;
                    $mark->uid = $this->_user->user_id;
                    $mark->bid = $pid;
                    $mark->pid = $buildDaily->bd_pid;
                    $mark->title = $v['title'];
                    $mark->description = $v['desc'];
                    $mark->img = $img_id;
                    $mark->attachment = $_att_id;
                    $mark->status = 0;
                    $mark->create_time = time();
                    if(!$mark->save()){
                        return 'save mark error';
                    }
            }
            }
        }
        $build = BuildDaily::with('project')->find($pid);
        $build->bd_final = 0;
        $build->save();
        $project = $build->project;
        $content = 'Your customer has made some comments ';
        $url = $_SERVER['REDIRECT_URL'].'/proposal-b/'.$project->prj_id;
        //\Event::fire(new BuildingEvent($project->prj_id,$project->modeler,$content,$url));
        $this->jsonOk("ok",[]);

    }



    public function marklists(){
        $pid = Input::get('pid',0);
        if($pid==0){
            return $this->jsonErr("missing parameter!");
        }
        $marks = BuildMark::where('bid',$pid)
            ->orderBy('id','DESC')
            ->get();
        if(count($marks)<1){
            return $this->jsonErr("no more data");
        }
        foreach($marks AS $k=>$v){
            $marks[$k]->img=$this->getOssPath($v['img'],'1000');
            $marks[$k]->attachment=$v['attachment'] ? $this->getOssPath($v['attachment'],'1000'):'';
            //找到最后一次乙方上传的图片
            $resp = MarkResponse::where('mid',$v->id)->orderBy('id','DESC')->first();
            $marks[$k]->status=0;
            $marks[$k]->response = "";
            $marks[$k]->has_record = 0;
            if($resp){
                $marks[$k]->response = $this->getOssPath($resp->attachment,'1000');
                $marks[$k]->status=$resp->status;
                if($resp->description){
                    $marks[$k]->description=$resp->description;
                }
                //甲方是否有过反馈
                $resp_count = MarkResponse::where('mid',$v->id)->sum('status');
                $marks[$k]->has_record = $resp_count>0?1:0;

            }
            if(!$resp){
                $marks[$k]->status=0;
            }
            $pics = [
                ['name'=>'Annotation','src'=> $marks[$k]->img,'default'=>0]
            ];
            if($v['attachment']){
                $pics[] = ['name'=>'Reference','src'=> $marks[$k]->attachment,'default'=>0];
            }
            $pics[]=[
                'name'=>'Update','src'=>$marks[$k]->response?$marks[$k]->response: env('APP_URL').'/images/Update.png','default'=>$marks[$k]->response?0:1
            ];
            $marks[$k]->pics=$pics;
            unset($marks[$k]->img);
            unset($marks[$k]->attachment);
            unset($marks[$k]->response);
            $role = $this->judgeCaller($pid);
            if($role==1){
                $marks[$k]->status = $marks[$k]->status_jia;
            }else{
                $marks[$k]->status = $marks[$k]->status_yi;
            }
            unset($marks[$k]->status_jia);
            unset($marks[$k]->status_yi);
        }
        return $this->jsonOk("ok",['lists'=>$marks]);
    }

    public function records(){
        $mid = Input::get('mid',0);
        if($mid==0){
            return $this->jsonErr("not found");
        }
        $resps = MarkResponse::where('mid',$mid)->whereIn('status',[1,2])->orderBy('id','DESC')->get();
        if(count($resps)<1){
            return $this->jsonErr("no more data");
        }
        $mark = BuildMark::select('title','description','img','attachment')->find($mid);
        $mark->img=$this->getOssPath($mark->img,'1000');
        //$mark->attachment=$this->getOssPath($mark->attachment,'500');
        //dd($resps);

        foreach($resps AS $k=>$v){
            $_r = $v->attachment;
            $resps[$k]->img=$mark->img;
            $resps[$k]->title=$mark->title;
            $resps[$k]->description=$v->description;
            $resps[$k]->attachment=$mark->attachment ? $this->getOssPath($mark->attachment,'1000'):'';
            $resps[$k]->response=$_r ? $this->getOssPath($_r,'1000'):'';

            //unset($resps[$k]->uid);

            $pics = [
                [
                    'name'=>'Annotation','src'=> $resps[$k]->img,'default'=>0
                ]
                ];
                $pics[] = [
                    'name'=>'Reference','src'=> $resps[$k]->attachment,'default'=>0
                ];
            $pics[]=[
                'name'=>'Update','src'=>$resps[$k]->response?$resps[$k]->response: env('APP_URL').'/images/Update.png','default'=>$resps[$k]->response?0:1
            ];
            $resps[$k]->pics=$pics;
            if($resps[$k]->status==2){
                $resps[$k]->status =0;
            }
            unset($resps[$k]->img);
            unset($resps[$k]->attachment);
            unset($resps[$k]->response);
            $resps[$k]->current_time = time();
        }
        return $this->jsonOk("ok",['list'=>$resps,'title'=>$mark->title]);


    }

    /**
     * 审核乙方修改
     * @param Request $req
     * @return mixed
     */
    public function check(Request $req){
        $mid = $req->get('mid',0);
        $status = $req->get('status',0);
        $desc = $req->get('desc','');
        if($mid==0 || $status==0){
            return $this->jsonErr("missing parameter");
        }
        $resp = \App\Model\MarkResponse::where('mid',$mid)->orderby('id','desc')->first();

        $resp->status = $status;
        $resp->description = $desc;
        if($resp->save()){
            $project = $resp->mark->build->project;

            $mark = $resp->mark;
            $content = 'The customer has reviewed your revisions on '.$mark->title.'Please check the status';
            $url = $_SERVER['REDIRECT_URL'].'/proposal-b/'.$project->prj_id;
            //\Event::fire(new BuildingEvent($project->prj_id,$project->modeler,$content,$url));

            $_mark = \App\Model\BuildMark::find($mid);

            if($status==1){  //通过
                $_mark->status_jia = 2;
                $_mark->status_yi = 3;
            }elseif($status==2){   //不通过
                $_mark->status_jia = 0;
                $_mark->status_yi = 2;
            }
            $_mark->save();
            return $this->jsonOk("ok",[]);
        }
        return $this->jsonErr("error");


    }
//    public function deleteProject(Request $req){
//            $id = $req->get('id',0);
//            $prj = Project::where(['prj_id'=>$id,'prj_uid'=>$this->_user->user_id,'prj_progress'=>1])->first();
//            if(!$prj){
//                return $this->jsonErr("not found");
//            }
//            if($prj->delete()){
//                return $this->jsonOk("delete successfully",[]);
//            }else{
//                return $this->jsonErr("delete failed");
//            }
//    }
    public function getPhotos($photos,$size='-1')
    {
        if(count($photos)){
            $photo = $photos->map(function ($item)use($size){
                $item['low'] = $this->getOssPath($item->oss_item_id,'500');
                $item['high'] = $this->getOssPath($item->oss_item_id,$size);
                unset($item->id,$item->build_id,$item->oss_item_id);
                return $item;
            });
            return $photo;
        }else{
            return '';
        }
    }

}
