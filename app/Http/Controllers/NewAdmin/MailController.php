<?php

namespace App\Http\Controllers\NewAdmin;

use App\Model\Mail;
use App\Model\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Queue;

class MailController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 首页
     */
    public function index()
    {
        $mails = Mail::where('type','!=',5)->paginate(15);
        if(count($mails)!=0) {
            $first = $mails->first();
            return view('newadmin.mail.index')->with(['mails'=> $mails,'first'=>$first]);
        }else
        {
            return view('newadmin.mail.index');
        }

    }

    /**
     * 创建
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('newadmin.mail.create');
    }

    /**
     * @param $name
     * @return array
     */
    public function getUser($name)
    {
        $users = User::where('user_name','like','%'.trim($name).'%')->get();
        $arr = array();
        for ($i = 0;$i<count($users);$i++)
        {
            $li = '<li>'.'<input type="hidden" name="id" value="'.$users[$i]->user_id.'" id="id"/>'.$users[$i]->user_name.'&nbsp;'.$users[$i]->user_lastname.'</li>';
            $arr[$i] = $li;
        }

        return ['li'=>$arr];
    }

    /**
     * 保存
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'content' => 'required'
        ]);
        $notify = Mail::find($request->get('id'));
        $notify->content = $request->get('content');
        $notify->save();
        return ['content'=>$notify->content];
    }

    public function template(Request $request)
    {

        $type =  Mail::get()->sortByDesc('type')->first()->type;
        if($type>=5) {
            $type = $type+1;
        }else {
            $type = $type+2;
        }
        Mail::create(['title'=>$request->get('title'),'type'=>$type,'content'=>$request->get('content')]);
        return redirect('admin/mail/index');
    }
    /**
     * 发送
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postMail(Request $request)
    {
        $this->validate($request,[
            'content' => 'required'
        ]);
        $mail = new Mail();
        $mail->content = $request->get('content');
        $mail->type = $request->get('type');
        $mail->title = $request->get('user_id');
        $mail->save();
        if($request->get('user_id') == 'all')
        {
            $users = User::all();
            foreach($users as $user)
            {
                \Illuminate\Support\Facades\Mail::send('newadmin.mail.send', ['user' => $user->user_name.$user->user_lastname,'content'=>$mail->content], function ($message)use($user,$mail){
                    $message->to($user->user_email)->subject($mail->content);
                });

            }
        }else{
            $user = User::find($request->get('user_id'));
            \Illuminate\Support\Facades\Mail::send('newadmin.mail.send', ['user' => $user->user_name.$user->user_lastname,'content'=>$mail->content], function ($message)use($user){
                $message->to($user->user_email)->subject("Novaby`s Mail");
            });
        }
        return redirect('admin/mail/index');
    }

    /**
     * 删除
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        Mail::destroy($id);
        return redirect('admin/mail/index');
    }

}
