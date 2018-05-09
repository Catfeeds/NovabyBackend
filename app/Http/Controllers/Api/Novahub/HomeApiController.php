<?php

namespace App\Http\Controllers\Api\Novahub;

use App\Http\Controllers\Api\BaseApiController;
use App\Jobs\ProjectTrans;
use App\libs\StaticConf;
use App\Model\BuildDaily;
use App\Model\BuildMark;
use App\Model\Feedback;
use App\Model\MarkComment;
use App\Model\Message;
use App\Model\Ossitem;
use App\Model\Permission;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\ProjectInvite;
use App\Model\ProjectRate;
use App\Model\ProjectUser;
use App\Model\Role;
use App\Model\TeamRelation;
use App\Model\User;
use App\Model\UserCloud;
use App\Model\UserService;
use App\Model\UserTeam;
use Carbon\Carbon;
use Hamcrest\Core\IsNot;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class HomeApiController extends BaseApiController
{
    /**
     * Novahub我的项目
     * $type 0:商业项目 1:内部项目
     * @return mixed
     */
    public function project()
    {
        $select = Input::get('select', 0);
        $page = Input::get('page', 1);
        $type = Input::get('type', 0);
        $pageSize = Input::get('pagesize', 12);
        $offset = ($page - 1) * $pageSize;
        switch ($select) {
            case 1:
                $projects = $this->projectA($pageSize, $offset, $type);    //发布的项目
                break;
            case 2:
                $projects = $this->projectB($pageSize, $offset, $type);    //参与的项目
                break;
            default:
                $projects = $this->projectAll($pageSize, $offset, $type);
                break;
        }
        return $this->jsonOk('ok', ['projects' => $projects]);
    }
    /**
     * 获取全部相关项目
     * @param $pageSize
     * @param $offset
     * @param $type
     * @return mixed
     */
    private function projectAll($pageSize, $offset, $type)
    {
        $ids[0] = PrjApply::where('user_id', $this->_user->user_id)
            ->whereIn('apply_status', [1, 2, 3])
            ->orderBy('id', 'DESC')
            ->pluck('prj_id')->all();
        $ids[1] = Project::where('prj_uid', $this->_user->user_id)
            ->orderBy('prj_id', 'DESC')
            ->pluck('prj_id')->all();
        $ids[2] = ProjectUser::where('user_id', $this->_user->user_id)
            ->orderBy('project_id', 'DESC')
            ->pluck('project_id')->all();
        $where = '';
        if($type==1)
        {
            $where .= '1=1';
        }else{
            $where .= 'prj_industry!=0';
        }
        $ids = array_unique(collect($ids)->collapse()->all());
        $projects = Project::select('prj_id','prj_delete', 'prj_name','prj_industry', 'created_at', 'updated_at', 'prj_photos', 'prj_progress', 'prj_update_jia', 'prj_update_jia', 'prj_uid', 'prj_modeler', 'prj_success','prj_desc')
            ->where('prj_type', $type)
            ->whereRAW($where)
            ->whereIn('prj_id', $ids)
            ->where('prj_delete','!=',1)
            ->with('apply')
            ->take($pageSize)
            ->skip($offset)
            ->orderBy('prj_id', 'DESC')
            ->get();
        $tot = Project::where('prj_type', $type)
            ->whereIn('prj_id', $ids)
            ->count();
        foreach ($projects AS $item) {
            $_covers = explode(',', $item->prj_photos);
            $item->prj_cover = $this->getOssPath($_covers[0], '300');
            $t_carbon = new Carbon($item->updated_at);
            $t_int = $t_carbon->timestamp;
            $item->prj_created_at = $t_int;
            $item->can_delete = $item->prj_uid==$this->_user->user_id?1:0;
            unset($item->prj_photos);
            unset($item->created_at, $item->updated_at);
            $item->prj_progress = $this->projectProgress($item);
//            if ($item->apply->count() == 0 && $item->prj_uid == $this->_user->user_id) {  //项目是否可以删除
//                $item->is_delete = 1; //无人报价,可以删除
//            } else {
//                $item->is_delete = 0; //有人报价,不能删除
//            }
            if ($item->prj_uid == $this->_user->user_id) {    //项目是否属于我
                $item->is_belong = 1; //甲方
                $item->redirect = $this->ProjectRedirect($item,1);
                $item->prj_update = $item->prj_update_jia == 1 ? 1 : 0;
            } elseif ($item->prj_modeler != 0 && $item->prj_modeler == $this->_user->user_id) {
                $item->is_belong = 2; //模型师
                $item->redirect = $this->ProjectRedirect($item,0);
                $item->prj_update = $item->prj_update_yi == 1 ? 1 : 0;
            } else {
                $item->is_belong = 0;   //报价
                $item->redirect = $this->ProjectRedirect($item,0);
                $item->prj_update = 0;
            }
            unset($item->apply, $item->prj_uid, $item->prj_update_jia, $item->prj_update_yi,$item->prj_success,$item->prj_industry);
        }
        $project['project'] = $projects;
        $project['tot'] = $tot;
        return $project;
    }

    /**
     * 获取发布的项目
     * @param $pageSize
     * @param $offset
     * @param $type
     * @return array
     */
    private function projectA($pageSize, $offset, $type)
    {
        $projectA = [];
        $projects = Project::select('prj_id', 'prj_name', 'created_at', 'prj_photos', 'prj_progress', 'prj_update', 'prj_modeler')
            ->where('prj_uid', $this->_user->user_id)
            ->where('prj_type', $type)
            ->with('apply')
            ->take($pageSize)
            ->skip($offset)
            ->orderBy('prj_id', 'DESC')
            ->get();
        foreach ($projects AS $item) {
            $_covers = explode(',', $item->prj_photos);
            $item->prj_cover = $this->getOssPath($_covers[0], '300');
            $t_carbon = new Carbon($item->created_at);
            $t_int = $t_carbon->timestamp;
            $item->prj_created_at = $t_int;
            unset($item->prj_photos);
            unset($item->created_at);
            $progress = $this->projectProgress($item);
            if ($item->apply->count() == 0) {
                $item->is_delete = 1; //无人报价,可以删除
            } else {
                $item->is_delete = 0; //有人报价,不能删除
            }
            unset($item->apply);
        }
        $tot = Project::where('prj_uid', $this->_user->user_id)->count();
        $projectA['project'] = $projects->all();
        $projectA['tot'] = $tot;
        return $projectA;
    }

    /**
     * 获取参与的项目
     * @param $pageSize
     * @param $offset
     * @param $type
     * @return array
     */
    private function projectB($pageSize, $offset, $type)
    {
        $applies = PrjApply::where('apply_status', 1)
            ->where('user_id', $this->_user->user_id)
            ->with(['project' => function ($query) use ($type) {
                $query->where('prj_type', $type);
            }])
            ->take($pageSize)
            ->skip($offset)
            ->orderBy('id', 'DESC')
            ->get();
        $projects = [];
        foreach ($applies AS $k => $v) {
            $prj = $v->project;
            $_covers = explode(',', $prj->prj_photos);
            $t_carbon = new Carbon($prj->created_at);
            $t_int = $t_carbon->timestamp;
            $progress = $this->projectProgress($prj);
            $projects[] = [
                'prj_id' => $prj->prj_id,
                'prj_name' => $prj->prj_name,
                'prj_type' => $prj->prj_type,
                'prj_created_at' => $t_int,
                'prj_progress' => $progress,
                'prj_cover' => $this->getOssPath($_covers[0],'500'),
                'prj_update' => $prj->prj_update,
                'is_delete' => 0,
            ];
        }
        $projectB = [];
        $projectB['project'] = $projects;
        $tot = PrjApply::where('apply_status', 1)->where('user_id', $this->_user->user_id)->count();
        $projectB['tot'] = $tot;
        return $projectB;
    }

    /**
     * 判断Cloud空间是否足够
     * @param $user_id
     * @param $size
     * @return mixed
     */
    public function cloud($user_id, $size)
    {
        $cloud = UserCloud::where('user_id', $user_id)->first();
        if ($size > $cloud->surplus_cloud) {
            return $this->jsonErr('error', 'Your remaining space is not enough');
        } else {
            $cloud->surplus_cloud = $cloud->surplus_cloud - $size;
            $cloud->use_cloud = $cloud->use_cloud + $size;
            $cloud->save();
            return $this->jsonOk('ok', 'Your remaining space is enough');
        }
    }

    /**
     * 获取个人项目数量
     * @return mixed
     */
    public function projectCount()
    {
        if ($this->_user) {
            $totA = Project::where('prj_uid', $this->_user->user_id)->count();
            $totB = PrjApply::where('apply_status', 1)->where('user_id', $this->_user->user_id)->count();
            if ($totA || $totB) {
                return $this->jsonOk('ok', $totA + $totB);
            } else {
                return $this->jsonOk('ok', '');
            }
        } else {
            return $this->jsonErr('error', 'not login');
        }
    }

    /**
     * 项目Help
     * @param Request $request
     * @return mixed
     */
    public function projectHelp(Request $request)
    {
        $content = $request->get('content');
        $id = $request->get('id');
        $email = $this->_user['user_email'];
        $help = new Feedback();
        $help->feed_pid = $id;
        $help->feed_content = $content;
        $help->feed_email = $email;
        $help->save();
        return $this->jsonOk('ok', 'send successfully');
    }

    /**
     * 判断项目进度
     * @param $prj
     * @return int
     */
    private function projectProgress($prj)
    {
        switch ($prj->prj_progress) {
            case 1:
                $prj->prj_progress = 1; //Quoting
                break;
            case 2:
                if ($prj->prj_modeler != $this->_user->user_id && $prj->prj_uid != $this->_user->user_id) {
                    $prj->prj_progress = 3; //Closed
                } else {
                    $prj->prj_progress = 2;   //Ongoing
                }
                break;
            case 3:
            default:
                if ($prj->prj_success == 1) {
                    $prj->prj_progress = 4;   //Pass
                } elseif ($prj->prj_success == 0) {
                    $prj->prj_progress = 5;   //Fail
                }
                break;
        }
        return $prj->prj_progress;
    }

    public function userRoleAndPermission()
    {
        $lang = $this->lang;
       $prj_id = Input::get('id');
       $apply = PrjApply::where(['prj_id' => $prj_id, 'user_id' =>$this->_user->user_id])->first();
       $invite = ProjectInvite::where(['project_id'=>$prj_id,'user_id'=>$this->_user->user_id])->first();
       $users = ProjectUser::where(['project_id'=>$prj_id,'user_id'=>$this->_user->user_id])->first();
       if (!$apply && !$invite && !$users) {
           return $this->jsonErr('error', 'no permission');
       }
       elseif($apply){
           if ($apply->apply_status > 0 && $apply->apply_status < 4) {
               $agree = 1;     //同意NDA
           } elseif ($apply->apply_status == 4) {
               $agree = 2;     //没有同意NDA
           } else {
               $agree = 0;     //没查看
           }
       }elseif($invite){
        $agree =   $invite->status;
       }else{
           $agree =1;
       }
       $user_role = $this->isUserBelongProject($prj_id);
       if(!$user_role || !$user_role->role){
           $menu = '';
       }else{
           $permission = $user_role->role->permission->map(function ($item)use($lang){
               if($lang=='zh'){
                   $item->name = $item->name_cn;
               }
               unset($item->name_cn);
               return $item;
           })->sortBy('id');
           $menu = $this->Menu($permission);
           foreach ($menu as $k => $v){
               $menu[$v->display] = $v;
               foreach ($v->value as $key2 => $value2) {
                  $v->value[$value2->display] = $value2;
                  unset($v->value[$key2]);
               }
               unset($menu[$k],$v->child_menu,$v->display,$v->url);
           }
       }
       $data['have_agree'] = $agree;
       $data['menu'] = $menu;
       return $this->jsonOk('ok',$data);
    }

    /**
     * 获取项目信息
     * @return mixed
     */
    public function getProjectInformation()
    {
        $id = Input::get('id');
        $project  = Project::select('prj_name','prj_photos','prj_desc')->find($id);
        $project->name = $project->prj_name;
        $project->photo = $this->getOssPath($project->prj_photos,'500');
        $project->description = $project->prj_desc;
        unset($project->prj_name,$project->prj_photos,$project->prj_desc);
        $permission = $this->userFeaturesPermission($id,'projectSetting');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else{
            return $this->jsonOk('ok', ['project' => $project,'functions'=>$permission]);
        }

    }

    /**
     * 获取team成员列表
     * @return mixed
     */
    public function importTeam()
    {
        $project_id = Input::get('project_id');
        $team = TeamRelation::where('user_id',$this->_user->user_id)->first();
        if(!$team){
            return $this->jsonErr('error','not find team');
        }else{
            $teamUsers = TeamRelation::with('user')->where('team_id',$team->team_id)->get();
            $projectUser = ProjectUser::where('project_id',$project_id)->pluck('user_id')->all();
            $users = $teamUsers->filter(function ($item)use($projectUser){
                $item->user_name = $this->getName($item->user);
                $item->user_avatar = $this->getOssPath($item->user->user_icon,100);
                $item->user_type = $item->user->user_type;
                unset($item->user,$item->id,$item->team_id);
                return !in_array($item->user_id,$projectUser);
            })->values()->all();
            return $this->jsonOk('ok',['users'=>$users,'team_id'=>$team->team_id]);
        }
    }

    /**
     * 导入团队成员
     * @param Request $request
     * @return mixed
     */
    public function saveImport(Request $request)
    {
        $project_id = $request->input('project_id');
        $uids = $request->input('uids');
        $rule = [
            'uids'=>'required',
            'project_id'=>'required'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        $value = null;
        $project = Project::find($project_id);
        switch ($project->prj_type)
        {
            case 1:
                foreach($uids as $user_id)
                {
                    $value .= '('.$project_id.','.$user_id.',8),';
                }
                break;
            default:
                foreach($uids as $user_id)
                {
                    $value .= '('.$project_id.','.$user_id.',5),';
                }
                break;
        }
        $sql = 'insert into project_users(project_id,user_id,user_role)value'.rtrim($value,',');
        $insert = DB::statement($sql);
        if($insert){
            return $this->jsonOk('ok','import successfully');
        }else{
            return $this->jsonErr('error');
        }
    }
    /**
     * 查看项目成员
     * @return mixed
     */
    public function getMembers()
    {
        $id = Input::get('id');
        $projectUser = ProjectUser::where(['user_id'=>$this->_user->user_id,'project_id'=>$id])->first();
        $project = Project::find($id);
        switch ($project->prj_type)
        {
            case 1:
                $users = ProjectUser::select('user_id','user_role')->with('user','role')->where('project_id',$id)->whereIn('user_role',[7,8])->get();
                $users = $users->map(function ($item)use($project,$projectUser){
                    $item->user_name = $this->getName($item->user);
                    $item->user_type = $item->user->user_type;
                    $item->user_avatar = $this->getAvatar($item->user->user_icon);
                    $item->role_name = $item->role->name;
                    $item->user_role = $item->role->id;
                    $item->remove = $item->user->user_id==$this->_user->user_id||
                    $item->user->user_id==$project->prj_uid ||
                    $item->role->id<$projectUser->user_role?0:1;

                    $item->operate = $item->user->user_id==$this->_user->user_id||
                    $item->user->user_id==$project->prj_uid ||
                    $item->user->user_id==$project->prj_modeler ||
                    $item->role->id<$projectUser->user_role ||
                    ($item->role->id==2 && $projectUser->user_role==1) ||
                    ($item->role->id==3 && $projectUser->user_role==1)?0:1;
                    unset($item->user,$item->role);
                    return $item;
                });
                $roles = Role::select('id','name','name_cn')->where('type',1)->where('active',1)->get();
                break;
            default:
                $users = ProjectUser::select('user_id','user_role')->with('user','role')->where('project_id',$id)->whereIn('user_role',[1,2,3,5])->get();
                $users = $users->map(function ($item)use($project,$projectUser){
                    $item->user_name = $this->getName($item->user);
                    $item->user_type = $item->user->user_type;
                    $item->user_avatar = $this->getAvatar($item->user->user_icon);
                    $item->role_name = $item->role->name;
                    $item->user_role = $item->role->id;
                    $item->remove = $item->user->user_id==$this->_user->user_id||       //remove权限
                    $item->user->user_id==$project->prj_uid ||
                    $item->user->user_id==$project->prj_modeler ||
                    $item->role->id<$projectUser->user_role ||
                    ($item->role->id==2 && $projectUser->user_role==1) ||
                    ($item->role->id==3 && $projectUser->user_role==1)?0:1;

                    $item->operate = $item->user->user_id==$this->_user->user_id||      //操作权限
                    $item->user->user_id==$project->prj_uid ||
                    $item->user->user_id==$project->prj_modeler ||
                    $item->role->id<$projectUser->user_role ||
                    ($item->role->id==2 && $projectUser->user_role==1) ||
                    ($item->role->id==3 && $projectUser->user_role==1)?0:1;
                    unset($item->user,$item->role);
                    return $item;
                });
                $roles = Role::select('id','name','name_cn')->where('type',0)->where('active',1)->get();
                break;
        }
        $users = $users->sortBy('user_role')->groupBy(function ($item){
            return $item->user_role;
        });
        $result = [];
        $result = $users->map(function ($item,$key)use($result,$projectUser,$roles){
            $result = [
                'name'=>$this->lang=='zh'?Role::find($key)->name_cn:Role::find($key)->name,
                'lists'=>$item
            ];
            return $result;
        })->values();
        $roles = $this->getRole($id,$roles);
        return $this->jsonOk('ok',['users'=>$result,'roles'=>$roles]);
    }

    /**
     * @param $id
     * @param $roles
     * @return null
     */
    private function getRole($id,$roles)
    {
        $project_id = $id;
        $project_user = ProjectUser::where(['user_id'=>$this->_user->user_id,'project_id'=>$project_id])->first();
        $roles = $roles->map(function ($item){
            switch ($this->lang){
                case 'zh':
                    $name = $item->name_cn;
                    break;
                default:
                    $name = $item->name;
                    break;
            }
            $item->name = $name;
            unset($item->pid,$item->type,$item->active,$item->name_cn);
            return $item;
        });
        switch ($project_user->user_role)
        {
            case 1:         //外部项目-甲方
                $roles = $roles->filter(function ($item){
                    return $item->id ==1 || $item->id==5;
                })->values();
                break;
            case 2:         //外部项目-乙方团队管理员
                $roles = $roles->filter(function ($item){
                    return in_array($item->id,[2,3,5]);
                })->values();
                break;
            case 3:         //外部项目-乙方非团队管理员
                $roles = $roles->filter(function ($item){
                    return in_array($item->id,[3,5]);
                })->values();
                break;
            case 5:         //未分组成员
                $roles = null;
                break;
            case 7:         //内部项目-管理员
                $roles = $roles->filter(function ($item){
                    return in_array($item->id,[7,8]);
                })->values();
                break;
            case 8:         //内部项目-非管理员
                $roles = $roles->filter(function ($item){
                    return in_array($item->id,[8]);
                })->values();
                break;
            default:
                break;
        }
        return $roles;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function removeMember(Request $request)
    {
        $project_id = $request->input('id');
        $user_id = $request->input('user_id');
        $rule = [
            'user_id'=>'required',
            'id'=>'required'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        $project_user = ProjectUser::where(['project_id'=>$project_id,'user_id'=>$user_id])->first();
        $project_apply = PrjApply::where(['prj_id'=>$project_id,'user_id'=>$user_id])->first();
        if($project_user){
            $project_user->delete();
            if($project_apply){
                $project_apply->delete();
            }
            return $this->jsonOk('ok','remove successfully');
        }else{
            return $this->jsonErr('error','remove error');
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function saveMember(Request $request)
    {
        $project_id = $request->input('id');
        $users = $request->input('users');
        $rule = [
            'users'=>'required',
            'id'=>'required'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        foreach ($users as $item)
        {
            ProjectUser::where(['project_id'=>$project_id,'user_id'=>$item['id']])->update(['user_role'=>$item['role']]);
        }
        return $this->jsonOk('ok','update successfully');
    }
    /**
     * 邀请成员加入
     * @param Request $request
     */
    public function inviteMember(Request $request)
    {
        $ids = $request->input('ids');
        $project_id = $request->input('project_id');
        $rule = [
            'ids'=>'required',
            'project_id'=>'required'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        $project = Project::find($project_id);
        if($project){
            $this->invite($ids,$project);
            return $this->jsonOk('ok','send successfully');
        }else{
            return $this->jsonErr('no this project');
        }
    }

    /**
     * @param $ids
     * @param $project
     */
    private function invite($ids,$project)
    {
        $ids = collect($ids)->unique();
        foreach($ids as $user_id)
        {
            $result = ProjectInvite::where(['project_id'=>$project->prj_id,'user_id'=>$user_id])->first();
            if(!$result){
                $this->sendInvite($project,$user_id,$this->_user->user_id,0);
            }else{
                switch ($result->status){
                    case 1:         //同意不发送
                        break;
                    case 2:         //拒绝重新发送
                        $this->sendInvite($project,$user_id,$this->_user->user_id,2);
                        break;
                    default:        //未操作
                        $message = Message::where(['msg_rid'=>$project->prj_id,'msg_to_uid'=>$user_id])->first();
                        $message->msg_time = time();
                        $message->msg_read = 0;
                        $message->msg_delete = 0; //针对消息被删除的情况
                        $message->save();
                        break;
                }
            }
        }
    }
    /**
     * 封装邀请发送
     * @param $project
     * @param $user_id
     * @param $inviter_id
     */
    private function sendInvite($project,$user_id,$inviter_id,$code)
    {
        if($code){
            $invite = ProjectInvite::where(['project_id'=>$project->prj_id,'user_id'=>$user_id])->first();
        }else{
            $invite = new ProjectInvite();
        }
        $invite->project_id = $project->prj_id;
        $invite->user_id = $user_id;
        $invite->status = 0;
        $invite->save();
        $message = new Message();
        $message->msg_from_uid = $inviter_id;
        $message->msg_to_uid = $user_id;
        $message->msg_action = 5;
        $message->msg_rid = $project->prj_id;
        $message->msg_remark = $project->prj_name;
        $message->msg_time = time();
        $message->save();
    }
    /**
     * 获取项目quote
     * @return mixed
     */
    public function getQuote()
    {
        $id = Input::get('id');
        $permission = $this->userFeaturesPermission($id,'requirement');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else{
            $project = Project::select('prj_industry','prj_category','prj_tags','prj_accuracy','prj_models_tot','prj_budget','prj_expect')->find($id);
            $project->prj_resolution = $project->prj_accuracy;
            $project->prj_category = explode(",",$project->prj_category);
            $project->prj_tags = explode(",",$project->prj_tags)?explode(",",$project->prj_tags):'';
            $project->prj_duration = $project->prj_expect;
            unset($project->prj_accuracy,$project->prj_expect);
            return $this->jsonOk('ok', ['projects' => $project,'functions'=>$permission]);
        }

    }
    /**
     * 获取项目原画需求
     * @return mixed
     */
    public function getRequirement()
    {
        $id = Input::get('id');
        $permission = $this->userFeaturesPermission($id,'modelSpecification');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else{
            $project = Project::select('prj_id', 'prj_attachment', 'prj_desc','prj_progress','prj_type')->with(['models' => function ($query) {
                $query->select('bd_pid', 'bd_id','bd_photos', 'bd_name', 'bd_description', 'bd_document')->where('is_del',0);
            }])->find($id);
            $attach = [];
            if ($project->prj_attachment) {
                foreach (explode(',', $project->prj_attachment) as $items) {
                    $attach[] = $this->zipinfo($items);
                }
            }
            $project->attach = $attach;
            $project->desc = $project->prj_desc;
            $project->models = $project->models->map(function ($item) {
                $item->name = $item->bd_name;
                $photos = [];
                if ($item->bd_photos) {
                    foreach (explode(',', $item->bd_photos) as $items) {
                        $oss = Ossitem::find($items);
                        $photos[] = [
                            'url'=>$this->getOssPath($items,'500'),
                            'src'=>$oss->oss_path
                            ];
                    }
                }
                $item->photos = $photos;
                $item->desc = $item->bd_description;
                $document = [];
                if ($item->bd_document) {
                    foreach (explode(',', $item->bd_document) as $items) {
                        $document[] = $this->zipinfo($items);
                    }
                }
                $item->attach = $document;
                $item->build_id=$item->bd_id;
                unset($item->bd_id,$item->bd_name, $item->bd_photos, $item->bd_description, $item->bd_document, $item->bd_pid);
                return $item;
            })->all();
            $permission = collect($permission)->map(function ($item)use($project){
                if($project->prj_type==0 && $project->prj_progress>=2){
                    $item['operate'] = 0;
                }
                return $item;
            });
            unset($project->prj_progress,$project->prj_type);
            return $this->jsonOk('ok', ['projects' => $project,'functions'=>$permission]);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getWorkBench(Request $request)
    {
        $id = $request->get('id');
        $permission = $this->userFeaturesPermission($id,'modelWorkbench');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else {
            $project = Project::with('models')->find($id);
            if ($project->prj_progress < 2) {
                $status = 0;
            } else {
                $status = 1;
            }
            $role = ProjectUser::where(['project_id' => $id, 'user_id' => $this->_user->user_id])->first();
            if ($project->models) {
                $models = $project->models->filter(function ($item1){
                    return $item1->is_del!=1;
                })->map(function ($item2) use ($role) {
                    $model = (object)[];
                    $model->id = $item2->bd_id;
                    $model->name = $item2->bd_name;
                    $model->status = $this->getModelStatus($role->user_role, $item2->bd_pub, $item2->status);
                    return $model;
                })->values();
            } else {
                $models = '';
            }
            $result = '';
            switch ($project->prj_type) {
                case 1:         //内部项目
                    if ($project->prj_progress >= 3) {
                        $result = 'The current project has been completed.';
                    }
                    break;
                default:        //商业项目
                    if ($project->prj_progress > 1 && $project->prj_progress < 3 && time() > $this->checkTime($project)) {      //项目逾期
                        $result = 'The current project is overdue, which means the whole project could be closed. It is suggested to negotiate with each other first.';
                    }
                    if ($project->prj_progress == 3 && time() <= $this->checkTime($project)) {    //项目结束
                        $result = 'The current project has been passed, see project result in the result page.';
                    }
                    break;
            }
            return $this->jsonOk('ok', ['models' => $models, 'functions' => $permission, 'result' => $result, 'project_status' => $status]);
        }
    }
    /**
     * 获取项目结果
     * @return mixed
     */
    public function getResult()
    {
        $project_id = Input::get('id');
        $permission = $this->userFeaturesPermission($project_id,'resultDownload');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else{
            $project = Project::find($project_id);
            $type = $project->prj_type?false:true;
            $result = (object)[];
            switch ($project->prj_progress)
            {
                case 1:
                case 2:
                    if(time() > $this->checkTime($project) && $project->prj_progress==2){
                        $status =3; //逾期
                    }else{
                        $status = 1; //未结束
                    }
                    $file =  '';
                    break;
                case 3:
                    $status =2; //未评分
                    $builds = BuildDaily::select('bd_attachment','bd_id','bd_3d')
                            ->with(['images','attach'])
                            ->where('bd_pid',$project_id)
                            ->get();
                    foreach ($builds as $build) {
                        $file['model'][] = $build->bd_3d?$this->zipinfo($build->bd_attachment):'';
                        $file['concept'][] = $build->bd_3d?'':$this->getFiles($build->images);
                        $file['attach'][] = $build->bd_3d?'':$this->getFiles($build->attach);
                    }
                    foreach ($file['model'] as $key=>$value) {
                        if(empty($value)){
                            unset($file['model'][$key]);
                        }
                    }
                    foreach ($file['concept'] as $key=>$value) {
                        if(empty($value)){
                            unset($file['concept'][$key]);
                        }
                    }
                    foreach ($file['attach'] as $key=>$value) {
                        if(empty($value)){
                            unset($file['attach'][$key]);
                        }
                    }
                    $file['model'] = collect($file['model'])->values();
                    $file['concept'] = collect($file['concept'])->collapse()->values();
                    $file['attach'] = collect($file['attach'])->collapse()->values();
                    $result->rate = $project->prj_success == 1?'pass':'fail';
                    $result->time = 0;
                    $result->quality = 0;
                    $result->commucation = 0;
                    $result->comment = '';
                    break;
                default:
                    $status =4; //显示结果
                    $rate = ProjectRate::where('r_pid',$project->prj_id)->first();
                    $result = (object)[];
                    $result->rate = $project->prj_success == 1?'pass':'fail';
                    $result->time = $rate->r_time;
                    $result->quality = $rate->r_quality;
                    $result->commucation = $rate->r_other;
                    $result->comment = $rate->r_comment;
                    $builds = BuildDaily::select('bd_attachment','bd_id','bd_3d')
                        ->with(['images','attach'])
                        ->where('bd_pid',$project_id)
                        ->get();
                    foreach ($builds as $build) {
                        $file['model'][] = $build->bd_3d?$this->zipinfo($build->bd_attachment):'';
                        $file['concept'][] = $build->bd_3d?'':$this->getFiles($build->images);
                        $file['attach'][] = $build->bd_3d?'':$this->getFiles($build->attach);
                    }
                    foreach ($file['model'] as $key=>$value) {
                        if(empty($value)){
                            unset($file['model'][$key]);
                        }
                    }
                    foreach ($file['concept'] as $key=>$value) {
                        if(empty($value)){
                            unset($file['concept'][$key]);
                        }
                    }
                    foreach ($file['attach'] as $key=>$value) {
                        if(empty($value)){
                            unset($file['attach'][$key]);
                        }
                    }
                    $file['model'] = collect($file['model'])->values();
                    $file['concept'] = collect($file['concept'])->collapse()->values();
                    $file['attach'] = collect($file['attach'])->collapse()->values();
                    break;
            }
        }
        return $this->jsonOk('ok',['status'=>$status,'functions'=>$permission,'result'=>$result,'file'=>$file,'type'=>$type]);
    }
    /**
     * 更改项目更新标记
     * @return mixed
     */
    public function projectUpdateStatus()
    {
        $id = Input::get('id');
        $project = Project::find($id);
        if (!$project) {
            return $this->jsonErr('error', 'no this project');
        } else {
            if ($this->_user->user_id == $project->prj_uid) {
                $project->prj_update_jia = 0;
            } else {
                if ($project->prj_modeler && $this->_user->user_id == $project->prj_modeler) {
                    $project->prj_update_yi = 0;
                }
            }
            $project->save();
            return $this->jsonOk('ok', 'update status successfully');
        }
    }
    /**
     * 项目跳转步骤
     * @param $project
     * @param $code
     * @return int
     */
    private  function ProjectRedirect($project,$code)
    {
        switch ($project->prj_progress)
        {
            case 1:                   //报价阶段
                switch ($code)
                {
                    case 1:             //甲方
                        $apply = PrjApply::where('prj_id',$project->prj_id)->get();
                        if(!$apply){
                            $redirect = 0;              //邀请
                        }else{
                            if(!$project->prj_desc){
                                $redirect = 1;          //requirement
                            }else{
                                $redirect = 2;          //选人
                            }
                        }
                        break;
                    default:             //乙方
                        $redirect = 3;
                        break;
                }
                break;
            case 2:                   //制作阶段
                $redirect = 4;
                break;
            case 3:                   //结束阶段
            default:
                $redirect = 5;
                break;
        }
        return $redirect;
    }

    /**
     * 模型状态
     * @param $role
     * @param $convert
     * @param $code
     * @return int
     */
    private function getModelStatus($role,$convert,$code)
    {
        switch ($convert)
        {
            case 1:
                $status = 1;     //转换中
                break;
            case 2:
                $status = 2;     //转换成功
                break;
            case 3:
               $status = 3;     //转换失败
                break;
            case 4:
                if($code==0){
                   $status = 0;     //未操作
                }elseif($code==1){
                    $status = 4;      //发布，甲方同意
                }else{
                    $status = 5;      //发布，甲方拒绝
                }
                break;
            default:
               $status = 0;     //未操作
                break;
        }
        if($role==1 && $status<4) {
            $status = 0;
        }
        return $status;
    }

    /**
     * 校检状态
     * @param $project
     * @return mixed
     */
    private function checkTime($project)
    {
        $pay = DB::table('build_pays')->select('pay_time')->where(['has_pay'=>1,'pid'=>$project->prj_id])->first();
        if($pay)
        {
            $start = $pay->pay_time;
        }else{
            $start = 0;
        }
        $apply = PrjApply::where('prj_id',$project->prj_id)->where('user_id',$project->prj_modeler)->first();
        if($apply){
            $end   = $start+$apply->apply_cost_time*3600*24;
        }else{
         $end = $start+time();
        }
        return $end;
    }
    /**获取feedback页面
     * @return mixed
     */
    public function getFeedBackInfo()
    {
        $id = Input::get('id');
        $permission = $this->userFeaturesPermission($id,'feedBack');
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else{
            $project = Project::find($id);
            $models = BuildDaily::select('bd_id','bd_name','bd_pub','status')->where('bd_pid',$id)->get();
            $projectUser = ProjectUser::where(['project_id'=>$id,'user_id'=>$this->_user->user_id])->first();
            $advance = (object)[];
            switch ($project->prj_type) {
                case 1:
                    $advance->source = [];
                    break;
                default:
                    if($projectUser->user_role==1){
                        $advance->source = [];
                    }else{
                        switch ($this->lang)
                        {
                            case 'zh':
                                $source = StaticConf::$feedback_outsourcing_zh;
                                break;
                            default:
                                $source = StaticConf::$feedback_outsourcing;
                                break;
                        }
                        $advance->source = $source;
                    }
                    break;
            }
            $advance->models = $models->prepend(['bd_id'=>0,'bd_name'=>'All']);
            return $this->jsonOk('ok',['advance'=>$advance,'functions'=>$permission]);
        }
    }

    /**
     * 获取feedback列表
     * @return mixed
     */
    public function getFeedBack()
    {
        $id = Input::get('id');
        $source = Input::get('source',null);
        $model = Input::get('model_id',null);
        $keywords = Input::get('keywords',null);
        $page = Input::get('page', 1);
        $pageSize = Input::get('pagesize', 10);
        $offset = ($page - 1) * $pageSize;
        $select = BuildMark::select('build_marks.id','build_marks.uid','build_marks.status','build_marks.title','build_marks.bid','build_marks.mark','project_users.user_role')
            ->with('marker','build')
           ->where('pid',$id)
            ->leftjoin('project_users',function ($join)use($source) {
                $join->on('build_marks.pid', '=', 'project_users.project_id')
                    ->on('build_marks.uid', '=', 'project_users.user_id');
            });
        $projectUser = ProjectUser::where(['project_id'=>$id,'user_id'=>$this->_user->user_id])->first();
        if($projectUser->user_role==1)  //商业项目甲方只能看到甲方角色组的标注
        {
            $select = $select->where('project_users.user_role','=',1);
        }
        if($source){
            $select = $select->where('project_users.user_role','=',$source);
        }
        if($keywords){
            $select = $select->where('title','like','%'.$keywords.'%');
        }
        if($model){
            $select = $select->where('bid',$model);
        }
        $remark = $select->take($pageSize)
            ->skip($offset)
            ->orderBy('build_marks.id','DESC')
            ->get();
        $remark  = $remark->filter(function ($items){
            return $items->build->is_3d!=1;
        })->map(function ($item){
            $item->number = 'AA-'.$item->id;
            $item->name = $item->build->bd_name;
            $item->author = (object)[];
            $item->author->avatar = $this->getAvatar($item->marker->user_icon);
            $item->author->user_type = $item->marker->user_type;
            switch ($item->status)
            {
                case 1:
                    $item->status = 'processing';
                    break;
                case 2:
                    $item->status = 'completed';
                    break;
                case 3:
                    $item->status = 'abort';
                    break;
                case 4:
                    $item->status = 're-Open';
                    break;
                default:
                    $item->status = 'pending';
                    break;
            }

            if($item->user_role==1){
                $item->mark = $this->lang =='zh'?StaticConf::$audition_zh[$item->mark]['name']:StaticConf::$audition[$item->mark]['name'];
            }else{
                $item->mark = '';
            }
            unset($item->build,$item->marker,$item->uid,$item->bid,$item->user_role);
            return $item;
        });
        return $this->jsonOk('ok',['remark'=>$remark]);
    }

    /**
     * feedback详情
     * @return mixed
     */
    public function feedbackDetail()
    {
        $mark_id = Input::get('id');
        $mark  = BuildMark::with('marker','markResponse','build')->find($mark_id);
        $permission = $this->userFeaturesPermission($mark->pid,'feedBack');
        $project = Project::find($mark->pid);
        $build = BuildDaily::find($mark->bid);
        if(is_string($permission))
        {
            return $this->jsonErr($permission);
        }else{
            $mark->name = $mark->build->bd_name;
            $mark->annotation = $this->getOssPath($mark->img,-1);
            //$mark->reference = $mark->attachment?$this->getOssPath($mark->attachment):env('APP_URL').'/images/icon_feedback.png';
            $mark->reference = $mark->attachment?$this->getOssPath($mark->attachment,1000):'';
            $mark->update = $mark->markResponse?$this->getOssPath($mark->markResponse->attachment,1000):'';
            $mark->author = (object)[];
            $mark->author->avatar = $this->getAvatar($mark->marker->user_icon);
            $mark->author->user_type = $mark->marker->user_type;
            switch ($this->lang)
            {
                case 'zh':
                    $status = StaticConf::$status_zh;
                    $audition = StaticConf::$audition_zh;
                    break;
                default:
                    $status = StaticConf::$status;
                    $audition = StaticConf::$audition;
                    break;
            }
        }
        foreach ($permission as $key=>$value)
        {
            if($key=='comment'){
                if($project->prj_progress>2){
                    $value['operate']=0;
                }
                if($build->bd_pub==4 && $build->status==1){
                    $value['operate'] =0;
                }
            }
        }
        $comments = MarkComment::where('mid',$mark_id)->with('user')->orderBy('create_time','DESC')->get();
        $comments = $comments->map(function ($item){
            $item->user_name = $this->getName($item->user);
            $item->user_type = $item->user->user_type;
            $item->user_avatar = $this->getAvatar($item->user->user_icon,100);
            unset($item->user,$item->user_id,$item->mid,$item->id);
            return $item;
        });
        unset($mark->uid,$mark->status_jia,$mark->number,$mark->status_yi,$mark->create_time,$mark->update_time,$mark->marker,$mark->img,$mark->attachment,$mark->markResponse,$mark->pid,$mark->bid,$mark->build);
        return $this->jsonOk('ok',['detail'=>$mark,'functions'=>$permission,'status'=>$status,'audition'=>$audition,'comments'=>$comments]);
    }

    /**
     * 更新feedback状态
     * @param Request $request
     * @return mixed
     */
    public function updateStatus(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');
        $result = $request->input('result');
        $buildMark = BuildMark::find($id);
        switch ($type)
        {
            case 'status':
                $buildMark->status = $result;
                break;
            case 'audition':
                $buildMark->mark = $result;
                break;
        }
        $buildMark->save();
        return $this->jsonOk('ok','update successfully');
    }

//    /**
//     * 列出标注所有留言
//     * @return mixed
//     */
//    public function listComments()
//    {
//        $mid = Input::get('mid');
//        $comments = MarkComment::where('mid',$mid)->with('user')->orderBy('create_time','DESC')->get();
//        $comments = $comments->map(function ($item){
//            $item->user_name = $this->getName($item->user);
//            $item->user_type = $item->user->user_type;
//            $item->user_avatar = $this->getAvatar($item->user->user_icon,100);
//            unset($item->user,$item->user_id,$item->mid,$item->id);
//            return $item;
//        });
//        return $this->jsonOk('ok',$comments);
//    }

    /**
     * 发送标注留言
     * @param Request $request
     * @return mixed
     */
    public function sendComment(Request $request)
    {
        $mid = $request->input('mid');
        $user_id = $this->_user->user_id;
        $content = $request->input('content');
        $rules = [
            'mid'=>'required',
            'content'=>'required'
        ];
        $validate = $this->postValidate($request->all(),$rules);
        if($validate){
            return $this->jsonErr('error',$validate);
        }else{
            $comment = new MarkComment();
            $comment->mid = $mid;
            $comment->user_id = $user_id;
            $comment->content = $content;
            $comment->create_time = time();
            $comment->save();
            return $this->jsonOk('ok','send ok');
        }
    }

    /**
     * 判断是否有服务权限
     * @return mixed
     */
    public function userServices()
    {
        if(!$this->_user){
            return $this->jsonErr('no permission');
        }else{
            $userServices = UserService::where('user_id',$this->_user->user_id)->first();
            if(!$userServices){
                return $this->jsonErr('no permission');
            }else{
                $services[]['name']=StaticConf::services[$userServices->service_type];
                return $this->jsonOk('ok',$services);
            }
        }
    }

    /**
     * 判断key是否正确
     * @param Request $request
     * @return mixed
     */
    public function validateKey(Request $request)
    {
        $key = $request->input('key');
        $rules = [
            'key'=>'required',
        ];
        $validate = $this->postValidate($request->all(),$rules);
        if($validate){
            return $this->jsonErr($validate);
        }else{
            if($key=='ilovenovaby'){
                $service = new UserService();
                $service->user_id = $this->_user->user_id;
                $service->service_type = 1;
                $service->start_time = time();
                $service->end_time = time();
                $service->save();
                return $this->jsonOk('ok','key is right');
            }else{
                return $this->jsonErr('The key is not right');
            }
        }

    }
}
