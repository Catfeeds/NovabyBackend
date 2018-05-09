<?php

namespace App\Http\Controllers\Api;

use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\libs\QiNiuManager;
use App\libs\StaticConf;
use App\Model\CommentReport;
use App\Model\Field;
use App\Model\Following;
use App\Model\Ossitem;
use App\Model\Reason;
use App\Model\User;
use App\Model\UserInfo;
use App\Model\UserPlan;
use App\Model\Wallet;
use App\Model\Work;
use App\Model\WorkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use \Validator;
use DB;
use Storage;
use App\libs\Tools;
use App\libs\OSSManager;

class ProfileApiController extends BaseApiController
{
    public function save_bak(Request $req)
    {
        $firstname = $req->get('firstname');
        $lastname = $req->get('lastname');
        $gender = $req->get('gender');
        $country = $req->get('country');
        $job = $req->get('job');
        $work_exp = $req->get('work_exp');
        $home_id = $req->get('homeid');
        $fileds = $req->get('fileds');
        $usertype = $req->get('usertype');
        $firstname_sign = $req->get('firstname_sign');
        $lastname_sign = $req->get('lastname_sign');
        $company_name = $req->get('company_name');
        $company_vat = $req->get('company_vat');
        $company_country = $req->get('company_country');
        $company_city = $req->get('company_city');
        $company_address = $req->get('company_address');

        $rules = [
            'firstname' => 'required|between:5,20',
            'lastname' => 'required|between:5,20',
            'gender' => 'required',
            'country' => 'required',
            'job' => 'required',
            'work_exp' => 'required',
            'homeid' => 'required',
            'fileds' => 'required',


        ];
        $rules_a = [];
        if ($usertype == 1) {
            $rules_a = [
                'firstname_sign' => 'required',
                'lastname_sign' => 'required',

            ];

        } elseif ($usertype == 2) {
            $rules_a = [
                'company_name' => 'required|between:2,100',
                'company_vat' => 'required',
                'company_country' => 'required',
                'company_city' => 'required',
                'company_address' => 'required',
            ];
        }
        $rules_f = array_merge($rules, $rules_a);
        $message = [
            "required" => ":attribute can\'t be blank",
            "between" => ":attribute must be :min and :max"
        ];

        $attributes = [
            "firstname" => 'firstname',
            'lastname' => 'lastname',
            'gender' => 'gender',
            'country' => 'country',
            "job" => 'job',
            'work_exp' => 'work experience',
            'homeid' => 'home page',
            'fileds' => 'professionnal fileds',
            "usertype" => 'user type',
            'firstname_sign' => 'firstname sign',
            'lastname_sign' => 'lastname sign',
            'company_name' => 'company name',
            'company_vat' => 'company vat',
            'company_country' => 'company country',
            'company_city' => 'company_city',
            'company_address' => 'company address',

        ];


        $validator = Validator::make(
            $req->all(),
            $rules_f,
            $message,
            $attributes
        );
        if ($validator->fails()) {
            $warnings = $validator->messages();
            $show_warning = $warnings->first();
            return $this->jsonErr($show_warning);
        }

        $user = User::find($this->_user->user_id);
        $user->user_name = $firstname;
        $user->user_lastname = $lastname;
        $user->user_gender = $gender;
        $user->user_country = $country;
        $user->user_job = $job;
        $user->user_work_exp = $work_exp;
        $user->user_page_id = $home_id;
        $user->user_fileds = $fileds;
        if ($usertype == 1) {
            $user->user_sign_firstname = $firstname_sign;
            $user->user_sign_lastname = $lastname_sign;
        } elseif ($usertype == 2) {
            $user->user_company_name = $company_name;
            $user->user_conmany_vat = $company_vat;
            $user->user_comany_city = $company_country;
            $user->user_comany_country = $company_city;
            $user->user_comany_address = $company_address;
        }
        if ($user->save()) {
            return $this->jsonOk('update successfully', ['result' => 'ok']);
        } else {
            return $this->jsonErr('update failed', []);
        }
    }

