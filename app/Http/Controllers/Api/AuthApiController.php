<?php

namespace App\Http\Controllers\Api;


use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Http\Controllers\IndexController;
use App\Listeners\MailListener;
use App\Listeners\NotifyListener;
use App\Model\Project;
use App\Model\UserCloud;
use App\Model\UserInfo;
use App\Model\Wallet;
use App\Model\User;
use App\Model\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use \Validator;


class AuthApiController extends BaseApiController
{

    /**
     * 登录
     * @param Request $req
     * @return mixed
     */
    public function login(Request $req){
        $email = $req->get('email');
        $passwrod = $req->get('password');
        $rules = array(
            'email' => 'required|email',
            'password' => 'required',
        );
        $message = array(
            "required"             => ":attribute can't be blank",
        );

        $attributes = array(
            "email" => 'email',
            'password' => 'password',
        );
        $validator = Validator::make(
            $req->all(),
            $rules,
            $message,
            $attributes
        );
        if ($validator->fails()) {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return $this->jsonErr($show_warning);
        }
        $passwrod = md5($passwrod);
        $user = User::with('info')->where('user_email', $email)->where('user_password',$passwrod)->first();
        if($user){
            if($user->user_type==0)   //检验是否审核的企业账号
            {
                return $this->jsonErr('Sorry,the business account is under review');
            }else{
                if($user->user_status ==0) //检验账号是否激活
                {
                    return $this->jsonErr('Sorry,Sorry, your account has not been activated');
                }else{
                    if($user->info){
                        $lang = $user->info->user_lang;
                    }else{
                        $lang='';
                    }
                    $base_info = [
                        'user_id'       =>  $user->user_id,
                        'user_name'     =>  $this->getName($user),
                        'user_avatar'   =>  $this->getAvatar($user->user_icon),
                        'user_type'     =>  $user->user_type,
                        'user_email'     =>  $user->user_email,
                        'user_country' =>$user->country?$user->country->name:'',
                        'user_city' =>$user->city?$user->city->name:'',
                        'user_work' =>$user->Job?$user->Job->name:'',
                        'user_lang' => $lang,
                        'wallet' => $user->wallet ? $user->wallet->USD : 0,
                    ];
                    Cache::forget($user->user_token);
                    $user->user_token = $this->createToken($user->user_id);
                    Cache::forever($user->user_token,$base_info);
                    $user->user_token_time=time();
                    $user->user_last_login_time = time();
                    if($user->save()){
                        Work::where('work_uid',$user->user_id)->where(['work_photos'=>null,'work_title'=>null,'work_trans'=>0])->with('upload')->delete();
                        Project::where('prj_uid',$user->user_id)->where(['prj_type'=>0,'prj_industry'=>0,'prj_progress'=>1])->delete();
                        return $this->jsonOk('ok',['token'=>$user->user_token,'basic_info'=>$base_info]);
                    }else{
                        return $this->jsonErr('Sorry, password and account don\'t match.');
                    }
                }
            }
        }else{
            return $this->jsonErr('Sorry, password and account don\'t match.');
        }

    }

