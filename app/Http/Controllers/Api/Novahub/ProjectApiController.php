<?php

namespace App\Http\Controllers\Api\Novahub;

use App\Http\Controllers\Api\BaseApiController;
use App\libs\StaticConf;
use App\Model\BuildDaily;
use App\Model\BuildMark;
use App\Model\BuildPay;
use App\Model\Field;
use App\Model\Following;
use App\Model\MarkResponse;
use App\Model\Message;
use App\Model\Ossitem;
use App\Model\PrjApply;
use App\Model\PrjChat;
use App\Model\PrjInvite;
use App\Model\Project;
use App\Model\ProjectRate;
use App\Model\ProjectUser;
use App\Model\Rai;
use App\Model\User;
use App\Model\Wallet;
use App\Model\Work;
use App\Model\WorkDetail;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use phpDocumentor\Reflection\Types\Null_;
use Dingo\Api\Exception\ResourceException;
use Validator;

class ProjectApiController extends BaseApiController
{

    /**
     * 创建Project
     * @param Request $request
     * @return mixed
     */
    public function createProject(Request $request)
    {
        $rules = array(
            'name'  => 'required|max:100',
            'photo' => 'required',
            'description' => 'required',
            'type'=>'required|integer'
        );
        $validate = $this->postValidate($request->all(),$rules);
        if($validate){
            return $this->jsonErr($validate);
        }else{
            $cover = new Ossitem();
            $cover->oss_key       =   'elements';
            $cover->oss_path      =   $request->input('photo')['src'];
            $cover->oss_item_uid  =   $this->_user->user_id;
            $cover->oss_item_eid  =   0;
            $cover->size          =   $request->input('photo')['size'];
            $cover->save();
            $project = new Project();
            $project->prj_uid       =   $this->_user->user_id;
            $project->prj_name = $request->input('name');
            $project->prj_photos = $cover->oss_item_id;
            $project->prj_progress = 1;
            $project->prj_desc = $request->input('description');
            $project->prj_type = $request->input('type');   //0=商业项目 1=内部项目
            $project->save();
            $projectUser = new ProjectUser();
            $projectUser->project_id = $project->prj_id;
            $projectUser->user_id = $this->_user->user_id;
            if($project->prj_type==1){
                $projectUser->user_role = 7;    //内部项目管理员
            }else{
                $projectUser->user_role = 1;    //商业项目甲方
            }
            $projectUser->save();
            return $this->jsonOk('ok',['project_id'=>$project->prj_id]);
        }
    }

    /**
     * project设置 保存project
     * @param Request $request
     * @return mixed
     */
    public function saveProject(Request $request)
    {
        $rules = array(
            'name'  => 'required|max:100',
            'description' => 'required',
        );
        $validate = $this->postValidate($request->all(),$rules);
        if($validate){
            return $this->jsonErr($validate);
        }else{
            $project = Project::find($request->input('id'));
            if($request->input('photo')['size']){
                $cover = new Ossitem();
                $cover->oss_key       =   'elements';
                $cover->oss_path      =   $request->input('photo')['src'];
                $cover->oss_item_uid  =   $this->_user->user_id;
                $cover->oss_item_eid  =   0;
                $cover->size          =   $request->input('photo')['size'];
                $cover->save();
                $project->prj_photos = $cover->oss_item_id;
            }
            $project->prj_name = $request->input('name');
            $project->prj_desc = $request->input('description');
            $project->save();
        }
            return $this->jsonOk('ok',['project_id'=>$project->prj_id]);
    }

    /**
     * 删除项目
     * @return mixed
     */
    public function deleteProject()
    {
        $id = Input::get('id');
        $project = Project::find($id);
        if($project){
            if($this->_user->user_id==$project->prj_uid){
                $project->prj_delete=1;
                $project->save();
                return $this->jsonOk('ok','delete project');
            }else{
                return $this->jsonErr('no permission');
            }
        }else{
            return $this->jsonErr('no this project');
        }
    }