    /**
     * 保存个人信息
     * @param Request $req
     * @return mixed
     */
    public function save(Request $req)
    {
        $firstname = $req->get('firstname', NULL);
        $lastname = $req->get('lastname', NULL);
        $gender = $req->get('gender');
        $country = $req->get('country');
        $city = $req->get('city');
        $company_name = $req->get('company_name');
        $company_size = $req->get('company_size') ? $req->get('company_size') : 0;
        $year_founded = $req->get('year_founded') ? $req->get('year_founded') : '';
        $hourly = $req->get('hourly', NULL);
        $english = $req->get('english', NULL);
        $company_type = $req->get('company_type') ? $req->get('company_type') : 0;
        $job = $req->get('job') ? $req->get('job') : 0;
        $description = $req->get('description');
        $work_exp = $req->get('work_exp') ? $req->get('work_exp') : 0;
        $home_id = $this->_user->user_type == 4 ? $this->inputUrl($req->get('homeid')): $req->get('homeid', NULL);
        if ($home_id) {
            $ck_home_id = User::where('user_page_id', $home_id)->where('user_id', "!=", $this->_user->user_id)->count();
            if ($ck_home_id) {
                return $this->jsonErr($this->returnErr($this->lang,'page'));
            }
        }
        if ($firstname) {
            $ck_name = User::where(['user_name' => $firstname, 'user_lastname' => $lastname])->where('user_id', "!=", $this->_user->user_id)->count();
            if ($ck_name) {
                return $this->jsonErr($this->returnErr($this->lang,'name'));
            }
        }
        if ($company_name) {
            $ck_compamy_name = User::where(['company_name' => $company_name])->where('user_id', "!=", $this->_user->user_id)->count();
            if ($ck_compamy_name) {
                return $this->jsonErr($this->returnErr($this->lang,'company'));
            }
        }
        $fileds = $req->get('fileds');
        if ($fileds) {
            $fileds = implode(",", $fileds);
        }
        if (count($fileds) > 3) {
            return $this->jsonErr($this->returnErr($this->lang,'field'));
        }
        if ($this->_user->user_type == 4) {             //企业
            $rules = [
                'country' => 'required',
                'city' => 'required',
                'fileds' => 'required',
                'year_founded' => 'required|size:4',
                'homeid' => 'required'
            ];
        } elseif ($this->_user->user_type == 3) {        //模型师
            $rules = [
                'gender' => 'required',
                'country' => 'required',
                'work_exp' => 'required',
                'fileds' => 'required',
                'city' => 'required',
                'english' => 'required',
                'hourly' => 'required',
            ];
        } else {                                      //普通账号
            if ($firstname) {     //申请成为认证模型师
                $rules = [
                    'firstname' => 'required|between:2,100',
                    'lastname' => 'required|between:2,100',
                    'gender' => 'required',
                    'country' => 'required',
                    'city' => 'required',
                    'hourly' => 'required',
                ];
            } else {          //保存信息
                $rules = [
                    'gender' => 'required',
                    'country' => 'required',
                    'city' => 'required',
                ];
            }
        }
        $request = $req->all();
        $request['homeid'] = $home_id;
        $result = $this->postValidate($request,$rules);
        if($result) {
            return $this->jsonErr($result);
        }
        $user = User::find($this->_user->user_id);
        $user->user_name = $firstname ? $firstname : $user->user_name;
        $user->user_lastname = $lastname ? $lastname : $user->user_lastname;
        $user->user_gender = $gender;
        $user->user_country = $country;
        $user->user_job = $job;
        $user->user_work_exp = $work_exp;
        $user->user_page_id = $home_id;
        $user->user_fileds = $fileds;
        $user->user_description = $description;
        $user->user_city = $city;
        $user->hourly_rate = $hourly;
        $user->english_level = $english;
        $user->company_size = $company_size;
        $user->company_type = $company_type;
        $user->year_founded = $year_founded;
        $user->company_name = $company_name;
        $cover = $req->get('cover');
        if ($user->cover >= 0 && strlen($cover) < 4) {
            $top = $req->get('top');
            $left = $req->get('left');
            $user->cover_top = $top;
            $user->cover_left = $left;
        } else {
            $top = $req->get('top');
            $left = $req->get('left');
            $item = new Ossitem();
            $item->oss_key = 'elements';
            $item->oss_path = $cover;
            $item->oss_item_uid = $this->_user->user_id;
            if ($item->save()) {
                $user->cover = $item->oss_item_id;
                $user->cover_top = $top;
                $user->cover_left = $left;
            }
        }
        if ($user->save()) {
            return $this->jsonOk('update successfully', ['result' => 'ok']);
        } else {
            return $this->jsonErr('update failed', []);
        }


    }