    /**
     * 注册
     * @param Request $req
     * @return mixed
     */
    public function register(Request $req)
    {
        $lang = Input::get('lang');
        $email = $req->get('email');
        $passwrod = $req->get('password');
        $password_confirmation = $req->get('password_confirmation');
        $firstname = $req->get('firstname');
        $lastname = $req->get('lastname', '');

        if ($firstname)
        {
            $rules = array(
                'email' => 'required|email|unique:user,user_email',
                'password' => 'required|between:6,20|confirmed',
                'password_confirmation' => 'required|between:6,20',
                'firstname' =>'required'

            );
            $request = $req->all();
        }else {
            $home_id = $this->inputUrl($req->get('web'));
            if ($home_id) {
                $ck_home_id = User::where('user_page_id', $home_id)->count();
                if ($ck_home_id) {
                    return $this->jsonErr($this->returnErr($lang,'page'));
                }
            }
            $rules = array(
                'email' => 'required|email|unique:user,user_email',
                'password' => 'required|between:6,20|confirmed',
                'password_confirmation' => 'required|between:6,20',
                'company_name' =>'required|unique:user,company_name',
                'web' =>'required|url',
                'year' =>'required|numeric',
            );
            $request = $req->all();
            $request['web'] = $home_id;
        }
        $result = $this->postValidate($request,$rules);
        if($result) {
            return $this->jsonErr($result);
        }
        $user = new  User();

        $user->user_email = $email;
        $user->user_password = md5($passwrod);
        if($req->get('company_name'))
        {
            $company_name =$req->input('company_name');
            $fileds = $req->get('fileds');
            if($fileds){
                $fileds= implode(",",$fileds);
            }
            if(count($fileds)>3){
                return $this->jsonErr($this->returnErr($lang,'field'));
            }
            $user->user_icon = $req->get('icon',0);
            $user->user_fileds = $fileds;
            $user->user_description = $req->get('description');
            $user->english_level = $req->get('english',0);
            $user->year_founded = $req->get('year',2017);
            $user->user_page_id = $req->get('web');
            $user->company_type = $req->get('company_type',0);
            $user->company_size = $req->get('company_size',0);
            $user->company_name =$company_name;
            $user->user_country = $req->get('country');
            $user->user_city = $req->get('city');
            $user->user_type = 0; //企业审核
            $user->user_status = 0;
            $user->save();
            $info = new UserInfo();
            $info->user_id = $user->user_id;
            $info->user_lang = $lang;
            $info->save();
            return $this->jsonOk("register successfully",[]);
        }
        else{
            $_ckuser = User::where(['user_name'=>$firstname,'user_lastname'=>$lastname])->first();
            if($_ckuser){
                return $this->jsonErr($this->returnErr($lang,'name'));
            }
            $user->user_name = $firstname;
            $user->user_lastname = $lastname;
            $user->user_type = 1; //个人
            $user->user_status = 1;
            $user->save();
            $info = new UserInfo();
            $info->user_id = $user->user_id;
            $info->user_lang =$lang;
            $info->save();
            \Event::fire(new NotifyEvent(1,$user->user_id));
            $url = 'https://www.novaby.com/sign-in';
             Mail::send('emailtpl.signup', ['user' =>$user->user_name,'url'=>$url], function ($message)use($user){
                 $message->to($user->user_email)->subject('Welcome to Novaby!');
             });
            $wallet = new Wallet();
            $wallet->uid = $user->user_id;
            $wallet->USD = 0.00;
            $wallet->save();
//            $cloud = new UserCloud();
//            $cloud->user_id = $user->user_id;
//            $cloud->have_cloud = 1024*1024*1024*5;    //初始5G容量
//            $cloud->save();
            $token = $this->createToken($user->user_id);
            $user->user_token=$token;
            $user->user_token_time=time();
            $user->save();
            $base_info = [
                'user_id'       =>  $user->user_id,
                'user_name'     =>  $this->getName($user),
                'user_avatar'   =>  $this->getAvatar($user->user_icon),
                'user_type'     =>  $user->user_type,
                'user_email'    =>  $user->user_email,
            ];
            return $this->jsonOk("successfully",['basic_info'=>$base_info,'token'=>$user->user_token]);
//            $url = '';
//            Mail::send('emailtpl.activeAccount', ['user' =>$user->user_name,'url'=>$url], function ($message)use($user){
//                $message->to($user->user_email)->subject('Welcome to Novaby!');
//            });
//            return $this->jsonOk("register successfully, please activate your account via email link",[]);
        }
    }

    /**
     * 激活账号
     * @return mixed
     */
    public function activeAccount()
    {
        $user = User::find(Input::get('id'));
        \Event::fire(new NotifyEvent(1,$user->user_id));
        Mail::send('emailtpl.signup', ['user' =>$user->user_name], function ($message)use($user){
            $message->to($user->user_email)->subject('Welcome to Novaby!');
        });
        $wallet = new Wallet();
        $wallet->uid = $user->user_id;
        $wallet->USD = 0.00;
        $wallet->save();
//            $cloud = new UserCloud();
//            $cloud->user_id = $user->user_id;
//            $cloud->have_cloud = 1024*1024*1024*5;    //初始5G容量
//            $cloud->save();
        $token = $this->createToken($user->user_id);
        $user->user_token=$token;
        $user->user_token_time=time();
        $user->user_status=1;
        $user->save();
        $base_info = [
            'user_id'       =>  $user->user_id,
            'user_name'     =>  $user->company_name?$user->company_name:$user->user_name,
            'user_avatar'   =>  $this->getAvatar($user->user_icon),
            'user_type'     =>  $user->user_type,
            'user_email'    =>  $user->user_email,
        ];
        return $this->jsonOk("successfully",['basic_info'=>$base_info,'token'=>$user->user_token]);
    }
}
