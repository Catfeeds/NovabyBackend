<?php

namespace App\Http\Controllers\NewAdmin;

use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Model\AuthConfig;
use App\Model\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 认证模型师
     */
    public function index()
    {
        $users = User::where('user_type',2)->orderBy('user_last_login_time','DESC')->paginate(15);
        return view('newadmin.auth.user')->with('users',$users);
    }
    /**
     * 模型师认证通过
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function pass($id)
    {
        $user = User::find($id);
        $user->user_type = 3;
        $user->save();
        \Event::fire(new NotifyEvent(12,$user->user_id));
        $url = env('CLIENT_BASE').'projects';
        Mail::send('emailtpl.authModeler', ['user' =>$user->user_name,'status'=>1,'url'=>$url], function ($message)use($user){
            $message->to($user->user_email)->subject('Congratulations! Your modeler application was successful.');
        });
        return redirect('/admin/auth/modeler');

    }

    /**
     * 模型师认证不通过
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function fail($id)
    {
        $user = User::find($id);
        $user->user_type = 1;
        $user->auth_model = null;
        $user->save();
        $url = env('CLIENT_BASE').'join/artist';
        Mail::send('emailtpl.authModeler', ['user' =>$user->user_name,'status'=>0,'url'=>$url], function ($message)use($user){
            $message->to($user->user_email)->subject('Congratulations! Your modeler application was successful.');
        });
        return redirect('/admin/auth/modeler');
    }
    public function company()
    {
        $users = User::where('user_type',0)->orderBy('user_id','DESC')->paginate(15);
        return view('newadmin.auth.company')->with('users',$users);
    }
    /**
     * 企业认证通过
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function companyPass($id)
    {
        $user = User::find($id);
        $user->user_type = 4;
        $user->user_status = 1;
        $user->save();
        \Event::fire(new NotifyEvent(13,$user->user_id));
        $url = env('CLIENT_BASE').'sign-in';
        Mail::send('emailtpl.authCompany', ['user' =>$user->user_name,'status'=>1,'url'=>$url], function ($message)use($user){
            $message->to($user->user_email)->subject('Congratulations! Your company application was successful.');
        });
        return redirect('/admin/auth/company');

    }

    /**
     * 企业认证不通过
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function companyFail($id)
    {
        $user = User::find($id);
        $url = env('CLIENT_BASE').'business/four';
        Mail::send('emailtpl.authCompany', ['user' =>$user->user_name,'status'=>0,'url'=>$url], function ($message)use($user){
            $message->to($user->user_email)->subject('Congratulations! Your modeler application was successful.');
        });
        User::destroy($id);
        return redirect('/admin/auth/company');
    }
    public function config()
    {
        $introduction = AuthConfig::find(1)->first();
        $functions = AuthConfig::where('id','!=',1)->get();
        return view('newadmin.auth.config')->with(['introduction'=>$introduction,'functions'=>$functions]);
    }
    /**
     * 保存介绍
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function saveIntroduction(Request $request)
    {
        $introduction = AuthConfig::find(1);
        $introduction->content = $request->get('content');
        $introduction->save();
        return redirect('/admin/auth/config');
    }
    /**
     * 保存功能
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function saveFunction(Request $request)
    {
        $function = new AuthConfig();
        $function->content = $request->get('content');
        $function->save();
        return redirect('/admin/auth/config');
    }

}