    public function userInfo(Request $req)
    {
        $user = User::find($this->_user->user_id);
        if (!$user) {
            return $this->jsonErr('not found');
        }
        if ($user->user_fileds == '') {
            $_user_fileds = [];
        } else {
            $_user_fileds = explode(",", $user->user_fileds);
        }
        $user->company_size = $user->company_size ? $user->company_size : 0;
        $user->company_type = $user->company_type ? $user->company_type : 0;
        switch ($this->lang){
            case 'zh':
                $gender = [
                    0=>['id'=>0,'name'=>'保密'],
                    1=>['id'=>1,'name'=>'男'],
                    2=>['id'=>2,'name'=>'女'],
                ];
                $company_size = StaticConf::$company_size_zh;
                $company_type = StaticConf::$company_type_zh;
                $english_level =  [
                    0=>['id'=>0,'name'=>'不限制英语水平'],
                    1=>['id'=>1,'name'=>'初级英语水平'],
                    2=>['id'=>2,'name'=>'可以对话'],
                    3=>['id'=>3,'name'=>'流利'],
                    4=>['id'=>4,'name'=>'英语母语'],
                ];
                $work_exp = [
                    1=>['id'=>1,'name'=>'1-3年'],
                    2=>['id'=>2,'name'=>'3-5年'],
                    3=>['id'=>3,'name'=>'超过5年'],
                ];
                $hourly_rate = [
                    0=>['id'=>0,'name'=>'不限制时薪'],
                    1=>['id'=>1,'name'=>'不高于10美元'],
                    2=>['id'=>2,'name'=>'10美元-30美元'],
                    3=>['id'=>3,'name'=>'30美元-60美元'],
                    4=>['id'=>4,'name'=>'60美元及其以上'],
                ];
                break;
            default:
                $gender = [
                    0=>['id'=>0,'name'=>'It\'s private'],
                    1=>['id'=>1,'name'=>'Male'],
                    2=>['id'=>2,'name'=>'Female'],
                ];
                $company_size = StaticConf::$company_size;
                $company_type = StaticConf::$company_type;
                $english_level =  [
                    0=>['id'=>0,'name'=>'Any level'],
                    1=>['id'=>1,'name'=>'Basic'],
                    2=>['id'=>2,'name'=>'Conversational'],
                    3=>['id'=>3,'name'=>'Fluent'],
                    4=>['id'=>4,'name'=>'Native or bilingual'],
                ];
                $work_exp = [
                    1=>['id'=>1,'name'=>'1-3 year'],
                    2=>['id'=>2,'name'=>'3-5 year'],
                    3=>['id'=>3,'name'=>'more than 5 year'],
                ];
                $hourly_rate = [
                    0=>['id'=>0,'name'=>'Any hourly rate'],
                    1=>['id'=>1,'name'=>'$10 and below'],
                    2=>['id'=>2,'name'=>'$10 - $30'],
                    3=>['id'=>3,'name'=>'$30 - $60'],
                    4=>['id'=>4,'name'=>'$60 and above'],
                ];
                break;
        }
        if($this->_user->user_type==4)
        {
            $user_select = [
                'english_level' => $english_level,
                'company_size'  => $company_size,
                'company_type'  => $company_type
            ];
        }else{
            $user_select = [
                'gender'         =>  $gender,
                'english_level' => $english_level,
                'hourly_rate'   => $hourly_rate,
                'work_exp'      => $work_exp
            ];
        }
        $_data = [
            'userid' => $user->user_id,
            'firstname' => $user->user_name,
            'lastname' => $user->user_lastname,
            'gender' => $user->user_gender,
            'cover' => $user->cover != 0 ? $this->getOssPath($user->cover, '1000') : $_SERVER['APP_URL'] . '/images/personal-default.png',
            'cover_top' => $user->cover_top == null ? 0 : $user->cover_top,
            'cover_left' => $user->cover_left == null ? 0 : $user->cover_left,
            'country' => $user->user_country ? $user->country : '',
            'job' => $user->job?$user->job : '',
            'work_exp' => $user->user_work_exp?$user->user_work_exp:0,
            'homepage' => $user->user_page_id ? $user->user_page_id : '',
            'fields' => $_user_fileds,
            'avatar' => $this->getAvatar($user->user_icon),
            'user_type' => $user->user_type,
            'city' => $user->user_city ? $user->city : '',
            'company_name' => $user->company_name,
            'company_size' => $user->company_size?$user->company_size:0,
            'year_founded' => !empty($user->year_founded)?$user->year_founded : '',
            'company_type' => $user->company_type?$user->company_type:0,
            'user_description' => $user->user_description,
            'english_level' => $user->english_level?$user->english_level:0,
            'hourly_rate' => $user->hourly_rate?$user->hourly_rate:0,
        ];
        $_data['user_facebook'] = $user->user_facebook_id ? 1 : 0;
        $_data['user_twitter'] = $user->user_twitter_id ? 1 : 0;
        $_data['user_linkedin'] = $user->user_linkedin_id ? 1 : 0;
        $_data['user_insgram'] = 0;
        $_data['user_pinterest'] = $user->user_pinterest_id ? 1 : 0;
        return $this->jsonOk('ok', ['user' => $_data,'user_select'=>$user_select]);

    }

