<?php

namespace App\Http\Controllers\Api;


use App\libs\Tools;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Model\Comment;
use App\Model\User;
use Illuminate\Support\Facades\Input;


class CommentApiController extends BaseApiController
{
    public function commentlists(){
        $id = Input::get('model',0);
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',10);
        $tot = Comment::where(['comment_eid'=>$id,'comment_pid'=>0])->count();
        $offset = ($page-1)*$page_size;
        //$comments = Comment::where(['comment_id'=>$id])->first();
        $comments = Comment::where(['comment_eid'=>$id,'comment_pid'=>0])->select('comment_id','comment_uid','comment_content','comment_create_time','comment_pid','reply_to')
        ->orderBy('comment_id','DESC')->skip($offset)->take($page_size)->get();

        foreach($comments AS $k=>$v){
            $user = User::where(['user_id'=>$v->comment_uid])->select('user_name','user_lastname','user_type','user_icon','company_name')->first();
            $comments[$k]->user_name=$user->user_type==4?$user->company_name:$user->name.' '.$user->user_lastname;
            $comments[$k]->user_avatar = $this->getAvatar($user->user_icon);
            $comments[$k]->author = $this->getAuthorAndWorks1($v->comment_uid);

            $t_carbon=new Carbon($v->comment_create_time);
            $t_int=$t_carbon->timestamp;
            $comments[$k]->comment_create_time=$t_int;
            $comments[$k]->time = time();
            $comments[$k]->time1 = date('Y-m-d H:i:s',time());
            $comments[$k]->time2 = $v->comment_create_time;
            $reply=[];
            //$_pid = $v->comment_pid;
            unset($comments[$k]->comment_eid);
            $sub_comments =  Comment::where(['comment_pid'=>$v->comment_id])->select('comment_id','comment_uid','comment_content','comment_create_time','comment_pid','reply_to')
                ->orderBy('comment_id','DESC')->get();

            foreach($sub_comments AS $k1=>$v1){
                $_user_reply = User::where(['user_id'=>$v1->comment_uid])->select('user_name','user_lastname','user_type','user_icon')->first();

                $sub_comments[$k1]->user_name=$_user_reply->name.' '.$_user_reply->user_lastname;
                if($v1->reply_to){
                    $_user_reply_to = User::where(['user_id'=>$v1->reply_to])->select('user_name','user_lastname','user_type','user_icon')->first();
                    //dd($_user_reply_to);
                    $sub_comments[$k1]->user_reply_to_name=$_user_reply_to->name.' '.$_user_reply_to->user_lastname;

                }else{
                    $sub_comments[$k1]->user_reply_to_name='';
                }
            }
            $comments[$k]->sub_comments=$sub_comments;

        }


        if(count($comments)>0)
        {
            return $this->jsonOk('ok',['comments'=>$comments,'tot'=>$tot,'totalpage'=>ceil($tot/$page_size),'currpage'=>$page]);
        }else{
            return $this->jsonErr("No More Data");
        }

    }
    public function replyDelete(Request $req){
        $cid = $req->get('cid',0);
        if($cid){
            $comment = Comment::find($cid);
            if($comment && $comment->comment_uid==$this->_user->user_id && $comment->comment_pid!=0){
                $comment->delete();
                return $this->jsonOk("ok",['result'=>'delete successfully']);
            }else{
                return $this->jsonErr("delete error");
            }

        }
        return $this->jsonErr("delete error");
    }
}