    /**
     * 商业项目保存quote
     * @param Request $request
     * @return mixed
     */
    public function saveQuote(Request $request)
    {
        $rules = array(
            'id'=>'required',
            'industry' => 'required',
            'categorys' => 'required',
            'tags'=>'required',
            'resolution'=>'required',
            'number'=>'required|numeric',
            'budget'=>'required',
            'duration'=>'required',
        );
        $validate = $this->postValidate($request->all(),$rules);
        if($validate) {
            return $this->jsonErr($validate);
        }else{
            $tags = $request->get('tags',[]);
            $tag = implode(',',$tags);
            $categorys = $request->get('categorys',[]);
            $category = implode(',',$categorys);
            $project = Project::find($request->input('id'));
            $project->prj_budget      =   $request->get('budget');      //模型均价
            $project->prj_industry  =   $request->get('industry');      //行业
            $project->prj_category  =   $category;          //类型
            $project->prj_accuracy =    $request->get('resolution');    //分辨率
            $project->prj_models_tot = $request->get('number');     //项目模型数量
            $project->prj_tags  =   $tag;           //标签
            $project->prj_expect  =   $request->get('duration');   //项目估算周期
            $project->prj_permission  =   1;
            $project->prj_payterm = 1;
            if($project->save()){
                Mail::later(10,'emailtpl.newProject', ['project'=>$project->prj_name], function ($message){
                    $message->to('wunan9531@gmail.com')->subject('New project');
                });
                return $this->jsonOk('ok','save successfully');
            }else{
                return $this->jsonErr('save error');
            }
        }
    }
    /**
     * 推荐列表
     * @param Request $request
     * @return mixed
     */
    public function recommendList(Request $request)
    {
        $id = Input::get('id');
        $permission = $this->userFeaturesPermission($id,'inviteToBid');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }
        $project = Project::find($id);
        $category = explode(",",$project->prj_category);
        $unrecommend_uids = PrjApply::where(['is_apply'=>2,'prj_id'=>$id])->orWhere(['is_invite'=>2,'prj_id'=>$id])->orWhere(['is_recommend'=>1,'prj_id'=>$id])->pluck('user_id');
        $apply_uids = PrjApply::where('prj_id',$id)->where(['apply_status'=>0,'is_apply'=>1])->orWhere(['apply_status'=>0,'is_invite'=>1])->pluck('user_id')->all();
        $select_uids[0] = Work::whereIn('work_cate',$category)->where('work_uid','!=',$this->_user->user_id)->with('user')->get();
        $select_uids[1] = Project::whereIn('prj_category',$category)->where('prj_modeler','!=',$this->_user->user_id)->where('prj_progress','>=',3)->with('modeler')->get();
        $recommend_uids[1] =collect($select_uids)->collapse()->filter(function ($item1){
            return $item1->user->user_type==3 || $item1->user->user_type==4;
        })->map(function($item2){
            return $item2 = $item2->user->user_id;
        })->unique();
        if(env('APP_URL')=='https://api.novaby.com'){
            $recommend_uids[2] = collect([10000,10001,10009]);
        }else{
            $recommend_uids[2] = collect([12442,12475]);
        }
        $recommend_uids[3] = $apply_uids;
        $unrecommend_uids = $unrecommend_uids->push($this->_user->user_id)->all();
        $recommend_uids = collect($recommend_uids)->collapse()->unique();
        $user_ids = $recommend_uids->filter(function ($item)use($unrecommend_uids){
            return !in_array($item,$unrecommend_uids);
        })->all();
        $users = [];
        foreach ($user_ids as $item){
            $user = $this->modelerInformation($request,$item,$project);
            $users[] = $user;
        }
        $users = collect($users)->sortByDesc(function($item){
            return $item->rai;
        })->sortByDesc(function($item){
            return $item->user_type;
        })->values();

