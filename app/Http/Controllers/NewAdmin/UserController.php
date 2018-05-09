<?php

namespace App\Http\Controllers\NewAdmin;


use App\Model\Following;
use App\Model\Likes;
use App\Model\User;
use App\Model\UserExplain;
use App\Model\Work;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\libs\ApiConf;
use App\Model\Ossitem;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 普通用户列表
     * @return
     */
    public function index()
    {
        $users = User::where('user_type',1)->orderBy('user_works','DESC')->paginate(20);
        foreach($users as $user)
        {
            $user->user_avatar = $this->getAvatar($user->user_icon);
            $user->likes = Likes::where('like_uid',$user->user_id)->count();
            $user->upload = Work::where('work_uid',$user->user_id)->count();
            $user->following = Following::where('from_uid',$user->user_id)->count();
            $user->follower = Following::where('to_uid',$user->user_id)->count();
        }
        $count = User::where('user_type',1)->count();
        return view('newadmin.user.index')->with(['users'=> $users,'count'=> $count]);
    }

    /**
     * 模型师列表
     * @return
     */
    public function modeler()
    {
        $users = User::where('user_type',3)->paginate(15);
        foreach($users as $user)
        {
            $user->user_avatar = $this->getAvatar($user->user_icon);
            $user->likes = Likes::where('like_uid',$user->user_id)->count();
            $user->upload = Work::where('work_uid',$user->user_id)->count();
            $user->following = Following::where('from_uid',$user->user_id)->count();
            $user->follower = Following::where('to_uid',$user->user_id)->count();
        }
        $count = User::where('user_type',3)->count();
        return view('newadmin.user.modeler')->with(['users'=> $users,'count'=> $count]);
    }

    /**
     * 企业用户列表
     * @return
     */
    public function company()
    {
        $users = User::where('user_type',4)->paginate(15);
        foreach($users as $user)
        {
            $user->user_avatar = $this->getAvatar($user->user_icon);
            $user->likes = Likes::where('like_uid',$user->user_id)->count();
            $user->upload = Work::where('work_uid',$user->user_id)->count();
            $user->following = Following::where('from_uid',$user->user_id)->count();
            $user->follower = Following::where('to_uid',$user->user_id)->count();
        }
        $count = User::where('user_type',4)->count();
        return view('newadmin.user.company')->with(['users'=> $users,'count'=> $count]);
    }
    /**
     * @param Request $request
     * @return array
     */
    public function checkDate(Request $request)
    {
        return [
            'date'=>$request->get('date'),
            'type'=>$request->get('type')
        ];
    }

    /**
     * 日期查询
     * @param $date
     * @return
     */
    public function search($date,$type)
    {
        $users = User::where('user_register_time','like','%'.trim($date).'%')->where('user_type',$type)->paginate(15);
        foreach($users as $user)
        {
            $user->likes = Likes::where('like_uid',$user->user_id)->count();
            $user->upload = Work::where('work_uid',$user->user_id)->count();
            $user->following = Following::where('from_uid',$user->user_id)->count();
            $user->follower = Following::where('to_uid',$user->user_id)->count();
        }
        $count = User::where('user_register_time','like','%'.trim($date).'%')->where('user_type',$type)->count();
        if($type==1)
        {
            return view('newadmin.user.index')->with(['users'=> $users,'count'=> $count,'date'=>$date]);
        }elseif($type==3)
        {
            return view('newadmin.user.modeler')->with(['users'=> $users,'count'=> $count,'date'=>$date]);
        }
        else{
            return view('newadmin.user.company')->with(['users'=> $users,'count'=> $count,'date'=>$date]);
        }

    }

    /**
     * 推荐
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function recommend(Request $request)
    {
        $this->validate($request,[
            'id' => 'required',
            'explain'=> 'required'
        ]);
        $user_explain = new UserExplain();
        $user_explain->uid = $request->get('id');
        $user_explain->explain = $request->get('explain');
        $user_explain->save();
        return redirect('/admin/user/index');
    }

    /**
     * 默认头像
     * @param $id
     * @param $type
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function  defaultAvatar($id,$type)
    {
        $user = User::find($id);
        $user->user_icon = 0;
        $user->save();
        if($type==1)
        {
            return redirect('/admin/user/index');
        }elseif($type==2)
        {
            return redirect('admin/modeler/index');
        }else{
            return redirect('/admin/company/index');
        }

    }

    /**
     * 获取用户头像
     * @param $id
     * @param string $size
     * @return string
     */
    public function getAvatar($id,$size=''){

        if(!$id){
            return env('APP_URL').ApiConf::DEFAULT_IMG;
        }else{
            $item = Ossitem::find($id);
            return ApiConf::IMG_URI.$item->oss_path.$size;
        }
    }

    /**
     * 展示合作伙伴
     * @param $id
     * @param $type
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function becomePartner($id,$type)
    {
        $user = User::find($id);
        $user->is_partner = $user->is_partner==1?0:1;
        $user->save();
        if($type==1)
        {
            return redirect('/admin/user/index');
        }elseif($type==2)
        {
            return redirect('admin/modeler/index');
        }else{
            return redirect('/admin/company/index');
        }
    }
}
