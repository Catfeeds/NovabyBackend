<?php

namespace App\Http\Controllers\Api;

use App\Events\NotifyEvent;
use App\libs\StaticConf;
use App\Model\Message;
use App\Model\Notify;
use App\Model\ProjectUser;
use App\Model\TeamInvite;
use App\Model\TeamRelation;
use App\Model\User;
use App\Model\UserTeam;
use Hamcrest\Core\IsNot;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class TeamApiController extends BaseApiController
{
    /**
     * Team列表
     * @return mixed
     */
    public function listTeam()
    {
        $id = Input::get('id');
        $relation = TeamRelation::where('user_id',$id)->first();
        if($relation){
            $team = UserTeam::with('users')->find($relation->team_id);
            $users = $team->users->map(function ($item){
                $user = (object)[];
                $user->user_name = $this->getName($item);
                $user->user_avatar = $this->getAvatar($item->user_icon);
                $user->user_type = $item->user_type;
                $user->user_id = $item->user_id;
                unset($item);
                return $user;
            });
            $data['team_name'] = $team->name;
            $data['team_id'] = $team->id;
            $data['team_members'] = $users;
            if($this->_user){
                $data['is_me'] = $team->creator_id == $this->_user->user_id?1:0;
            }else{
                $data['is_me'] = 0;
            }
            return $this->jsonOk('ok',$data);
        }else{
            return $this->jsonErr('no team');
        }
    }

    /**
     * 创建团队
     * @param Request $request
     */
    public function createTeam(Request $request)
    {
        $name = $request->input('name');
        if(UserTeam::where('creator_id',$this->_user->user_id)->first()){
            return $this->jsonErr('you already have a team');
        }
        $rule = [
            'name'=>'required|unique:user_teams,name'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        $team = new UserTeam();
        $team->name = $name;
        $team->icon = 0;
        $team->creator_id = $this->_user->user_id;
        $team->created_at = time();
        $team->save();
        $relation = new TeamRelation();
        $relation->team_id = $team->id;
        $relation->user_id = $this->_user->user_id;
        if($relation->save()){
            return $this->jsonOk('ok','create team successfully');
        }else{
            return $this->jsonErr('error');
        }
    }

    /**
     * 搜索用户
     * @param Request $request
     */
    public function searchUser(Request $request)
    {
        $email = $request->input('email');
        $project_id = $request->input('project_id',0);
        $user = User::select('user_name','user_lastname','company_name','user_type','user_id','user_email','user_icon')
            ->where('user_email',$email)
            ->first();
        if($user){
            $user->user_name = $this->getName($user);
            $user->user_avatar = $this->getAvatar($user->user_icon);
            if($project_id){
                $project = ProjectUser::where(['user_id'=>$user->user_id,'project_id'=>$project_id])->first();
                $user->belong_project = $project?1:0;
            }else{
                $team = TeamRelation::where('user_id',$user->user_id)->first();
                $user->user_team = $team?1:0;
            }
            unset($user->user_icon,$user->user_lastname,$user->company_name);
            return $this->jsonOk('ok',['user'=>$user]);
        }else{
            return $this->jsonErr('not find user');
        }
    }

    /**
     * 邀请成员加入
     * @param Request $request
     */
    public function inviteUser(Request $request)
    {
        $ids = $request->input('ids');
        $team_id = $request->input('team_id');
        $rule = [
            'ids'=>'required',
            'team_id'=>'required'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        $team = UserTeam::find($team_id);
        if($team){
            $this->invite($ids,$team);
            //$inviter_name = $this->getName($this->_user);
            //$this->inviteMail($ids,$inviter_name,$team);
            return $this->jsonOk('ok','send successfully');
        }else{
            return $this->jsonErr('no this team');
        }
    }

    /**
     * 保存团队信息
     * @param Request $request
     */
    public function saveTeam(Request $request)
    {
        $name = $request->input('name');
        $id = $request->input('id');
        $rule = [
            'name'=>'required'
        ];
        $result = $this->postValidate($request->all(),$rule);
        if($result) {
            return $this->jsonErr($result);
        }
        $search = UserTeam::where(['name'=>$name,'id'=>!$id])->first();
        if($search){
            return $this->jsonErr('team name has been used');
        }else{
            $team = UserTeam::find($id);
            if($team){
                $team->name = $name;
                $team->save();
                return $this->jsonOk('ok','save team successfully');
            }else{
                return $this->jsonErr('no find this team');
            }
        }
    }
    /**
     *同意加入团队
     */
    public function joinTeam()
    {
        $invite_id = Input::get('id');
        $invite = TeamInvite::with('message')->find($invite_id);
        if(!$invite){
            return $this->jsonErr('this team have no invite you');
        }
        $message = $invite->message;
        $message->msg_read = 1;
        $message->msg_status = 1;
        $message->msg_rid = 0;
        $message->save();
        $team = UserTeam::find($invite->team_id);
        if($team){
            $search = TeamRelation::where('user_id',$this->_user->user_id)->first();
            if($search){
                return $this->jsonErr('you have already in a team');
            }else{
                $relation = new TeamRelation();
                $relation->team_id = $team->id;
                $relation->user_id = $this->_user->user_id;
                $name = $this->getName($this->_user);
                $relation->save();
                $invite->delete();
                \Event::fire(new NotifyEvent(14,$team->creator_id,$name));
                return $this->jsonOk('ok','join this team');
            }
        }else{
            return $this->jsonErr('not find this team');
        }

    }

    /**
     *拒绝加入团队
     */
    public function rejectTeam()
    {
        $invite_id = Input::get('id');
        $invite = TeamInvite::with('message')->find($invite_id);
        if(!$invite){
            return $this->jsonErr('this team have no invite you');
        }
        $message = $invite->message;
        $message->msg_read = 1;
        $message->msg_status = 2;
        $message->msg_rid = 0;
        $message->save();
        $team = UserTeam::find($invite->team_id);
        if($team){
            $invite->delete();
            $name = $this->getName($this->_user);
            \Event::fire(new NotifyEvent(15,$team->creator_id,$name));
            return $this->jsonOk('ok','refuse this team');
        }else{
            return $this->jsonErr('not find this team');
        }
    }

    /**
     * 删除成员
     */
    public function deleteMember()
    {
        $id = Input::get('id');
        $user_id = Input::get('user_id');
        $team = UserTeam::find($id);
        if(!$team){
            return $this->jsonErr('no this team');
        }
        if($this->_user->user_id!=$team->creator_id){
            return $this->jsonErr('no permission');
        }
        if($this->_user->user_id==$user_id){
            return $this->jsonErr('can\'t delete yourself');
        }
        $result = TeamRelation::where(['team_id'=>$id,'user_id'=>$user_id])->delete();
        if($result){
            return $this->jsonOk('ok','delete member successfully');
        }else{
            return $this->jsonErr('delete member error');
        }

    }
    /**
     * @param $id
     */
    public function quitTeam($id)
    {

    }
    private function inviteMail($ids,$inviter,$team)
    {
        if (is_array($ids))
        {
            $users = User::select('user_name','user_email')->whereIn('user_id',$ids)->get();
            foreach ($users as $user)
            {
                Mail::later(10,'emailtpl.team_invite', ['user' =>$user->user_name,'name'=>$team->name,'inviter'=>$inviter], function ($message)use($user){
                    $message->to($user->user->user_email)->subject('Someone invite you join in a team!');
                });
            }
        }
    }
    private function invite($ids,$team)
    {
        $ids = collect($ids)->unique();
        foreach($ids as $user_id)
        {
            $result = TeamInvite::where(['team_id'=>$team->id,'user_id'=>$user_id])->first();
            if(!$result){
                $this->sendInvite($team,$user_id,$this->_user->user_id);
            }else{
                switch ($result->status){
                    case 1:         //同意不发送
                        break;
                    case 2:         //拒绝重新发送
                        $this->sendInvite($team,$user_id,$this->_user->user_id);
                        break;
                    default:        //未操作
                        $message = Message::where(['msg_rid'=>$result->id,'msg_to_uid'=>$user_id])->first();
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
     * @param $team
     * @param $user_id
     * @param $inviter_id
     */
    private function sendInvite($team,$user_id,$inviter_id)
    {
        $invite = new TeamInvite();
        $invite->team_id = $team->id;
        $invite->user_id = $user_id;
        $invite->inviter_id = $inviter_id;
        $invite->status = 0;
        $invite->save();
        $message = new Message();
        $message->msg_from_uid = $inviter_id;
        $message->msg_to_uid = $user_id;
        $message->msg_action = 6;
        $message->msg_rid = $invite->id;
        $message->msg_remark = $team->name;
        $message->msg_time = time();
        $message->save();
    }
}
