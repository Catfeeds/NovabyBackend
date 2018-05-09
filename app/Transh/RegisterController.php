<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Redirect;
use Session;
use App\libs\Tools;
use Mail;
class RegisterController extends Controller
{
    public function register(Request $request){
        $email=$request->get('email');
        $firstname=$request->get('firstname');
        $lastname=$request->get('lastname');
        $userpass=$request->get('userpass');
        $userrepass=$request->get('userrepass');
        $icode = $request->get('icode','');
        $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
        $errors = [
            'zh_cn'=>
                [
                    'missingCode'=>'没有邀请码！',
                    'errorCode'=>'邀请码和邮箱不匹配',
                    'failed'=>'注册失败',
                    'emailexists'=>'邮箱已被注册',
                    'success'=>'注册成功',
                ],
            'en'=>
                [
                    'missingCode'=>'missing invite code!',
                    'errorCode'=>'invalid invite code!',
                    'failed'=>'register failed',
                    'emailexists'=>'email exists',
                    'success'=>'success',
                ]
        ];
        /*
        if(!$icode){
            return response()->json(['code'=>-3,'msg'=>$errors[$lang]['missingCode']]);
        }
        */
        $vcode=0;
        if($icode){

            $ck_code = DB::table('invite')->where(['code'=>$icode,'email'=>$email])->first();
            if( $ck_code){
                $vcode=1;
            }
        }


/*
        if($vcode==0){
            return response()->json(['code'=>-4,'msg'=>$errors[$lang]['errorCode']]);
        }
*/
        $reward_conpon = 1;
        $ck_res=DB::table('user')->where('user_email',$email)->get();
        if(!$ck_res){
		    $iid=DB::table('user')->insertGetId([
		        'user_name' => $firstname,
                'user_lastname' => $lastname,
		        'user_email' => $email,
		        'user_password' => md5($userpass),
		        'user_type' => 1,
		        'user_status' => 1,
                'user_isvalidate'=>null,
		    ]);
            if($iid){
                $res=DB::table('user')->where(['user_id'=>$iid])->select('user_id','user_name','user_status')->first();
                if($res){
                    Session(['userInfo'=>$res]);
                    if($vcode){
                        $ck_inviter_wallet=DB::table('wallet')->where(['uid'=>$ck_code->uid])->first();
                        if($ck_inviter_wallet){
                            if($ck_code->uid>0){
                                DB::table('wallet')->where(['uid'=>$ck_code->uid])->increment('coupon',$reward_conpon);

                            }
                        }
                    }else{
                        if($vcode && $ck_code->uid!=0){
                            $_data = ['uid'=>$ck_code->uid,'coupon'=>$reward_conpon,'dollar'=>0,'rmb'=>0];
                            DB::table('wallet')->insert($_data);
                            $wallet_log_data = [
                                'type'=>2,
                                'income'=>1,
                                'amount'=>$reward_conpon,
                                'method'=>1,
                                'uid'=>$ck_code->uid,
                                'ctime'=>time(),
                            ];
                            DB::table('wallet_logs')->insertGetId($wallet_log_data);
                        }
                    }



                    if($vcode && $ck_code->issurvey){
                        $_data = ['uid'=>$iid,'coupon'=>1,'dollar'=>0,'rmb'=>0];
                        DB::table('wallet')->insert($_data);
                        $_wallet_log_data = [
                            'type'=>1,
                            'income'=>1,
                            'amount'=>1,
                            'method'=>1,
                            'uid'=>$iid,
                            'ctime'=>time(),
                        ];
                        DB::table('wallet_logs')->insertGetId($_wallet_log_data);
                        $_update_invite_data=['accept'=>1,'receiver'=>$iid];
                        DB::table('invite')->where(['code'=>$icode])->update($_update_invite_data);
                    }


                    //$n_data = ['uid'=>$iid,'coupon'=>$reward_conpon,'dollar'=>0,'rmb'=>0];
                    //DB::table('wallet')->insert($n_data);
                    //DB::table('invite_users')->where(['email'=>$email])->update(['receiver'=>$iid]);
                    //$code = md5($iid.'@'.time());
                    //$code_data = ['v_code'=>$code,'v_uid'=>$iid];
                    //DB::table('verify_codes')->insert($code_data);
                    //$flag = Mail::send('home.verifymail',['code'=>$code],function($message) use ($email){
                     //   $to = $email;
                     //   $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
                     //   \App::setLocale($lang);
                     //   $subjects=isset($_COOKIE['lang'])?'Novaby 账户验证':'Active Novaby Account!';
                     //   $message ->to($to)->subject($subjects);
                    //});

                    $email = $email;
                    $code = md5($iid.'@'.time());
                    $ck_code = DB::table('verify_codes')->where(['v_uid'=>$iid])->first();
                    if($ck_code){
                        DB::table('verify_codes')->where(['id'=>$ck_code->id])->update(['v_code'=>$code]);
                    }else{
                        DB::table('verify_codes')->insert(['v_uid'=>$iid,'v_code'=>$code]);
                    }
                    $flag = Mail::send('home.verifymail',['code'=>$code],function($message) use ($email){
                        $to = $email;
                        $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
                        \App::setLocale($lang);
                        $subjects=isset($_COOKIE['lang'])?'Novaby 账户验证':'Active Novaby Account!';
                        $message ->to($to)->subject($subjects);
                    });
                    return response()->json(['code'=>0,'msg'=>$errors[$lang]['success']]);
                }
                    
            }else{
                return response()->json(['code'=>-2,'msg'=>$errors[$lang]['failed']]);
            }
        }else{
            return response()->json(['code'=>-1,'msg'=>$errors[$lang]['emailexists']]);
        }
    }
    public function chkreg(Request $req){
        dd($req->all());
    }
    
    public function login(Request $request){
        $email=$request->get('email');
        $password=$request->get('password');
        $remember = $request->get('rememberme');
        $res=DB::table('user')->where(['user_email'=>$email,'user_password'=>md5($password)])->select('user_id','user_name','user_status','user_isvalidate')->first();
        $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
        $errors = [
            'zh_cn'=>
                [
                    'failed'=>'密码和用户名不匹配',
                    'success'=>'登录成功',

                ],
            'en'=>
                [
                    'failed'=>'Your ID and password don\'t match',
                    'success'=>'login success',

                ]
        ];
        if($res){
                if ($remember){
                    $lifetime = time() + 60 * 60 * 24 * 365;
                }
                
                    Session(['userInfo'=>$res]);
                    $has_verify = $res->user_isvalidate;
                    return response()->json(['code'=>0,'msg'=>$errors[$lang]['success'],'v'=>$has_verify]);
		}else{
                    return response()->json(['code'=>-1,'msg'=>$errors[$lang]['failed']]);
                }
        }
}