    /**
     * 钱包余额和paypal账户信息
     * @return mixed
     */
    public function payInfo()
    {
        $user = User::find($this->_user->user_id);
        if (!$user) {
            return $this->jsonErr('not found');
        }
        $wallet = Wallet::where('uid', $this->_user->user_id)->first();
        if ($wallet) {
            $data = [
                'paypal_email' => $user->paypal_email ? $user->paypal_email : '',
                'paypal_name' => $user->paypal_name ? $user->paypal_name : '',
                'wallet' => $wallet->USD
            ];
        } else {
            $wallet = new Wallet();
            $wallet->uid = $user->user_id;
            $wallet->USD = 0.00;
            $wallet->save();
            $data = [
                'paypal_email' => $user->paypal_email ? $user->paypal_email : '',
                'paypal_name' => $user->paypal_name ? $user->paypal_name : '',
                'wallet' => $wallet->USD
            ];

        }
        return $this->jsonOk('ok', ['data' => $data]);

    }

    public function changepasswd(Request $req)
    {
        $opass = $req->get('opass');
        $newpass = $req->get('newpass');
        $newpassrepeat = $req->get('newpass_confirmation');
        $rules = array(
            'opass' => 'required',
            'newpass' => 'required|between:5,20|confirmed',
            'newpass_confirmation' => 'required|between:5,20',
        );
        $message = array(
            "required" => ":attribute can't be blank",
            "between" => ":attribute must be :min and :max"
        );

        $attributes = array(
            "opass" => 'original password ',
            'newpass' => 'new password',
            'newpass_confirmation' => 'password confirmation',

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
        $user = User::find($this->_user->user_id);
        if (!$user) {
            return $this->jsonErr('not found');
        }
        if ($user->user_password != md5($opass)) {
            return $this->jsonErr('origin password wrong');
        }
        $user->user_password = md5($newpass);
        if ($user->save()) {
            \Event::fire(new NotifyEvent(3, $user->user_id));
            \Event::fire(new MailEvent(3, $user));
            return $this->jsonOk('update password successfully', []);
        } else {
            return $this->jsonErr('update failed');
        }
    }

    /**
     * 保存paypal信息
     */
    public function savePay(Request $request)
    {
        $user = User::find($this->_user->user_id);
        if (!$user) {
            return $this->jsonErr('not found');
        }
        $this->validate($request, [
            'paypal_email' => 'required|email',
            'paypal_name' => 'required'
        ]);
        $user->paypal_email = $request->get('paypal_email');
        $user->paypal_name = $request->get('paypal_name');
        if ($user->save()) {
            return $this->jsonOk('update payment Account successfully', []);
        } else {
            return $this->jsonErr('error');
        }

    }

    public function recommend()
    {
        $followed = [];
        $has_follows = DB::table('following')->select('to_uid')->where(['from_uid' => $this->_user->user_id, 'followed' => 1])->get();

        foreach ($has_follows AS $k => $v) {
            $followed[] = $v->to_uid;
        }
        $users = User::select('user_id', 'user_icon', 'user_name', 'user_type', 'user_lastname', 'user_job', 'user_country', 'company_name', 'user_city')->whereNotIn('user_id', $followed)->limit(15)->get();
        foreach ($users AS $k => $v) {
            if ($v->user_type != 4) {
//                $users[$k]->user_job=$v->job->name;
            }
            $users[$k]->user_country = $v->user_country ? $v->country->name : '';
            if ($v->user_type == 4) {
                $users[$k]->user_name = $v->company_name;
                unset($users[$k]->user_job);
            }
            $users[$k]->user_city = $v->user_city ? $v->city->name : '';
            $users[$k]->user_avatar = $this->getAvatar($v->user_icon, '100');
        }
        return $this->jsonOk('ok', ['users' => $users]);


    }


    public function basicInfo()
    {
        $base_info = [
            'user_id' => $this->_user->user_id,
            'user_name' => $this->getName($this->_user),
            'user_avatar' => $this->getAvatar($this->_user['user_icon'], '100'),
            'user_type' => $this->_user->user_type,
            'user_email' => $this->_user->user_email,
            'user_country' => $this->_user->country ? $this->_user->country->name : '',
            'user_city' => $this->_user->city ? $this->_user->city->name : '',
            'user_work' => $this->_user->Job ? $this->_user->Job->name : '',
            'wallet' => $this->_user->wallet ? $this->_user->wallet->USD : 0,

        ];
        $lang = UserInfo::where('user_id',$this->_user->user_id)->first();
        if ($lang){
            $base_info['user_lang'] = $lang->user_lang;
            unset($lang);
        }else{
            $base_info['user_lang'] = 'en';
        }
        return $this->jsonOk("successfully", ['basic_info' => $base_info]);

    }

    public function editIcon(Request $req)
    {

        $pic = $req->get('pic', '');
        $ext = $req->get('ext', '');
        $exts = [
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/jpeg' => 'jpeg',
            'image/gif' => 'gif'
        ];
        if (!$pic) {
            return $this->jsonErr("no pic");
        }
        if (!$ext) {
            return $this->jsonErr("error");
        }

        if (!key_exists($ext, $exts)) {
            return $this->jsonErr("file error");
        }
        $ext = $exts[$ext];
        $save_path = $this->_user ? $this->_user->user_id . '.' . $ext : rand(100000, 99999999);
        $pics = explode("base64,", $pic);
        $con = base64_decode($pics[1]);
        if ($con) {
            Storage::disk('tmp')->put($save_path, $con);
            //$ossmgr = new OSSManager();
            $oss_base_path = date('YmdHis/');
            $target_id = Tools::guid();
            $oss_file = $target_id . '.' . $ext;
            $oss_zip_path = $oss_base_path . $oss_file;
            $zip_path = Storage::disk('tmp')->getAdapter()->getPathPrefix() . $save_path;
            $ossmgr = new QiNiuManager(0);
            $result = $ossmgr->upload($oss_zip_path,$zip_path);
            unlink($zip_path);
            if (!$result['error']) {
                $_tid = DB::table('oss_item')->insertGetId([
                    'oss_key' => $ossmgr->key,
                    'oss_path' => $oss_zip_path,
                    'oss_item_uid' => $this->_user ? $this->_user->user_id : 1,
                    'size' => 0
                ]);
                if ($req->get('key') == 1) {
                    return $this->jsonOk("update successfully", ['icon' => $_tid]);
                } else {
                    $user = User::find($this->_user->user_id);
                    $user->user_icon = $_tid;
                    if ($user->save()) {
                        return $this->jsonOk("update successfully", []);
                    } else {
                        return $this->jsonErr("update Error");
                    }
                }

            } else {
                return $this->jsonErr("update Error");
            }
        } else {
            return $this->jsonErr("no pic found");
        }
    }

    public function editIcon1(Request $req)
    {
        $pic = $req->get('pic', '');
        if (!$pic) {
            return $this->jsonErr("no pic");
        }
        $item = new Ossitem();
        $item->oss_key = 'elements';
        $item->oss_path = $pic;
        $item->oss_item_uid = $this->_user->user_id;
        $item->size = 0;
        if ($item->save()) {
            $icon_id = $item->oss_item_id;
            $user = User::find($this->_user->user_id);
            $user->user_icon = $icon_id;
            if ($user->save()) {
                return $this->jsonOk("update successfully", []);
            } else {
                return $this->jsonErr("update Error");
            }
        }
        {
            return $this->jsonErr("update Error");
        }
    }

    /**
     * 获取举报原因
     * @return mixed
     */
    public function getReason()
    {
        $type = Input::get('type');
        $reasons = Reason::select('id', 'content')->where(['type' => $type, 'display' => 1])->get();
        if ($reasons != null) {
            return $this->jsonOk('ok', ['data' => $reasons]);
        } else {
            return $this->jsonOk('error', 'not have reason');
        }

    }

    /**
     * 举报
     * @param Request $request
     * @return mixed
     */
    public function report(Request $request)
    {
        if ($request->get('type') == 1) {
            $report = new WorkReport();
            $report->from_uid = $this->_user->user_id;
            $report->work_id = $request->get('id');
            $report->status = 0;
            if ($request->get('reason') != null) {
                $report->reason_id = $request->get('reason');
            } else {
                $report->content = $request->get('content');
            }
            if ($report->save()) {
                return $this->jsonOk('ok', ['mag' => 'report successful!']);
            } else {
                return $this->jsonErr('error', 'error');
            }
        } else {
            $report = new CommentReport();
            $report->from_uid = $this->_user->user_id;
            $report->comm_id = $request->get('id');
            $report->status = 0;
            if ($request->get('reason') != null) {
                $report->reason_id = $request->get('reason');
            } else {
                $report->content = $request->get('content');
            }
            if ($report->save()) {
                return $this->jsonOk('ok', ['mag' => 'report successful!']);
            } else {
                return $this->jsonErr('error', 'error');
            }
        }
    }

    /**
     * 企业
     * @return mixed
     */
    public function partner()
    {
        $page_size = Input::get('pagesize');
        $page = Input::get('page');
        $act = Input::get('act',0);
        $offset = ($page - 1) * $page_size;
        if($act==1){
            $where = 'user_type=4 and is_partner=1';
        }else{
            $where = 'user_type=3';
        }
        $users = User::select('user_icon', 'company_name','user_name','user_lastname', 'user_type', 'user_city', 'user_id', 'user_country', 'user_description', 'user_fileds')
            ->whereRaw($where)
            ->skip($offset)
            ->take($page_size)
            ->orderBy('user_works', 'desc')
            ->orderBy('user_id', 'desc')
            ->get();
        if (count($users) > 0) {
            $work_num = 3;
            $_w = 200;
            $_q = 90;
            if ($this->Mobile()) {
                $work_num = 3;
                $_w = 680;
                $_q = 70;
            }
            foreach ($users as $user) {
                if($user->user_type==3)
                {
                    $user->user_name = $user->user_name." ".$user->user_lastname;
                }else{
                    $user->user_name = $user->company_name;
                }
                $user->user_description = $user->user_description != null ? $user->user_description : '';
                $user->user_avatar = $this->getAvatar($user->user_icon, '100');
                $dict_field = [];
                $fields = explode(",", $user->user_fileds);
                if (count($fields)) {
                    $dict_field = Field::whereIn('id', $fields)->get();
                }
                switch ($this->lang)
                {
                    case 'zh':
                        $dict_fields = [];
                        foreach ($dict_field AS $k => $v) {
                            $dict_fields[] = $v->name_cn;
                        }
                        $user->user_country = !empty($user->user_country) ? $user->country->name : '';
                        $user->user_city = !empty($user->user_city) ? $user->city->name : '';
                        break;
                    default:
                        $dict_fields = [];
                        $user->user_country = !empty($user->user_country) ? $user->country->name : '';
                        $user->user_city = !empty($user->user_city) ? $user->city->name : '';
                        foreach ($dict_field AS $k => $v) {
                            $dict_fields[] = $v->name;
                        }
                        break;
                }
                $user->user_fields = $dict_fields;
                $user->follow = 0;
                if ($this->_user) {
                    $user->follow = Following::where(['from_uid' => $this->_user->user_id, 'to_uid' => $user->user_id, 'followed' => 1])->count()>0?1:0;
                }
                $works = Work::select('work_cover', 'work_model', 'work_id')->where('work_uid', $user->user_id)->where('work_privacy', 0)->where('work_del',0)->where('work_status', 1)->orderBy('work_id', 'DESC')->take($work_num)->get();
                foreach ($works as $item) {
                    $item->work_cover = $this->getOssPath($item->work_cover,'300');
                    $item->work_model = $item->work_model != 0 ? 1 : 0;
                }
                $user->work = $works;
                unset($user->user_lastname,$user->company_name,$user->user_icon,$user->country,$user->city);
            }
            return $this->jsonOk('ok', ['users' => $users]);
        } else {
            return $this->jsonErr('error', 'no more data');
        }

    }

    /**
     * 获取企业作品
     * @return string
     */
    public function getPartnerWork()
    {
        $id = Input::get('id');
        $page_size = Input::get('pagesize');
        $page = Input::get('page');
        $offset = ($page - 1) * $page_size + 2;
        $work = Work::select('work_cover', 'work_model', 'work_id')->orderBy('work_id', 'DESC')
            ->where('work_uid', $id)
            ->where('work_status', 1)
            ->skip($offset)
            ->take($page_size)
            ->get();
        if (count($work) > 0) {
            foreach ($work as $item) {
                $item->work_cover = $this->getOssPath($item->work_cover, '200');
                $item->work_model = $item->work_model != 0 ? 1 : 0;
            }
            return $this->jsonOk('ok', ['work' => $work]);
        } else {
            return $this->jsonErr('error', 'no more data');
        }
    }

    /**
     * 保存语言
     * @param Request $request
     */
    public function saveLang(Request $request)
    {
        $lang = $request->input('lang');
        $info = UserInfo::where('user_id',$this->_user->user_id)->first();
        if($info)
        {
            $info->user_lang = $lang;
        }else{
            $info = new UserInfo();
            $info->user_id=$this->_user->user_id;
            $info->user_lang = $lang;
        }
        $result = $info->save();
        if($result){
            return $this->jsonOk('ok','save successfully');
        }else{
            return $this->jsonErr('error','save error');
        }
    }

//    /**
//     * 判断用户输入的是否为url
//     * @param $url
//     * @return bool|string
//     */
//    private function inputUrl($url)
//    {
//        if(preg_match('/(http:\/\/|https:\/\/)+www.[0-9a-zA-Z]+_?[0-9a-zA-Z]+.[0-9a-zA-Z]{2,}$/',$url)) {
//            return  $url;
//        }elseif(preg_match('/^www.[0-9a-zA-Z]+_?[0-9a-zA-Z]+.[0-9a-zA-Z]{2,}$/',$url)){
//            return 'http://'.$url;
//        }elseif(preg_match('/[0-9a-zA-Z]+_?[0-9a-zA-Z]+.[0-9a-zA-Z]{2,}$/',$url)){
//            return 'http://www.'.$url;
//        }else{
//            return false;
//        }
//    }
}
