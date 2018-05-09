<?php

namespace App\Http\Controllers\Api;

use App\Events\Event;
use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Model\Notify;
use App\Model\Passreset;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Validator;
use Mail;

class AccountApiController extends BaseApiController
{
    /**
     * 找回密码发送邮件
     * @param Request $req
     * @return mixed
     */
    public function findpass(Request $req){
        $email = $req->get('email','');
        $user = User::where('user_email',$email)->first();
        if(!$user){
            return $this->jsonErr("email not found");
        }
        $code = $this->code();
        $pr = new Passreset();
        $pr->uid = $user->user_id;
        $pr->code = $code;
        $pr->create_at = time();
        if($pr->save()){
            $flag = Mail::send('emailtpl.mail',['code'=>$code,'user'=>$user->user_name],function($message) use ($email){
                $to = $email;
                $subject = 'Reset your Novaby password';
                $message ->to($to)->subject($subject);
            });
            if($flag){
                return $this->jsonOk('ok',['msg'=>'send email ok']);
            }else{
                return $this->jsonErr('send mail error!');
            }
        }

    }

    /**
     * 验证码
     * @return string
     */
    private function code(){
        $code ='';
        for($i=0;$i<5;$i++){
            $code.=rand(0,9);
        }
        return $code;
    }

    /**重置密码
     * @param Request $req
     * @return mixed
     */
    public function resetpass(Request $req){
        $email = $req->get('email','');
        $code = $req->get('code','');
        $pass = $req->get('password','');
        $pass_repeat = $req->get('password_repeat','');
        if(strlen($pass)<5 || strlen($pass)>20){
            return $this->jsonErr("password must be  5-20 character");
        }
        $pr = Passreset::where('code',$code)->first();
        if(!$pr){
            return $this->jsonErr("verification code error");
        }
        if($pass_repeat!=$pass){
            return $this->jsonErr("password mismatch");
        }
        $user = User::where('user_email',$email)->first();
        if(!$user){
            return $this->jsonErr("email not found");
        }
        $user->user_password=md5($pass);
        if($user->save()){
            $pr->delete();
            return $this->jsonOk("reset password successfully",[]);
        }else{
            return $this->jsonErr("reset password error");
        }

    }

    /**检查验证码
     * @param Request $req
     * @return mixed
     */
    public function ckeckcode(Request $req){
        $email = $req->get('email','');
        $code = $req->get('code','');
        $pr = Passreset::where('code',$code)->first();
        if(!$pr){
            return $this->jsonErr("verification code error");
        }else{
            return $this->jsonOk("ok",[]);
        }

    }
}
