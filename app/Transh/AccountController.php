<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Session;
use DB;
use App\libs\ApiConf;
use Mail;
class AccountController extends Controller
{
    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    public function logout(){
        Session::set('userInfo',null);
        if(Session::has('survey')){
            Session::set('survey',null);
        }
        if(Session::has('skipsurvey')){
            Session::set('skipsurvey',null);
        }
        header('Location: /');
    }
    public function userprofile(){
        $user=DB::table('user')->where('user_id',$this->info->user_id)->first();
        if($user->user_icon>0){
            $icon=DB::table('oss_item')->select('oss_path')->where('oss_item_id',$user->user_icon)->first();
            $user->icon=ApiConf::IMG_URI.$icon->oss_path;
        }else{
            $user->icon='';
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        return view('user.userprofile',['user'=>$this->info,'userinfo'=>$user,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Edit Profile']);
    }
    public function usersetting(){
        $cart_info=[];
        $notices=[];
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        //return view('user.userprofile',['user'=>$info,'userinfo'=>$user,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info]);
        $payments=DB::table('payments')->where(['user_id'=>$this->info->user_id])->first();
        if(!$payments){
            $payments=(Object)[];
            $payments->paypal_account='';
            $payments->alipay_account='';
        }

        $auths = DB::table('user')->select('user_facebook_id','user_twitter_id','user_linkedin_id','user_email')->where(['user_id'=>$this->info->user_id])->first();
        return view('user.usersetting',['user'=>$this->info,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Settings','payments'=>$payments,'auths'=>$auths]);
    }

    public function editaccount(Request $request){

        $email=$request->get('email');
        $regx='/^[0-9a-zA-Z]+@(([0-9a-zA-Z]+)[.])+[a-z]{2,4}$/i';
        if(!preg_match($regx,$email)){
            echo -1;
            exit;
        }

        $opass=$request->get('opass');
        $pass=$request->get('pass');
        $repass=$request->get('repass');

        $ck_mail=DB::table('user')->where(['user_email'=>$email])->first();
        if($ck_mail && $ck_mail->user_id!=$this->info->user_id){
            return response()->json(['code'=>-2,'error'=>'email already exists']);

        }
        $ck_pass=DB::table('user')->where(['user_id'=>$this->info->user_id])->first();
        if($ck_pass->user_password!=md5($opass)){
            return response()->json(['code'=>-3,'error'=>'Original Password invalid!']);
        }

        $data=[
            'user_email'=>$email,
            'user_password'=>md5($pass),

        ];
        $res=DB::table('user')->where('user_id',$this->info->user_id)->update($data);
        if($res){
            return response()->json(['code'=>0,'msg'=>'ok']);
        }else{
            return response()->json(['code'=>-1,'msg'=>'ok','error'=>'error']);
        }

    }
    public function editpayaccount(Request $request){

        $paypal_account=$request->get('paypal_account');
        $alipay_account=$request->get('alipay_account');
        $wechat_account=$request->get('Wechat');
        $ck_data=DB::table('payments')->where(['user_id'=>$this->info->user_id])->first();
        if(!$ck_data){
            $res=DB::table('payments')->insert(
                [
                    'user_id'=>$this->info->user_id,
                    'paypal_account'=>$paypal_account,
                    'alipay_account'=>$alipay_account,
                    'wechat_account'=>$wechat_account,
                ]
            );
            if($res){
                return response()->json(['code'=>200,'msg'=>'ok']);
            }
        }else{
            $res=DB::table('payments')->where(['user_id'=>$this->info->user_id])->update([
                'paypal_account'=>$paypal_account,
                'alipay_account'=>$alipay_account,
                'wechat_account'=>$wechat_account,
            ]);
        }
        if($res){
            return response()->json(['code'=>200,'msg'=>'ok']);
        }
    }
    public function profile( Request $request)
    {


        $myinfo=DB::table('user')->where(['user_id'=>$this->info->user_id])->first();
        if($myinfo->user_icon>0){
            $record=DB::table('oss_item')->where('oss_item_id',$myinfo->user_icon)->first();
            $myinfo->user_icon='http://'.ApiConf::OSS_BUKET_NAME_ELEMENTS.'.'.ApiConf::OSS_ENDPOINT.'/'.$record->oss_path;
        }else{
            $myinfo->user_icon='';
        }
        $mylist=DB::table('element')->where(['user_id'=>$this->info->user_id,'element_isdel'=>0])->orderBy('element_id','DESC')->Paginate(5);
        $cates=DB::table('category')->get();
        $newcates=[];
        foreach($cates AS $k=>$v){
            $newcates[$v->cate_id]=$v->cate_name;
        }

        foreach($mylist AS $k=>$v){
            $mylist[$k]->format=$newcates[$mylist[$k]->element_format];
            $images=explode(',',$v->element_images);
            $cover=DB::table('oss_item')->where(['oss_item_id'=>$images[0]])->first();

            $mylist[$k]->cover='http://'.ApiConf::OSS_BUKET_NAME_ELEMENTS.'.'.ApiConf::OSS_ENDPOINT.'/'.$cover->oss_path;
        }
        //$mylist_sql=" select elem.*,a1.cate_name AS category,a2.cate_name AS style,a3.cate_name AS format,a4.cate_name AS level from `element` as `elem` left join `category` as `a1` on `elem`.`element_category` = `a1`.`cate_id` left join `category` as `a2` on `elem`.`element_style` = `a2`.`cate_id` left join `category` as `a3` on `elem`.`element_format` = `a3`.`cate_id` LEFT JOIN category AS a4 ON elem.element_level=a4.cate_id WHERE elem.user_id=".$userInfo->user_id;
        //$mylist=DB::select($mylist_sql);


        return view('user.profile',['datas'=>['myinfo'=>$myinfo,'list'=>$mylist],'title'=>'Profile']);
    }
    public function editIcon(Request $request){

        $icon=$request->get('icon');
        $insert_id=DB::table('oss_item')->insertGetId(
            [
                'oss_key'=>'elements',
                'oss_path'=>$icon,
                'oss_item_uid'=>$this->info->user_id,

            ]
        );
        if($insert_id){
            echo $res=DB::table('user')->where('user_id',$this->info->user_id)->update(['user_icon'=>$insert_id]);

        }
    }
    public function editprofile(Request $request){

        $firstname=$request->get('firstname');
        $lastname=$request->get('lastname');
        $page_id=$request->get('page_id', NULL);
        $location=$request->get('location');
        $work=$request->get('work');
        if($page_id){
            $ck_page_id=DB::table('user')->select("user_id")->where(['user_page_id'=>$page_id])->first();
            if($ck_page_id && $ck_page_id->user_id!=$this->info->user_id){
                return response()->json(['code'=>-2,'msg'=>'','error'=>'error']);
                exit;
            }
        }
        $data=[
            'user_name'=>$firstname,
            'user_lastname'=>$lastname,
            'user_page_id'=>$page_id,
            'user_country'=>$location,
            'user_work'=>$work,

        ];
        $res=DB::table('user')->where('user_id',$this->info->user_id)->update($data);
        if($res){
            $res=DB::table('user')->where(['user_id'=>$this->info->user_id])->select('user_id','user_name','user_status','user_isvalidate')->first();
            Session(['userInfo'=>$res]);
            return response()->json(['code'=>0,'msg'=>'ok']);
        }else{
            return response()->json(['code'=>-1,'msg'=>'ok','error'=>'error']);
        }

    }
    public function stepOne(){
        $ck_apply = DB::table("apply_user")->select("status")->where(['uid'=>$this->info->user_id])->first();
        if($ck_apply && $ck_apply->status==3){
            header('location: /');
            exit;
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $ages = [
            1=>'18-25',
            2=>'26-40',
            3=>'more then 40',
        ];
        $experiences =[
            1=>'1-3 years',
            2=>'3-5 years',
            3=>'5-10 years',
            4=>'more then 10 years',
            5=>'student',
        ];
        $fields = DB::table('category')->where(['cate_pid'=>1,'cate_active'=>0])->orderBy('cate_order', 'ASC')->get();
        foreach($fields AS $k=>$v){
            $fields[$k]->cate_name = ucwords($v->cate_name);
        }
        $genders = [1=>'male', 2=>'famale'];
        $user_info = DB::table('user')->leftJoin('apply_user', 'user.user_id', '=', 'apply_user.uid')->leftJoin('payments','user.user_id', '=', 'payments.user_id')->where('user.user_id',$this->info->user_id)->first();
        $age = $user_info->age?$user_info->age:0;
        $experience = $user_info->experience?$user_info->experience:0;
        $gender = $user_info->gender ? $ages[$user_info->gender]:'';
        $user_fields = $user_info->fields?explode("-",$user_info->fields):[];
        foreach($fields AS $k=>$v){
            if(in_array($v->cate_id, $user_fields)){
                $fields[$k]->s = 1;
            }else{
                $fields[$k]->s = 0;
            }
        }
        $apply_info = [
            'fields'=>$fields,
            'experiences'=>$experiences,
            'ages'=>$ages,
            'experience'=>$experience?['k'=>$experience,'v'=>$experiences[$experience]]:'',
            'age'=>$age?['k'=>$age,'v'=>$ages[$age]]:'',
            'country' =>$user_info->user_country,
            'gender'=>$user_info->gender ? $user_info->gender:1,
        ];
        //dd($apply_info);
        return view('user.sign.step1',['user'=>$this->info,'title'=>'step1','notices'=>$notices,'cart_info'=>$cart_info,'userinfo'=>$user_info,'apply_info'=>$apply_info]);
    }
    public function applyInfo(Request $req){

        $firstname = $req->get('firstname');
        $lastname = $req->get('lastname');
        $gender = $req->get('gender');
        $age = $req->get('age');
        $country = $req->get('country');
        $experience=$req->get('experience');
        $tags = $req->get('tags');
        $paypal_account = $req->get('paypal_account');
        $alipay_account = $req->get('alipay_account');
        $apply_data = [
            'age'=>$age,
            'gender'=>$gender,
            'experience'=>$experience,
            'fields'=>$tags,
            'uid'=>$this->info->user_id,
            'apply_time'=>time(),

        ];
        $info_data = [
            'user_name' => $firstname,
            'user_lastname' => $lastname,
            'user_country' => $country,

        ];
        $payment_data = [
            'paypal_account'=>$paypal_account,
            'alipay_account'=>$alipay_account,
            'user_id'=>$this->info->user_id,
        ];

        DB::table('user')->where(['user_id'=>$this->info->user_id])->update($info_data);
        $ck_apply = DB::table('apply_user')->where('uid',$this->info->user_id)->first();
        if($ck_apply){
            DB::connection()->enableQueryLog();
            DB::table('apply_user')->where(['id'=>$ck_apply->id])->update($apply_data,['uid'=>$this->info->user_id]);
            $sql = DB::getQueryLog();

            $query = end($sql);
           // var_dump($query);
        }else{
                DB::table('apply_user')->insert($apply_data);
        }

        $ck_payment = DB::table('payments')->where('user_id',$this->info->user_id)->first();
        if($ck_payment){
           // DB::table('payments')->where(['user_id'=>$this->info->user_id])->update($payment_data);
        }else{
            //DB::table('payments')->insert($payment_data);
        }


        return response()->json(['code'=>200,'data'=>1,'msg'=>'ok']);

    }
    public function applysuccess(){
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('apply.success',['user'=>$this->info,'title'=>'successfully!','notices'=>$notices,'cart_info'=>$cart_info]);

    }
    public function applyfail(){
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        DB::table('apply_user')->where(['uid'=>$this->info->user_id])->update(['isread'=>1]);
        $apply=DB::table('apply_user')->where(['uid'=>$this->info->user_id])->first();

        return view('apply.fail',['user'=>$this->info,'title'=>'successfully!','notices'=>$notices,'cart_info'=>$cart_info,'reason'=>$apply->deny_reason]);
    }
    public function applyresult(){
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('apply.result',['user'=>$this->info,'title'=>'successfully!','notices'=>$notices,'cart_info'=>$cart_info]);
    }
    public function wallet(){
        $wallet_data = DB::table('wallet')->where(['uid'=>$this->info->user_id])->first();
        $const_num = 0;
        $real_num = 0;
        if($wallet_data){
            $const_num = $wallet_data->coupon;
            $real_num = $wallet_data->dollar;
        }

        $wallet = [
            'const_num' => $const_num,
            'real_num' => $real_num,
        ];
        $logs = DB::table('wallet_logs')->where(['uid'=>$this->info->user_id])->get();
        $model_logs = DB::table('orders')->whereRAW('order_paytime>0 AND order_price>0 AND  order_owner='.$this->info->user_id)->orderBy('order_paytime','DESC')->get();

        foreach($model_logs AS $k=>$v){
            if($v->order_owner==$this->info->user_id){

            }
            if($v->order_paymethod==1){
                $model_logs[$k]->order_paymethod = 'Paypal';
            }elseif($v->order_paymethod==2){
                $model_logs[$k]->order_paymethod = 'Coupon';
            }else{
                $model_logs[$k]->order_paymethod = '';
            }
            $owner_info = DB::table('user')->select('user_name','user_lastname')->where(['user_id'=>$this->info->user_id])->first();
            $buyer_info = DB::table('user')->select('user_name','user_lastname','user_email')->where(['user_id'=>$v->order_uid])->first();
            $model_logs[$k]->payer_name = $buyer_info->user_name.' '.$buyer_info->user_lastname;
            $model_logs[$k]->payer_email = $buyer_info->user_email;
            $model_logs[$k]->owner_name = $owner_info->user_name.' '.$owner_info->user_lastname;
        }
        //dd($model_logs);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('user.wallet',['user'=>$this->info,'title'=>'My Wallet','notices'=>$notices,'cart_info'=>$cart_info, 'lists'=>$logs, 'wallet'=>$wallet,'model_log'=>$model_logs]);
    }
    public function verifyEmail(){
        $has_verify=DB::table('user')->select('user_isvalidate','user_email')->where(['user_id'=>$this->info->user_id])->first();
        if($has_verify->user_isvalidate==1){
            return response()->json(['code'=>200,'data'=>0]);
        }
        $email = $has_verify->user_email;
        $code = md5($this->info->user_id.'@'.time());
        $ck_code = DB::table('verify_codes')->where(['v_uid'=>$this->info->user_id])->first();
        if($ck_code){
            DB::table('verify_codes')->where(['id'=>$ck_code->id])->update(['v_code'=>$code]);
        }else{
            DB::table('verify_codes')->insert(['v_uid'=>$this->info->user_id,'v_code'=>$code]);
        }
        $flag = Mail::send('home.verifymail',['code'=>$code],function($message) use ($email){
            $to = $email;
            $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
            \App::setLocale($lang);
            $subjects=isset($_COOKIE['lang'])?'Novaby 账户验证':'Active Novaby Account!';
            $message ->to($to)->subject($subjects);
        });
        if($flag){
            return response()->json(['code'=>200,'data'=>1]);
        }else{
            echo "send email error";
        }


    }
    public function ckverify(){
        $data = DB::table('user')->select('user_isvalidate')->where(['user_id'=>$this->info->user_id])->first();
        if($data->user_isvalidate==1){
            echo 1;
        }
    }

    public function invite(){
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $invite_url = '';
        $inv_data = DB::table('invite')->where(['uid'=>$this->info->user_id])->first();
        if($inv_data){
            $invite_url = 'https://'.$_SERVER['HTTP_HOST'].'?invite='.$inv_data->code;
        }else{
           // $code = md5($this->info->user_id.time());
            //DB::table('invite')->insert(['uid'=>$this->info->user_id,'code'=>$code,'times'=>5]);
            //$invite_url = 'https://'.$_SERVER['HTTP_HOST'].'?invite='.$code;
        }
        $inv_nums = 0;
        $invites = DB::table('invite')->where(['uid'=>$this->info->user_id])->where('email','!=',null)->orderBy('id','desc')->get();
        foreach($invites AS $k=>$v){
            $has_follow = 0;
            if($v->receiver){
                $u = DB::table('user')->select('user_icon','user_name','user_lastname')->where(['user_id'=>$v->receiver])->first();

                $has_follow_data = DB::table('following')->where(['from_uid'=>$this->info->user_id,'to_uid'=>$v->receiver])->first();
                if($has_follow_data && $has_follow_data->followed==1){
                    $has_follow=1;
                }
                $invites[$k]->ico= $u->user_icon>0 ? ApiConf::IMG_URI.$this->getOssPath($u->user_icon)->oss_path."@0o_0l_50w_90q.src":'';
                $invites[$k]->username = $u->user_name." ".$u->user_lastname;
                $inv_nums ++;

            }else{
                $invites[$k]->ico= '';

            }
            $invites[$k]->follow=$has_follow;

        }

        return view('user.invite',['user'=>$this->info,'title'=>'My invitations','notices'=>$notices,'cart_info'=>$cart_info,'invite_url'=>$invite_url,'invites'=>$invites,'inv_nums'=>$inv_nums]);
    }
    public function doInvite(Request $req){
        $emails = $req->get('emails');
        if(count($emails)<=0) exit;
        $be_invited_emails=[];
        foreach($emails AS $k=>$v){
            $has_invite = DB::table('invite')->where(['email'=>$v])->count();
            $has_reg = DB::table('user')->where(['user_email'=>$v])->count();
            if($has_invite==0 && $has_reg==0){
                $be_invited_emails[]=$v;
            }


        }
        if(count($be_invited_emails)==0){
            return response()->json(['code'=>200,'data'=>0]);
        }
        /*
        $invite_url = '';
        $inv_data = DB::table('invite')->where(['uid'=>$this->info->user_id])->first();
        if($inv_data){

            $invite_url = 'http://'.$_SERVER['HTTP_HOST'].'?invite='.$inv_data->code;
        }
        if($invite_url == '') return;
        */

        $num = 0;
        if(count($be_invited_emails)>0){
            $userinfo =DB::table('user')->select('user_name','user_lastname','user_icon')->first();
            $username = $userinfo->user_name.' '.$userinfo->user_lastname;
            $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
            //\App::setLocale($lang);
            $subject = isset($_COOKIE['lang'])?' 邀请您加入Novaby！':'invited you to join Novaby!';
            $subject = $username.$subject;

            $time = time();
            $user_icon='https://'.$_SERVER['HTTP_HOST'].'/images/defaultuser.png';
            if($userinfo->user_icon!=0){
                $user_icon=ApiConf::IMG_URI.$this->getOssPath($userinfo->user_icon)->oss_path.'@0o_0l_200w_90q.src';
            }
            foreach($be_invited_emails AS $k=>$v){
                $code = md5($v);
                $inv_data=[
                    'uid'=>$this->info->user_id,
                    'code'=>$code,
                    'email'=>$v
                ];
                $inv_insert_id = DB::table('invite')->insertGetId($inv_data);
                $invite_url = 'https://'.$_SERVER['HTTP_HOST'].'/auth/register?invite='.$code;
                if($inv_insert_id){
                        $email = $v;
                        $flag = Mail::send('emailtpl.invite',['url'=>$invite_url, 'name'=>$username,'icon'=>$user_icon],function($message) use ($email,$subject){
                        $to = $email;
                        $message ->to($to)->subject($subject);
                });
                if($flag){
                    //return response()->json(['code'=>200,'data'=>1]);
                    $ck_email = DB::table('invite_users')->where(['email'=>$v])->first();
                    if(!$ck_email){
                        $insert_data = ['sender'=>$this->info->user_id,'email'=>$v,'send_time'=>$time];
                        DB::table('invite_users')->insert($insert_data);
                    }
                    $num++;
                }else{
                    echo "send email error";
                }
                }
            }
            if($num>0){
                return response()->json(['code'=>200,'data'=>$num]);
            }
        }
    }
    public function genrecharge(Request $req){
        $num = $req->get('num',0);
        if($num == 0){
            die();
        }
        $recharge_data = ['uid'=>$this->info->user_id,'tot'=>$num,'create_time'=>time()];
       // dd($recharge_data);
        $iid = DB::table('recharge')->insertGetid($recharge_data);
        if($iid){
            return response()->json(['code'=>200,'data'=>$iid]);
        }
    }
    public function modelerReward(Request $req){
        $ck_data = DB::table('apply_user')->where(['uid'=>$this->info->user_id])->first();
        if(!$ck_data){
            //没有申请
            return response()->json(['code'=>200,'data'=>-1]);
        }else{
            if($ck_data->wallet>0){
                //已经获取
                return response()->json(['code'=>200,'data'=>-2]);
            }else{
                $wallet = DB::table('wallet')->where(['uid'=>$this->info->user_id])->first();


                if(!$wallet){
                    $_data = [
                        'uid'=>$this->info->user_id,
                        'coupon'=>0,
                        'dollar'=>0,
                        'rmb'=>0,
                    ];
                    $res = DB::table('wallet')->insertGetId($_data);


                }
                DB::table('wallet')->where(['uid'=>$this->info->user_id])->increment('coupon',10);
                DB::table('apply_user')->where(['uid'=>$this->info->user_id])->update(['wallet'=>10,'isread'=>'1']);
                $wallet_log_data = [
                    'type'=>3,
                    'income'=>1,
                    'amount'=>10,
                    'method'=>1,
                    'uid'=>$this->info->user_id,
                    'ctime'=>time(),
                ];
                DB::table('wallet_logs')->insertGetId($wallet_log_data);
                return response()->json(['code'=>200,'data'=>0]);
            }
        }


    }
    public function apply(){
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $ck_survey = DB::table('wallet_logs')->where(['uid'=>$this->info->user_id,'type'=>1])->count();
        if($ck_survey==1){
            return redirect('/');
        }

        return view('nova.apply',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Welcome to Novaby']);
    }

    public function applywithoutapply(Request $req){
        $email = $req->get('email');
        $has_invite = DB::table('invite')->where(['email'=>$email])->count();
        $has_reg = DB::table('user')->where(['user_email'=>$email])->count();
        if($has_invite>0 || $has_reg>0){
            echo 0;
            exit;
        }

        Session::set('surveyEmail',$email);

        $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
        $subject=$lang?'Novaby 注册邀请链接':'Novaby Register link ';
        $icon='https://www.novaby.com/images/logo.jpg';
        $code = md5($email);
        $inv_data = [
            'uid'=>0,
            'code'=>$code,
            'email'=>$email,
            'issurvey'=>0,
        ];
        $id=DB::table('invite')->insertGetId($inv_data);

        $invite_url='https://www.novaby.com?invite='.$code;
        Mail::send('emailtpl.invite',['url'=>$invite_url, 'name'=>'Novaby','icon'=>$icon],function($message) use ($email,$subject){
            $to = $email;
            $message ->to($to)->subject($subject);
        });

        echo 1;

        /*

                Session::set('surveyEmail',$email);


                $icon='http://www.novaby.com/images/logo.jpg';
                $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
                $subject=$lang?'Novaby 注册邀请链接':'Novaby Register link ';
                $invite_url='http://www.novaby.com?invite=53550014ea33c66b0e24317d5c7eaf53';
                Mail::send('emailtpl.invite',['url'=>$invite_url, 'name'=>'Novaby','icon'=>$icon],function($message) use ($email,$subject){
                    $to = $email;
                    $message ->to($to)->subject($subject);
                });
                echo 1;
        */


    }
    public function doApply(Request $req){
        $apply_data = $req->get('data');
        if(!is_array($apply_data) || count($apply_data)!=8){
            echo 0;
        }else{
            //$email = $apply_data[8];
            //$ck_email1 = DB::table('survey')->where(['email'=>$email])->first();
            //if($ck_email1){
             //   echo 0;
             //   exit;
            //}
           // $ck_email2 = DB::table('user')->where(['user_email'=>$email])->first();
           // if($ck_email2){
            //    echo 0;
           //     exit;
           // }

            //Session::set('surveyEmail',$email);
            $data = ['a1'=>$apply_data[0],'a2'=>$apply_data[1],'a3'=>$apply_data[2],'a4'=>$apply_data[3],'a5'=>$apply_data[4],'a6'=>$apply_data[5],'a7'=>$apply_data[6],'a8'=>$apply_data[7],'email'=>''];
            $iid = DB::table('survey')->insert($data);

            //$icon='http://www.novaby.com/images/logo.jpg';
            if($iid){
                //$lang = isset($_COOKIE['lang'])?'zh_cn':'en';
                //$subject=$lang?'Novaby 注册邀请链接':'Novaby Register link ';

                //$code = md5($email);
                //$inv_data = [
                 //   'uid'=>0,
                 //   'code'=>$code,
                 //   'email'=>$email,
                 //   'issurvey'=>1,
                //];
                //DB::table('invite')->insertGetId($inv_data);
                //$invite_url='http://www.novaby.com?invite='.$code;
                //Mail::send('emailtpl.invite',['url'=>$invite_url, 'name'=>'Novaby','icon'=>$icon],function($message) use ($email,$subject){
                 //   $to = $email;
                 //   $message ->to($to)->subject($subject);
                //});
                $ck_wallet = DB::table('wallet')->where(['uid'=>$this->info->user_id])->count();
                if($ck_wallet==0){
                    $add_coupon_data =[
                        'uid'=>$this->info->user_id,
                        'coupon'=>1,
                        'dollar'=>0,
                        'rmb'=>0,

                    ];
                    DB::table('wallet')->insertGetId($add_coupon_data);

                }else{
                    DB::table('wallet')->where(['uid'=>$this->info->user_id])->increment('coupon',1);
                }

                $_log_data = [
                    'type'=>1,
                    'income'=>1,
                    'amount'=>1,
                    'method'=>1,
                    'uid'=>$this->info->user_id,
                    'ctime'=>time(),
                ];
                DB::table('wallet_logs')->insertGetId($_log_data);

                Session::set('survey',time()+10);

                echo 1;
            }


        }
    }
    public function skipapply(Request $req){
        Session::set('skipsurvey',time()+10);
        echo 1;

    }
    public function avatar(){
        $user=DB::table('user')->select('user_icon')->where('user_id',$this->info->user_id)->first();
        if($user->user_icon>0){
            $icon=DB::table('oss_item')->select('oss_path')->where('oss_item_id',$user->user_icon)->first();
            $user->icon=ApiConf::IMG_URI.$icon->oss_path;
        }else{
            $user->icon='/images/logo.jpg';
        }

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);


        return view('profile.avatar',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'change your avatar','icon'=>$user->icon]);


    }
    public function basic(){
        $data = DB::table('user')->select('user_name','user_lastname','user_gender','user_country','user_work','user_work_exp')->where(['user_id'=>$this->info->user_id])->first();
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);


        return view('profile.basic',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'basic info']);


    }
    public function professional(){
        $data = DB::table('user')->select('user_fileds')->where(['user_id'=>$this->info->user_id])->first();
        $data = explode(",",$data->user_fileds);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);


        return view('profile.professional',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$data,'title'=>'profile']);


    }

    public function payment(){
        $payment_account = DB::table('payments')->where(['user_id'=>$this->info->user_id])->first();
        if(!$payment_account){
            $payment_account=(object)['paypal_account'=>'','alipay_account'=>'','wechat_account'=>''];
        }
        //dd($payment_account);
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);


        return view('profile.payment',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'data'=>$payment_account,'title'=>'payment']);


    }
    public function social(){
        $auths = DB::table('user')->select('user_facebook_id','user_twitter_id','user_linkedin_id','user_email')->where(['user_id'=>$this->info->user_id])->first();

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);


        return view('profile.social',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'auths'=>$auths,'title'=>'social']);


    }
    public function security(){
        $account =  DB::table('user')->select('user_email')->where(['user_id'=>$this->info->user_id])->first();

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);


        return view('profile.security',['user'=>$this->info,'notices'=>$notices,'data'=>$account,'cart_info'=>$cart_info,'title'=>'security']);


    }
    public function updateprofessional(Request $req){
        $ids = $req->get("ids");
        $ids = rtrim($ids,",");
        $res = DB::table('user')->where(['user_id'=>$this->info->user_id])->update(['user_fileds'=>$ids]);
        echo 1;

    }
    public function updatebasic(Request $request){
        $firstname=$request->get('firstname');
        $lastname=$request->get('lastname');
        $page_id=$request->get('page_id', NULL);
        $location=$request->get('location');
        $work=$request->get('work');
        $work_exp= $request->get('work_exp');
        $gender = $request->get('gender');

        if($page_id){
            $ck_page_id=DB::table('user')->select("user_id")->where(['user_page_id'=>$page_id])->first();
            if($ck_page_id && $ck_page_id->user_id!=$this->info->user_id){
                return response()->json(['code'=>-2,'msg'=>'','error'=>'error']);
                exit;
            }
        }
        $data=[
            'user_name'=>$firstname,
            'user_lastname'=>$lastname,
            'user_page_id'=>$page_id,
            'user_country'=>$location,
            'user_work'=>$work,
            'user_work_exp'=>$work_exp,
            'user_gender'=>$gender,


        ];
        $res=DB::table('user')->where('user_id',$this->info->user_id)->update($data);
        if($res){
            $res=DB::table('user')->where(['user_id'=>$this->info->user_id])->select('user_id','user_name','user_status','user_isvalidate')->first();
            Session(['userInfo'=>$res]);
            return response()->json(['code'=>0,'msg'=>'ok']);
        }else{
            return response()->json(['code'=>-1,'msg'=>'ok','error'=>'error']);
        }
    }
}
