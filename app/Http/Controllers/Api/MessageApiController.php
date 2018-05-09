<?php

namespace App\Http\Controllers\Api;

use App\libs\Tools;
use App\Model\Notify;
use App\Model\Project;
use App\Model\TeamInvite;
use App\Model\UserTeam;
use App\Model\Work;
use Illuminate\Http\Request;
use App\Model\User;
use Illuminate\Support\Facades\Input;
use App\Model\Message;
use DB;

class MessageApiController extends BaseApiController
{
    public function lists(){
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',1000);
        $type = Input::get('type',"all");
        $status = Input::get('status','');
        $adn=" 1=1";
        if($type=='system'){
            $adn="  messages.msg_action=3 ";
        }
        if($status!=''){
            $adn.="  AND messages.msg_read= ".(1-$status);
        }
        $offset = ($page-1)*$page_size;
        $tot = Message::where('msg_delete',0)->where('msg_to_uid',$this->_user->user_id)->whereRAW($adn)->count();
        $unread_tot = Message::where('msg_delete',0)->where('msg_to_uid',$this->_user->user_id)->where('msg_read',0)->count();
        $messages = Message::where('msg_to_uid',$this->_user->user_id)
            ->where('msg_delete',0)
            ->whereRAW($adn)
            ->leftJoin('user','messages.msg_from_uid','=','user.user_id')
            ->select('messages.*','user.user_name','user.user_lastname','user.user_icon','user.user_type','user.company_name')
            ->orderBy('msg_id','DESC')
            ->take($page_size)
            ->skip($offset)
            ->get();
        foreach($messages AS $k=>$v){
                $messages[$k]->msg_time = $v->msg_time;
                $messages[$k]->time = time();
                $messages[$k]->status = 0;
            if($v->msg_action!=3 && $v->msg_action!=6){
                $avatar = $this->getAvatar($v->user_icon,'500');
                $messages[$k]->from = [
                    'uid'=>$v->msg_from_uid,
                    'avatar'=>$avatar,
                    'username'=>$this->getName($v),
                    'user_type'=>$v->user_type
                ];
            }else{
                $messages[$k]->from='';
            }
            $messages[$k]->msg_read=1-$v->msg_read;
            switch ($v->msg_action){
                case 1:
                    $messages[$k]->action = 'FOLLOW';
                    $messages[$k]->caction = 'B';
                    switch ($this->lang) {
                        case 'zh':
                            $messages[$k]->msg = '开始关注了您';
                            break;
                        default:
                            $messages[$k]->msg = 'started following you';
                            break;
                    }
                    $messages[$k]->msg_rid = $v->msg_from_uid;

                    break;
                case 2:
                    $messages[$k]->action = 'COMMENT';
                    $messages[$k]->caction = 'A';
                    $model_name = Work::where('work_id',$v->msg_rid)->select('work_title')->first();
                    switch ($this->lang) {
                        case 'zh':
                            $messages[$k]->msg = '评论了您的作品 '.$model_name->work_title;
                            break;
                        default:
                            $messages[$k]->msg = 'commented on your work '.$model_name->work_title;
                            break;
                    }
                    break;
                case 3:
                    $messages[$k]->action = 'SYSTEM';
                    $messages[$k]->caction = 'C';
                    switch ($this->lang) {
                        case 'zh':
                            if($v->msg_remark){
                                $messages[$k]->msg=$v->msg_remark." ".$v->notify->content_cn;
                            }else{
                                $messages[$k]->msg=$v->notify->content_cn;
                            }
                            break;
                        default:
                            if($v->msg_remark){
                                $messages[$k]->msg=$v->msg_remark." ".$v->notify->content;
                            }else{
                                $messages[$k]->msg=$v->notify->content;
                            }
                            break;
                    }
                    break;
                case 4:
                    $messages[$k]->action = 'LIKE';
                    $messages[$k]->caction = 'A';
                    $model_name = Work::where('work_id',$v->msg_rid)->select('work_title')->first();
                    switch ($this->lang) {
                        case 'zh':
                            $messages[$k]->msg = '喜欢了您的作品 '.$model_name->work_title;
                            break;
                        default:
                            $messages[$k]->msg = 'liked  your work '.$model_name->work_title;
                            break;
                    }
                    break;
                case 5:
                    $messages[$k]->action = 'INVITE';
                    $messages[$k]->caction = 'D';
                    $prj = Project::where('prj_id',$v->msg_rid)->select('prj_name')->first();
                    switch ($this->lang) {
                        case 'zh':
                            $messages[$k]->msg = '邀请您参与项目 '.$prj->prj_name;
                            break;
                        default:
                            $messages[$k]->msg = 'Invite  you to bid '.$prj->prj_name;
                            break;
                    }
                    break;
                case 6:
                    $messages[$k]->action = 'TEAM';
                    $messages[$k]->caction = 'E';
                    $user_name = $this->getName($v);
                    switch ($this->lang) {
                        case 'zh':
                            $messages[$k]->msg = $user_name.' 邀请您加入团队 '.$v->msg_remark;
                            $messages[$k]->status = $v->msg_status;
                            break;
                        default:
                            $messages[$k]->msg = $user_name.' invited  you to join  '.$v->msg_remark;
                            $messages[$k]->status = $v->msg_status;
                            break;
                    }
                    break;
                case 7:
                    $messages[$k]->action = 'Chat';
                    $messages[$k]->caction = 'F';
                    $user_name = $this->getName($v);
                    $prj = Project::where('prj_id',$v->msg_rid)->select('prj_name')->first();
                    switch ($this->lang) {
                        case 'zh':
                            $messages[$k]->msg = $user_name.'在项目:'.$prj->prj_name.'给您发送了一条消息'.$v->msg_remark;
                            break;
                        default:
                            $messages[$k]->msg = $user_name.'sent you a message:'.$v->msg_remark.'in project:'.$prj->prj_name;
                            break;
                    }
                    break;
                default:
                    break;
            }
            unset($messages[$k]->msg_from_uid);
            unset($messages[$k]->msg_to_uid);
            unset($messages[$k]->msg_action);
            unset($messages[$k]->msg_delete);
            unset($messages[$k]->user_icon);
            unset($messages[$k]->user_name);
            unset($messages[$k]->user_lastname);
            unset($messages[$k]->company_name);
            unset($messages[$k]->notify);
            unset($messages[$k]->msg_remark);
        }
        if(count($messages)){
            return $this->jsonOk('ok',['messages'=>$messages, 'tot'=>$tot,'newmsg'=>$unread_tot,'pages'=>ceil($tot/$page_size)]);
        }else{
            return $this->jsonErr("No More Message");
        }


    }
    public function delete(Request $req){
        $ids = $req->get('ids',[]);
        if(!is_array($ids)){
            return $this->jsonErr("delete error");
        }
        if(count($ids)>0 && $ids[0]){
            foreach($ids AS $k=>$v){
                $res = DB::table('messages')->where(['msg_id'=>$v,'msg_to_uid'=>$this->_user->user_id])->update(['msg_delete'=>1]);
            }
        }else{
            $res = DB::table('messages')->where(['msg_to_uid'=>$this->_user->user_id])->update(['msg_delete'=>1]);

        }
        if($res){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("delete error");
        }
    }
    public function markread(Request $req){
        $ids = $req->get('ids',[]);
        if(!is_array($ids)){
            return $this->jsonErr(" error");
        }
        if(count($ids)>0 && $ids[0]){
            foreach($ids AS $k=>$v){
                $res = DB::table('messages')->where(['msg_id'=>$v,'msg_to_uid'=>$this->_user->user_id])->update(['msg_read'=>1]);
            }
        }else{
            $res = DB::table('messages')->where(['msg_to_uid'=>$this->_user->user_id])->update(['msg_read'=>1]);
        }
        if($res){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("marked error");
        }
    }
    private function delMsg($id){

    }
}