        if($users->count()<1){
            $user_ids = User::whereIn('user_type',[1,2,3,4])->where('user_id','!=',$this->_user->user_id)->orderBy(DB::raw('RAND()'))->pluck('user_id')->take(5)->all();
            $users = [];
            foreach ($user_ids as $item){
                $user = $this->modelerInformation($request,$item,$project);
                $users[] = $user;
            }
            $users = collect($users)->sortByDesc(function($item){
                return $item->rai;
            })->values();
        }
        $permission = collect($permission)->map(function ($item)use($project){
            if($project->prj_type==0 && $project->prj_progress>=2){
                $item['operate'] = 0;
            }
            return $item;
        });
        return $this->jsonOk('ok',['users'=>$users,'sum'=>$users->count(),'functions'=>$permission]);
    }

    /**
     * 邀请
     * @param Request $request
     */
    public function invite(Request $request)
    {
        $id = $request->input('id');
        $uids = $request->input('uids');
        $project = Project::with('models')->find($id);
        if(!empty($uids)){
            foreach($uids AS $v){
                $inv = PrjApply::where(['prj_id'=>$id,'user_id'=>$v])->first();
                if(!$inv){
                    $inv = new PrjApply();
                    $inv->user_id = $v;
                    $inv->prj_id = $project->prj_id;
                    $inv->is_recommend=1;
                    $inv->apply_status = 0;
                }else{
                    if($inv->is_apply>=1){
                        $inv->is_apply=2;

                    }elseif($inv->is_invite>=1){
                        $inv->is_invite=2;
                    }else{
                        $inv->is_recommend=1;
                    }
                }
                $inv->prj_uptime = time();
                $inv->save();
            }
        }
        if(($project->prj_desc  || $project->prj_attachment) && count($project->models)){
            $requirement = 1;           //已经填写
            $this->ChangeInviteStatus($project,$uids);
        }else{
            $requirement = 0;           //还未填写
        }
        return $this->jsonOk('ok',['status'=>$requirement,'msg'=>'invite successful']);
    }

    /**
     * 上传原画
     * @param Request $request
     */
    public function requirement(Request $request)
    {
        $id = $request->get('id');
        $project = Project::find($id);
        $document  =   $request->get('docs',NULL);
        $models  =   $request->get('models',NULL);
        $deletes  =   $request->get('del_ids',NULL);
        if($document)
        {
            $did=[];      //项目需求文档
            foreach($document AS $k=>$v){
                $rec = new Ossitem();
                $rec->oss_key       =   'elements';
                $rec->oss_path      =   $v['src'];
                $rec->oss_item_uid  =   $this->_user->user_id;
                $rec->oss_item_eid  =   0;
                $rec->size          =   $v['size'];
                if($rec->save()){
                    $did[]=$rec->oss_item_id;
                }
            }
            $project->prj_attachment = implode(",",$did);
        }
        if($models){
            foreach ($models as $items)
            {
                if(isset($items['build_id']) && !empty($items['build_id'])){
                    $model = BuildDaily::find($items['build_id']);
                }else{
                    $model = new BuildDaily();
                }
                $model->bd_pid = $id;
                $model->bd_name = $items['name'];
                $model->bd_pid = $id;
                $model->bd_description = $items['desc'];
                $model->bd_pub = 0;
                $model->status = 0;
                $photo_id = [];    //模型原画
                if(isset($items['photos']) && !empty($items['photos']))
                {
                    foreach ($items['photos'] as $item1)
                    {
                        $rec = new Ossitem();
                        $rec->oss_key       =   'elements';
                        $rec->oss_path      =   $item1;
                        $rec->oss_item_uid  =   $this->_user->user_id;
                        $rec->oss_item_eid  =   0;
                        $rec->size          =   0;
                        if($rec->save()){
                            $photo_id[]=$rec->oss_item_id;
                        }
                    }
                    $model->bd_photos = implode(",",$photo_id);
                }else{
                    $model->bd_photos = 0;
                }
                $accth_id = [];    //模型需求文档
                if(isset($items['accaths'])){
                    foreach ($items['accaths'] as $item2)
                    {
                        $rec = new Ossitem();
                        $rec->oss_key       =   'elements';
                        $rec->oss_path      =   $item2['src'];
                        $rec->oss_item_uid  =   $this->_user->user_id;
                        $rec->oss_item_eid  =   0;
                        $rec->size          =   $item2['size'];
                        if($rec->save()){
                            $photo_id[]=$rec->oss_item_id;
                        }
                    }
                    $model->bd_document = implode(",",$accth_id);
                }else{
                    $model->bd_document = 0;
                }
                $model->save();
            }
        }
        if($deletes && $project->prj_type==1)
        {
            $deletes = implode(',',$deletes);
            $sql = 'update builddalys set is_del=1 where bd_id in ('.$deletes.')';
            DB::statement($sql);
        }
        if($project->prj_type==1) {
            $project->prj_progress = 2;
        }else{
            $users = PrjApply::where(['prj_id'=>$project->prj_id,'apply_status'=>0,'is_apply'=>2])
                ->orWhere(['prj_id'=>$project->prj_id,'apply_status'=>0,'is_invite'=>2])
                ->orWhere(['prj_id'=>$project->prj_id,'apply_status'=>0,'is_recommend'=>1])->lists('user_id');
            $this->ChangeInviteStatus($project,$users);
        }
        $project->save();
        return $this->jsonOk('ok','submit successfully');
    }

    /**
     * 更改邀请状态
     * @param $project
     * * @param $users
     */
     private function ChangeInviteStatus($project,$users)
     {
         foreach ($users as $item)
         {
             $user = User::find($item);
             $url = env('CLIENT_BASE').'novahub/project/'.$project->prj_id.'/requirement';
             Mail::later(10,'emailtpl.invite', ['user' =>$user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($user){
                 $message->to($user->user_email)->subject('Someone released a new project to invite you to apply!');
             });
             if($item!=$this->_user->user_id){
                 $msg = new Message();
                 $msg->msg_from_uid=$this->_user->user_id;
                 $msg->msg_to_uid=$item;
                 $msg->msg_action=5;
                 $msg->msg_rid=$project->prj_id;
                 $msg->msg_time = time();
                 $msg->save();
             }
         }
     }
    /**
     * 报价列表
     * @return mixed
     */
    public function applyList(Request $request)
    {
        $id = Input::get('id');
        $project = Project::find($id);
        $uids = PrjApply::where(['prj_id'=>$id,'is_apply'=>2])
            ->orWhere(['prj_id'=>$id,'is_invite'=>2])
            ->orWhere(['prj_id'=>$id,'is_recommend'=>1])
            ->pluck('user_id')
            ->all();
        $user_ids = array_unique($uids);
        $users = [];
        foreach ($user_ids as $item){
            $user = $this->modelerInformation($request,$item,$project);
            $users[] = $user;
        }
        $users = collect($users)->sortByDesc(function($item){
            return $item->rai;
        })->values();
        return $this->jsonOk('ok',['users'=>$users]);
    }

    /**
     * 获取支付合同
     * @return mixed
     */
    public function payTerm()
    {
        return $this->jsonOk('ok',['term'=>'I. CONFIDENTIAL INFORMATION. The term "Confidential Information" means any information or material which is proprietary to the Owner, whether or not owned or developed by the Owner, which is not generally known other than by the Owner, and which the Recipient may obtain through any direct or indirect contact with the Owner. Regardless of whether specifically identified as confidential or proprietary, Confidential Information shall include any information provided by the Owner concerning the business, technology and information of the Owner and any third party with which the Owner deals, including, without limitation, business records and plans, trade secrets, technical data, product ideas, contracts, financial information, pricing structure, discounts, computer programs and listings, source code and/or object code, copyrights and intellectual property, inventions, sales leads, strategic alliances, partners, and customer and client lists. The nature of the information and the manner of disclosure are such that a reasonable person would understand it to be confidential.']);
    }

    /**
     * 同意支付合同
     * @param Request $request
     * @return mixed
     */
    public function agreePayTerm(Request $request)
    {
        $id = $request->get('id');
        $user_id = $request->get('user_id');
        $project =  Project::find($id);
        $project->prj_payterm = 1;
        $project->save();
        $token = Input::get('token','');
        $payment = PrjApply::where(['prj_id'=>$id,'user_id'=>$user_id])->first();
        $contract = [ 'price'=>$payment->apply_price,
            'apply_time'=>$payment->apply_cost_time,
            'contract'=>$_SERVER['APP_URL'].'/api/task/getPdf/'.$id.'?token='.$token];
        return $this->jsonOk('ok',['contract'=>$contract]);
    }
    /**
     * 个人信息
     * @param $uid
     * @param $project
     * @param $code 1=推荐 2=报价
     * @return mixed
     */
    private function modelerInformation($request,$uid,$project){
        $item = User::select('user_id','user_name','user_lastname','user_country','user_city','user_icon','user_type','hourly_rate','company_name',
            'user_fileds','user_page_id','project_success','project_time','project_quality','project_commucation','project_amount','user_work_exp','year_founded','company_type')
            ->where('user_id',$uid)
            ->with(['info','build'=>function($query){$query->where('prj_progress',3.5);}])
            ->first();
        $item->user_country = $item->user_country?$item->country['name']:'';
        $item->user_city = $item->user_city?$item->city['name']:'';
        $item->name = $this->getName($item);
        $fields = explode(",",$item->user_fileds);
        if(count($fields)){
            $item->field = Field::whereIn('id',$fields)->pluck('name');
        }else{
            $item->field =[];
        }
        $build_count = $item->build->count();
        switch ($this->lang){
            case 'zh':
                if(count($fields)){
                    $fields = Field::whereIn('id',$fields)->pluck('name_cn');
                    $field = [];
                    foreach ($fields as $item1)
                    {
                        $field[] = $item1;
                    }
                    $item->field = $field;
                }else{
                    $item->field =[];
                }
                $item->hourly_rate = $item->hourly_rate?StaticConf::$hourly_rate_zh[$item->hourly_rate]:'';
                $item->user_work_exp = $item->user_work_exp?StaticConf::$work_exp_zh[$item->user_work_exp]:'';
                $item->company_type = $item->company_type?(StaticConf::$company_type_zh[$item->company_type])['name']:'';
                break;
            default:
                if(count($fields)){
                    $item->field = Field::whereIn('id',$fields)->pluck('name');
                }else{
                    $item->field =[];
                }
                $item->hourly_rate = $item->hourly_rate?StaticConf::$hourly_rate[$item->hourly_rate]:'';
                $item->user_work_exp = $item->user_work_exp?StaticConf::$work_exp[$item->user_work_exp]:'';
                $item->company_type = $item->company_type?(StaticConf::$company_type[$item->company_type])['name']:'';
                break;
        }
        $item->user_page_id = $item->user_type==4?$item->user_page_id:'';
        $item->project_success = $item->info->project_success?$item->project_success:'';
        $item->project_time = $item->info->project_time?$item->project_time:'';
        $item->project_quality = $item->info->project_quality?$item->project_quality:'';
        $item->project_commucation = $item->info->project_commucation?$item->project_commucation:'';
        $item->projects = $build_count>0?$build_count:'';
        $item->avatar = $this->getAvatar($item->user_icon,'100');
        $item->year_founded = $item->year_founded?$item->year_founded:'';
        $item->project_amount = $item->ifnoproject_amount?$this->transAmount($item->info->project_amount):'';
        //$rai = new Rai();
        //$item->rai = $rai->getRai($project->prj_category,$item->user_id);
        $item->rai = rand(80,99);
            $apply = PrjApply::where(['user_id'=>$uid,'prj_id'=>$project->prj_id])->first();
            if($apply){
                if($apply->is_apply>0){
                    $item->apply_type=1;    //申请
                }elseif($apply->is_invite>0){
                    $item->apply_type=2;    //邀请
                }else{
                    $item->apply_type=0;    //推荐
                }
                if($project->prj_modeler!=0){
                    if($project->prj_modeler==$item->user_id){
                        $item->status = 4;          //被选中
                    }else{
                        $item->status = 5;          //未被选中
                    }
                }else{
                    switch ($apply->apply_status){
                        case 1:
                        case 3:
                            $item->status = 1;  //同意NDA，并且报价
                            break;
                        case 2:
                            $item->status = 2;  //同意NDA，还没报价
                            break;
                        case 4:
                            $item->status = 3;  //拒绝DNA
                            break;
                        default:
                            $item->status = 0;  //没有查看
                            $item->re_send = 0;
                            if(time()-$apply->prj_uptime>=3600*24){
                                $item->re_send = 1;
                            }
                            break;
                    }
                }
                $item->apply_time = $apply->apply_cost_time?$apply->apply_cost_time:'';
                $item->apply_price = $apply->apply_price!=0.00?$apply->apply_price:'';
            }else{
                $item->apply_type=0;    //推荐
                $item->have_apply = '';
                $item->apply_time = '';
                $item->apply_price = '';
            }
        $item->work_covers = $this->userWorks($uid);
        unset($item->build,$item->info,$item->user_icon,$item->country,$item->city,$item->user_fileds,$item->company_name,$item->user_name,$item->user_lastname);
        return $item;

    }
    /**
     * 用户发布的最新10个作品
     * @param $id
     * @return array|string
     */
    public function userWorks($id)
    {
        $cover= Work::where('work_uid',$id)
            ->where('work_privacy',0)
            ->where('work_status',1)
            ->where('work_del',0)
            ->orderBy('work_id','DESC')
            ->limit(10)
            ->pluck('work_cover')
            ->all();
        if($cover) {
            $works = $this->path($cover,'1000');
        }else{
            $works=null;
        }
        if($works===null){
            $works="";
        }
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
        if($arrs) {
            for($i=0;$i<count($arrs);$i++) {
                $arr[] = $this->getOssPath($arrs[$i],$size);
            }
            return $arr;
        }
        else {
            return $this->getOssPath($arrs,$size);
        }
    }

    /**
     * 留言记录
     * @return mixed
     */
    public function getChatList()
    {
        $id = Input::get('id');
        $project = Project::find($id);
        $user_id= Input::get('user_id',0);
        if(!$this->_user){
            return $this->jsonErr('error','you have not permission');
        }else{
            if($user_id) {
                $chats = PrjChat::where(['prj_id' => $id, 'chat_from_uid' => $user_id, 'chat_to_uid' => $this->_user->user_id])
                    ->orWhere(['prj_id' => $id, 'chat_to_uid' => $user_id, 'chat_from_uid' => $this->_user->user_id])
                    ->orderBy('created_at')
                    ->get()->map(function ($item){
                        $userInfo = $this->getUserAvatarAndName($item->chat_from_uid,0);
                        $item->user_name = $userInfo->user_name;
                        $item->user_type = $userInfo->user_type;
                        $item->user_avatar = $userInfo->avatar;
                        $item->user_id= $userInfo->user_id;
                        $item->is_me = $item->chat_from_uid==$this->_user->user_id?1:2;
                        unset($item->chat_from_uid,$item->chat_to_uid,$item->prj_id,$item->id);
                        if(!$item->content){
                            $item = '';
                        }
                        return $item;
                    })->all();
                return $this->jsonOk('ok',['chat'=>$chats]);
            }else {
                if($this->_user->user_id==$project->prj_uid)    //作为甲方
                {
                    $chat[0] = PrjChat::where(['prj_id'=>$id,'chat_to_uid'=>$this->_user->user_id])
                        ->orderBy('created_at')
                        ->pluck('chat_from_uid')
                        ->unique()->all();
                    $chat[1] = PrjChat::where(['prj_id'=>$id,'chat_from_uid'=>$this->_user->user_id])
                        ->orderBy('created_at')
                        ->pluck('chat_to_uid')
                        ->unique()->all();
                    if($project->prj_modeler){
                        $chat[2] = [$project->prj_modeler];
                    }
                    $chat_users = collect($chat)->collapse()->unique();
                    if($chat_users){
                        $first = $chat_users->first();
                        $chat_lists = $chat_users->map(function ($item)use($first){
                            if($first == $item){
                                return $item = $this->getUserAvatarAndName($item,1);     //第一个人
                            }else{
                                return $item = $this->getUserAvatarAndName($item,0);
                            }
                        })->all();
                    }else{
                        $chat_lists = '';
                    }
                }else{                  //作为乙方
                    $chat_lists[] = $this->getUserAvatarAndName($project->prj_uid,1);
                }
                return $this->jsonOk('ok',['chat_lists'=>$chat_lists]);

            }
        }
    }

    /**
     * 创建会话
     * @param Request $request
     * @return mixed
     */
    public function createChat(Request $request)
    {
        $pid = $request->get('id');
        $user_id = $request->get('uid');
        $chat = PrjChat::where(['prj_id'=>$pid,'chat_to_uid'=>$user_id])
            ->orWhere(['prj_id'=>$pid,'chat_from_uid'=>$user_id])->first();
        if($chat){
            return $this->jsonOk('ok','createChat successful');
        }else{
            $chat = new PrjChat();
            $chat->prj_id = $pid;
            $chat->chat_from_uid = $this->_user->user_id;
            $chat->chat_to_uid = $user_id;
            $chat->content = NULL;
            $chat->created_at = time();
            $chat->save();
            return $this->jsonOk('ok','createChat successful');
        }
    }
    /**
     * 发送留言
     * @param Request $request
     * @return mixed
     */
    public function chatSend(Request $request)
    {
        $pid = $request->get('id');
        $user_id = $request->get('uid');
        if(!$user_id)
        {
            return $this->jsonErr('error','no user select');
        }
        $content = $request->get('content',NULL);
        $chat = PrjChat::where(['prj_id'=>$pid,'chat_to_uid'=>$this->_user->user_id,'chat_from_uid'=>$user_id])->first();
        if($chat){
            if($chat->content){
                $chat = new PrjChat();
            }
        }else{
            $chat = new PrjChat();
        }
        $chat->prj_id = $pid;
        $chat->chat_from_uid = $this->_user->user_id;
        $chat->chat_to_uid = $user_id;
        $chat->content = $content;
        $chat->created_at = time();
        $chat->save();
        return $this->jsonOk('ok','send successfully');
    }
    /**
     * 获取用户头像和name
     * @param $uid
     * @return mixed
     */
    private function getUserAvatarAndName($uid,$code)
    {
        $user = User::select('user_id','user_name','user_lastname','company_name','user_type','user_icon')->find($uid);
        $user->user_name = $this->getName($user);
        $user->avatar = $this->getAvatar($user->user_icon);
        if($code){
            $user->is_first = 1;
        }else{
            $user->is_first = 0;
        }
        unset($user->user_lastname,$user->user_icon,$user->company_name);
        return $user;
    }
    /**
     * 项目的模型列表
     * @return mixed
     */
    public function modelList(){
        $pid = Input::get('id',0);
        $project = Project::find($pid);
        if(!$project){
            return $this->jsonErr("not found");
        }else{
            $role = $this->judgeCaller($pid);
            switch ($role) {
                case 1:   //甲方
                    $build = BuildDaily::select('bd_id','bd_name','bd_status','bd_pub')
                        ->where('bd_pid', $pid)
                        ->orderby('bd_id', 'asc')
                        ->get();
                    if(!$build){
                        $build = '';
                    }
                    foreach ($build as $item)
                    {
                        if($item->bd_pub!=5 && $item->bd_status==1)
                        {
                            $item->bd_pub = 6;   //已查看
                        }elseif($item->bd_pub!=5){
                            $item->bd_pub = 7;   //未查看
                        }
                        unset($item->bd_status);
                    }
                break;
                case 2:   //乙方
                    $build = BuildDaily::select('bd_id','bd_name','bd_pub')
                        ->where('bd_pid', $pid)
                        ->orderby('bd_id', 'asc')
                        ->get();
                    if(!$build){
                        $build = '';
                    }
                    foreach ($build as $item)
                    {
                        if($item->bd_pub==0 || $item->bd_pub==4)    //已查看
                        {
                            $item->bd_pub = 6;
                        }
                    }
                break;
                default:
                    return $this->jsonErr('You dont\'t have permission!');
                    break;
            }
            return $this->jsonOk('ok',['build'=>$build]);
        }
    }

    /**
     * 项目的单个模型信息
     * @return mixed
     */
    public function modelInformation()
    {
        $build_id = Input::get('id');
        $build = BuildDaily::with('project')->with(['images','attach'])->find($build_id);
        $role = ProjectUser::where(['project_id'=>$build->project->prj_id,'user_id'=>$this->_user->user_id])->first();
        switch ($role->user_role)
        {
            case 1:             //外部项目甲方
                    if($build->bd_pub<4){
                        $work['status'] = $build->bd_attachment_trans?4:0;
                    }
                    elseif($build->bd_pub==4 && !$build->status) {
                        $work['status'] = 4;
                    }elseif($build->bd_pub==4 && $build->status==1){    //同意
                        $work['status'] = 5;
                    }elseif($build->bd_pub==4 && $build->status==2){     //拒绝
                        $work['status'] = 6;
                    }
                $work['name'] = $build->bd_name;
                $work['is_final'] = $build->bd_pub<4 || $build->status?0:$build->bd_final;
                $work['model_3d'] = ($build->bd_attachment_trans && $build->bd_3d)?$this->getModelFilesById($build->bd_attachment_trans):'';
                $work['model_2d'] = count($build->images)?$this->getPhotos($build->images):'';
                $work['model_attach'] = count($build->attach)?$this->getFiles($build->attach):'';
                break;
            case 2:             //外部项目乙方团队管理员
            case 3:             //外部项目乙方非团队管理员
            $model_trans = WorkDetail::where('w_id',$build_id)->whereNotNull('w_objs')->whereNotNull('w_format')->orderBy('id','DESC')->first();
            if($model_trans && $build->bd_3d){
                $work['status'] = $build->bd_pub;
                if($build->bd_pub==4 && !$build->status) {
                    $work['status'] = 4;
                }elseif($build->bd_pub==4 && $build->status==1){    //同意
                    $work['status'] = 5;
                }elseif($build->bd_pub==4 && $build->status==2){     //拒绝
                    $work['status'] = 6;
                }
                $work['name'] = $build->bd_name;
                $work['model_3d']= $this->getModelFilesById($model_trans->id)?$this->getModelFilesById($model_trans->id):$this->getModelFilesById($build->bd_attachment_trans);
            }else{
                $work['status'] = $build->bd_pub;
            }
            $work['is_final'] = $build->bd_final;
            $work['model_2d'] = count($build->images)?$this->getPhotos($build->images):'';
            $work['model_attach'] = count($build->attach)?$this->getFiles($build->attach):'';
                break;
            case 7:             //内部项目管理员
            case 8:             //内部项目非管理员
            $model_trans = WorkDetail::where('w_id',$build_id)->whereNotNull('w_objs')->whereNotNull('w_format')->orderBy('id','DESC')->first();
            if($model_trans && $build->bd_3d){
                $work['status'] = $build->bd_pub;
                if($build->bd_pub==4 && !$build->status) {
                    $work['status'] = 4;
                }elseif($build->bd_pub==4 && $build->status==1){    //同意
                    $work['status'] = 5;
                }elseif($build->bd_pub==4 && $build->status==2){     //拒绝
                    $work['status'] = 6;
                }
                $work['name'] = $build->bd_name;
                $work['model_3d']= $this->getModelFilesById($model_trans->id)?$this->getModelFilesById($model_trans->id):$this->getModelFilesById($build->bd_attachment_trans);
            }else{
                $work['status'] = $build->bd_pub;
            }
            $work['is_final'] = $build->bd_final;
            $work['model_2d'] = count($build->images)?$this->getPhotos($build->images):'';
            $work['model_attach'] = count($build->attach)?$this->getFiles($build->attach):'';
                break;
        }
        return $this->jsonOk('ok',['work'=>$work]);
    }

    /**
     * 标注问题列表
     * @param $bid
     * @param $pro_id
     * @return mixed
     */
    private function markLists($bid,$pro_id){
        $marks = BuildMark::where('bid',$bid)
            ->orderBy('update_time','DESC')
            ->orderBy('create_time','DESC')
            ->get();
        if(count($marks)<1){
            return [];
        }
        foreach($marks AS $k=>$v){
            $marks[$k]->status=0;
            $marks[$k]->response = "";
            $marks[$k]->has_record = 0;
            $default = 0;
            //标注图片
            $marks[$k]->img=$this->getOssPath($v['img'],'1000');
            //上传原画
            $marks[$k]->attachment=$v['attachment'] ? $this->getOssPath($v['attachment'],'1000'):'';
            //找到最后一次乙方上传的图片
            $resp = MarkResponse::where('mid',$v->id)->orderBy('id','DESC')->first();
            if($resp){
                //更新图片
                $marks[$k]->response = $this->getOssPath($resp->attachment,'1000');
//                $marks[$k]->status=$resp->status;
                if($resp->description){
                    $marks[$k]->description=$resp->description;
                }
                //甲方是否有过反馈
                $resp_count = MarkResponse::where('mid',$v->id)->whereIn('status',[1,2])->sum('status');
                $marks[$k]->has_record = $resp_count>1?1:0;
                if($resp->status==1){
                    $default = 1;  //反馈通过
                }else{
                    $default = 2;   //反馈不通过
                }
                if($resp->status==1){
                    $marks[$k]->status =2;      //通过
                }elseif($resp->status==3){
                    $marks[$k]->status =4;      //关闭
                }
                else{
                    if($marks[$k]->status_jia==0 && $marks[$k]->status_yi==0){
                        $marks[$k]->status =0;      //未上传
                    }elseif($marks[$k]->status_jia==1 && $marks[$k]->status_yi==1){
                        $marks[$k]->status =1;      //已上传，未操作
                    }else{
                        $marks[$k]->status =3;      //未通过
                    }
                }
            }else{
                $marks[$k]->status=0;
            }
            $pics[0] = ['name'=>'Annotation','src'=> $marks[$k]->img];
            $pics[1] = ['name'=>'Reference','src'=>$marks[$k]->attachment];
            $pics[2]=['name'=>'Update','src'=>$marks[$k]->response,'default'=>$default];
            $marks[$k]->pics=$pics;
            unset($marks[$k]->img);
            unset($marks[$k]->attachment);
            unset($marks[$k]->response);
            unset($marks[$k]->status_jia);
            unset($marks[$k]->status_yi);
            unset($marks[$k]->update_time);
        }
        return $marks;
    }
    /**
     * 甲方通过项目的单个模型
     * @param Request $request
     * @return mixed
     */
    public function accept(Request $request)
    {
        $build_id = $request->get('id');
        $result = $request->get('result');
        $build = BuildDaily::with('project')->find($build_id);
        $project = $build->project;
        if(!$build){
            return $this->jsonErr('model not find');
        }
        if($build->status==1 || $build->status==2) {
            return $this->jsonErr('this model has accepted ');
        }
        $build->bd_pub=4;
        $build->status=$result;
        $marks = BuildMark::where('bid',$build_id)
            ->orderBy('update_time','DESC')
            ->orderBy('create_time','DESC')
            ->get();
        foreach ($marks as $item){
            if($item->status==0 || $item->status==2){
                $item->status=3;
            }
            $item->save();
        }
        $build->save();
        $status = $this->modelStatus($build->bd_pid);
        if($status==0)
        {
            $project->prj_progress=3;
            $project->prj_success=1;
            if($project->prj_type==1){
                $project->prj_progress=3;
            }else{
                $price = PrjApply::where(['prj_id'=>$project->prj_id,'user_id'=>$project->prj_modeler])->first();
                $wallet = Wallet::where('uid',$project->prj_modeler)->first();
                if($wallet) {
                    $wallet->USD = $wallet->USD+$price->apply_price;
                }else{
                    $wallet = new Wallet();
                    $wallet->uid = $project->prj_modeler;
                    $wallet->USD = $price->apply_price;
                }
                $wallet->save();
            }
        }
        $project->prj_update_yi=1;
        $project->updated_at = date('Y-m-d H:i:s',time());
        $project->save();
        return $this->jsonOk('ok','accept successfully!');
    }
    /**
     * 模型状态
     * @param $projectId
     * @return int
     */
    private function modelStatus($projectId)
    {
        $project = Project::with('models')->find($projectId);
        $status=0;
        foreach ($project->models as $model)
        {
            if($model->bd_pub==4 && $model->status==1){
                $status+=0;
            }else{
                $status+=1;
            }
        }
        return $status;
    }

    /**
     * 项目结果
     * @param Request $request
     * @return mixed
     */
    public function PassOrFail(Request $request)
    {
        $project_id = $request->get('id');
        $result = $request->get('result',0);
        $project = Project::find($project_id);
        $project->prj_progress=3;
        $project->prj_success=$result;
        $project->updated_at = date('Y-m-d H:i:s',time());
        $project->save();
        return $this->jsonOk('ok','review successfully');
    }

    /**
     * 项目评分
     * @param Request $req
     * @return mixed
     */
    public function Rate(Request $req)
    {
        $id = $req->get('id');
        $project = Project::find($id);
        if($project->prj_progress<3){
            return $this->jsonErr('submission failed');
        }
        if($project->prj_success==1){
            $result=2;      //pass
        }else{
            $result=1;      //fail
        }
        $project->prj_progress = 3.5;
        $project->prj_update_yi=1;
        $project->updated_at = time();
        $project->save();
        $rate_time = $req->get('rate_time',0);
        $rate_quality = $req->get('rate_quality',0);
        $rate_communication = $req->get('rate_communication',0);
        $comments = $req->get('comment',NULL);
        $rate = new ProjectRate();
        $rate->r_time=$rate_time;
        $rate->r_quality = $rate_quality;
        $rate->r_other = $rate_communication;
        $rate->r_pid=$id;
        $rate->r_comment=$comments;
        $rate->r_catetime=time();
        $rate->r_result=$result;
        $rate->save();
        return $this->jsonOk('ok','submission successfully');
    }
    /**
     * 获取项目结果
     * @return mixed
     */
    public function getResult()
    {
        $user = $this->_user;
        $project_id = Input::get('id');
        $project = Project::find($project_id);
        if($user->user_id == $project->prj_uid){  //甲方
            $role = 0;
        }elseif($user->user_id == $project->prj_modeler){   //乙方
            $role = 1;
        }else{    //无权查看
            return $this->jsonErr('error','No Permission');
        }
        $projects['role'] = $role;
        if($project->prj_progress<3){
            $projects['result'] = '';
            $rate = '';
            $projects['have_result']=0;    //0，还没有结果
        } else{
            switch ($project->prj_success){
                case '1':
                    $file = [];
                    $builds = BuildDaily::select('bd_attachment')
                        ->where('bd_pid',$project_id)
                        ->get();
                    foreach ($builds as $build)
                    {
                        $file[] = $this->zipinfo($build->bd_attachment);
                    }
                    $projects['result'] = $file;
                    $rate = ProjectRate::where('r_pid',$project_id)->first();
                    $projects['have_result']=1;    //有结果
                    break;
                default:
                    $projects['result'] = '';
                    $rate = ProjectRate::where('r_pid',$project_id)->first();
                    $projects['have_result']=1;    //有结果
                    break;
            }
        }
        $projects['mark'] = $rate?$rate:'';
        return $this->jsonOk('ok',['projectResult'=>$projects]);
    }
    /**
     * 合同和支付信息
     * @return string
     */
    public function Contract(){
        $token = Input::get('token','');
        $id = Input::get('id',0);
        $project = Project::find($id);
        if(!$project){
            return $this->jsonErr("not found");
        }
        if(!$project->prj_modeler){
            return $this->jsonOk('ok',['bill'=>'']);
        }else{
            $apply = PrjApply::where(['prj_id'=>$id,'user_id'=>$project->prj_modeler])->first();
            $price_hour = $apply->apply_price.' USD';
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
            $contract = [
                'price'=>$price_hour,
                'pay_method'=>$pay_method,
                'pay_time'=>$pay_time,
                'order_no'=>$order_no,
                'contract'=>$_SERVER['APP_URL'].'/api/task/getPdf/'.$id.'?token='.$token,
                'has_pay'=>1,
            ];
        }
        return $this->jsonOk('ok',['bill'=>$contract]);
    }

}
