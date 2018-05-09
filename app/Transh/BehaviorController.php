<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Session;
use DB;
use Mail;

class BehaviorController extends Controller
{
    //
    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    public function sendmsg(Request $request){
        $content=htmlspecialchars($request->get('content'));
        $uid=$request->get('uid');
        $rid=DB::table('chat')->insertGetId(
            [
                'from_uid'=>$this->info->user_id,
                'to_uid'=>$uid,
                'content'=>$content,
                'sender'=>$this->info->user_id,

            ]
        );
        $user=DB::table('user')->select('user_icon')->where('user_id',$this->info->user_id)->first();
        $icon=$user->user_icon?$this->getOssPath($user->user_icon)->oss_path:'';
        $ret_data=['content'=>$content,'user_id'=>$this->info->user_id,'user_icon'=>$icon];
        return response()->json(['code'=>200,'msg'=>'ok','data'=>$ret_data]);

    }
    public function star(Request $request){
        $id=$request->get('id');
        $star=$request->get('stars');
        if($id > 0 && $star > 0){
            $order=DB::table('orders')->select('order_eid')->where(['order_id'=>$id])->first();
            if($order){
                $iid=DB::table('rates')->insertGetId([
                    'uid'=>$this->info->user_id,
                    'eid'=>$order->order_eid,
                    'oid'=>$id,
                    'stars'=>$star,
                ]);
                if($iid>0){
                    return response()->json(['code'=>200,'msg'=>'ok','result'=>1]);
                }
            }
        }
    }
    public function markmsg(){

        $id=Input::get('id',0);
        if($id<1) exit;

        $res=DB::table('chat')->where(['to_uid'=>$this->info->user_id,'from_uid'=>$id])->update(['sender_del'=>1]);
        echo $res;

    }
    public function like( Request $request)
    {

        $eid=$request->get('id');
        $touid=DB::table('element')->select('user_id')->where('element_id',$this->info->user_id)->first();
        $ck=DB::table('likes')->where(['like_eid'=>$eid,'like_uid'=>$this->info->user_id])->first();
        if(!$ck){

            $iid=DB::table('likes')->insert(
                [
                    'like_eid'=>$eid,
                    'like_uid'=>$this->info->user_id,
                    'liked'=>1,
                    'like_to_uid'=>$touid->user_id,
                ]
            );

            if($iid){
                DB::table('element')->where('element_id',$eid)->increment('element_likenum');

                return response()->json(['code'=>200,'msg'=>'ok','result'=>1]);
            }
        }else{
            if($ck->liked){
                DB::table('likes')->where('id',$ck->id)->update(['liked'=>0]);
                DB::table('element')->where('element_id',$eid)->decrement('element_likenum');
                return response()->json(['code'=>200,'msg'=>'ok','result'=>0]);

            }else{
                DB::table('likes')->where('id',$ck->id)->update(['liked'=>1]);
                DB::table('element')->where('element_id',$eid)->increment('element_likenum');
                return response()->json(['code'=>200,'msg'=>'ok','result'=>1]);
            }
        }
        // dd($request->all());
        //return view('user.like');
    }
    public function followuser(Request $request){
        $uid=$request->get('id');
        $ck=DB::table('following')->where(['from_uid'=>$this->info->user_id,'to_uid'=>$uid])->first();
        if(!$ck){

            $iid=DB::table('following')->insert(
                [
                    'from_uid'=>$this->info->user_id,
                    'to_uid'=>$uid,
                    'followed'=>1,
                ]
            );

            if($iid){
                //DB::table('element')->where('element_id',$eid)->increment('element_likenum');

                return response()->json(['code'=>200,'msg'=>'ok','result'=>1]);
            }
        }else{
            if($ck->followed){
                DB::table('following')->where('id',$ck->id)->update(['followed'=>0]);
                //DB::table('element')->where('element_id',$eid)->decrement('element_likenum');
                return response()->json(['code'=>200,'msg'=>'ok','result'=>0]);

            }else{
                DB::table('following')->where('id',$ck->id)->update(['followed'=>1]);
                // DB::table('element')->where('element_id',$eid)->increment('element_likenum');
                return response()->json(['code'=>200,'msg'=>'ok','result'=>1]);
            }
        }




        DB::table('following')->insert(
            [
                'from_uid'=>$this->info->user_id,
                'touid'=>$this->info->id,
            ]
        );

    }
    public function updateNotice(){
        $cate=Input::get('cate',null);
        if($cate!==null){
            if($cate==1 || $cate==3){
                $data=['user_read_event_time'=>time()];
                DB::table('user')->where('user_id',$this->info->user_id)->update($data);
            }
            if($cate==0){
                $data=['user_read_news_time'=>time()];
                $res=DB::table('user')->where(['user_id'=>$this->info->user_id])->update($data);

            }
            /*
            if($cate==3){
              DB::table('following')->where('to_uid',$info->user_id)->update(['isread'=>1]);
              DB::table('comments')->where('to_uid',$info->user_id)->update(['isread'=>1]);
              DB::table('likes')->where('like_to_uid',$info->user_id)->update(['read'=>1]);
            }
            */


        }

    }
    public function pubComment(Request $request){
        $img_server='http://elements.img-cn-hongkong.aliyuncs.com/';

        $content=$request->get('content');
        $content=htmlspecialchars($content);
        $id=$request->get('id');
        $to_uid=DB::table('element')->select('user_id')->where('element_id',$id)->first();
        $data=[
            'comment_uid'=>$this->info->user_id,
            'comment_eid'=>$id,
            'comment_content'=>$content,
            'to_uid'=>$to_uid->user_id,
        ];
        $iid=DB::table('comments')->insert($data);
        $ret_data=[];
        $user=DB::table('user')->where('user_id',$this->info->user_id)->first();
        $user->icon=$this->getOssPath($user->user_icon);
        $ret_data['user_name']=$user->user_name.' '.$user->user_lastname;
        $ret_data['user_icon']=$user->icon?$img_server.$user->icon->oss_path.'@0o_0l_200w_90q.src':'/images/logo.jpg';
        $ret_data['user_id']=$user->user_id;
        $ret_data['content']=$content;
        if($iid){
            return response()->json(['code'=>200,'msg'=>'ok','data'=>$ret_data]);
        }

    }
    public function userChat(){

        $uid=Input::get('uid',0);
        if($uid<=0) exit;
        $chat=DB::table('chat')->where(['from_uid'=>$this->info->user_id,'to_uid'=>$uid])->orWhere(['to_uid'=>$this->info->user_id,'from_uid'=>$uid])->get();
        foreach($chat AS $ck=>$cv){
            $sender_ico=DB::table('user')->select('user_icon')->where('user_id',$cv->sender)->first();
            $chat[$ck]->icon=$sender_ico->user_icon?$this->getOssPath($sender_ico->user_icon)->oss_path:'';
            $myicon=DB::table('user')->select('user_icon')->where('user_id',$this->info->user_id)->first();
            $chat[$ck]->myicon=$myicon->user_icon?$this->getOssPath($myicon->user_icon)->oss_path:'';
            $chat[$ck]->create_time=date('H:i A',strtotime($cv->create_time));
        }
        DB::table('chat')->where(['from_uid'=>$uid,'to_uid'=>$this->info->user_id])->update(['read'=>1]);
        return response()->json(['code'=>200,'list'=>$chat]);
    }
    public function changeEmail(Request $req){
        $email_regx = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        $email = $req->get('email');
        if(!preg_match($email_regx,$email)){
            $ret = ['code'=>200,'data'=>-2,'msg'=>''];
            return response()->json($ret);

        }else{
            $ck_email = DB::table('user')->where(['user_email'=>$email])->first();
            if($ck_email){
                $ret = ['code'=>200,'data'=>-1,'msg'=>''];
                return response()->json($ret);
            }
            $updateData= ['user_email'=>$email];
            $res = DB::table('user')->where(['user_id'=>$this->info->user_id])->update($updateData);
            if($res){
                $code = md5($this->info->user_id.'@'.time());
                $code_data = ['v_code'=>$code,'v_uid'=>$this->info->user_id];
                DB::table('verify_codes')->insert($code_data);
                $flag = Mail::send('home.verifymail',['code'=>$code],function($message) use ($email){
                    $to = $email;
                    $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
                    \App::setLocale($lang);
                    $subjects=isset($_COOKIE['lang'])?'Novaby 账户验证':'Active Novaby Account!';
                    $message ->to($to)->subject($subjects);
                });
                if($flag){
                    $ret = ['code'=>200,'data'=>0,'msg'=>'','email'=>$email];
                    return response()->json($ret);
                }

            }

        }

    }

    public function wish(Request $req){
        $id = $req->get('id',0);
        if($id==0){
            exit;
        }
        $ck = DB::table('prj_wish')->where(['prj_id'=>$id,'user_id'=>$this->info->user_id])->count();
        if($ck){
            exit;
        }else{
            $_data= ['prj_id'=>$id,'user_id'=>$this->info->user_id,'wish_time'=>time()];
            $res  = DB::table('prj_wish')->insertGetId($_data);
            if($res) echo 1;
        }

    }
    public function prj_apply(Request $req){
        $id = $req->get('id',0);
        if($id==0){
            exit;
        }
        $ck = DB::table('prj_apply')->where(['prj_id'=>$id,'user_id'=>$this->info->user_id])->count();
        if($ck){
            exit;
        }else{
            $_data= ['prj_id'=>$id,'user_id'=>$this->info->user_id,'apply_time'=>time(),'prj_status'=>1];
            $res  = DB::table('prj_apply')->insertGetId($_data);
            if($res){

                echo 1;
            }
        }

    }
}
