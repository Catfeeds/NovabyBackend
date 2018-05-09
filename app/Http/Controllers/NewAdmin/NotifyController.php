<?php

namespace App\Http\Controllers\NewAdmin;

use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Model\Mail;
use App\Model\Message;
use App\Model\Notify;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class NotifyController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 消息列表
     */
    public function index()
    {
        $notifies = Notify::where('type','!=',5)->orderBy('type','ASC')->get();
        if(count($notifies)!=0) {
            $first = $notifies->first();
            return view('newadmin.notify.index')->with(['notifies'=> $notifies,'first'=>$first]);
        }else
        {
            return view('newadmin.notify.index');
        }
    }

    /**
     * 创建
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('newadmin.notify.create');
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
    public function template(Request $request)
    {
        $type =  Notify::get()->sortByDesc('type')->first()->type;
        if($type>=5) {
            $type = $type+1;
        }else {
            $type = $type+2;
        }
        Notify::create(['title'=>$request->get('title'),'type'=>$type,'content'=>$request->get('content')]);
        return redirect('admin/notify/index');
    }
    /**
     * 发送
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postNotify(Request $request)
    {
        $sql1 = "ALTER TABLE messages ENGINE = MYISAM"; //改变数据表存储引擎，提升存储速度
        DB::statement($sql1);
        $this->validate($request,[
            'content' => 'required'
        ]);
        $notify = new Notify();
        $notify->content = $request->get('content');
        $notify->type = $request->get('type');
        $notify->title = $request->get('user_id');
        $notify->save();
        $value =null;
        if($request->get('user_id') == 'all')
        {
            $user_ids = User::get()->pluck('user_id');
            foreach($user_ids as $user_id)
            {
                $value .= '(0,'.$user_id.',3,'.$notify->id.','.time().'),';
            }
        }else{
            $value .= '(0,'.$request->get('user_id').',3,'.$notify->id.','.time().'),';
        }
        $sql2 = 'insert into messages(msg_from_uid,msg_to_uid,msg_action,msg_rid,msg_time)value'.rtrim($value,',');
        DB::statement($sql2);
        $sql3 = "ALTER TABLE messages ENGINE = InnoDB";  //恢复数据表存储引擎
        DB::statement($sql3);
        return redirect('admin/notify/index');
    }
    /**
     * 保存模版消息
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'content' => 'required'
        ]);
        $notify = Notify::find($request->get('id'));
        $notify->content = $request->get('content');
        $notify->save();
        return ['content'=>$notify->content];
    }
    /**
     * 删除
     * @param $id
     */
    public function destroy($id)
    {

    }

}
