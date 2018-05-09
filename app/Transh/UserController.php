<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use App\Model\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\PdaData;
use DB;
use Illuminate\Support\Facades\Redirect;
use Session;

use App\libs\Tools;
use Illuminate\Support\Facades\Input;
use Mail;

class UserController extends Controller
{
  private function cklogin($info){
      if(!$info || !$info->user_id){
          header('Location: /#login');
          exit;
      }

  }
  public function getComments($id=0,$page=1){
      $timeFormat = isset($_COOKIE['lang'])?'m-d H:i':'j,F H:i A';
      $timeFormat1 = isset($_COOKIE['lang'])?'H:i':'H:i A';

      $id=Input::get('id',0);
      $page=Input::get('page',1);
      if($id<0) exit;
      $page_size=20;
      $offset=($page-1)*$page_size;
      $tot=DB::table('comments')->where('comment_eid',$id)->count();
      $comments=DB::table('comments')->where('comment_eid',$id)->skip($offset)->take($page_size)->orderBy('comment_id','DESC')->get();
      $todayTime= mktime(0,0,0,date("m"),date("d"),date("Y"));
      foreach($comments AS $k=>$v){
          $user_icon=DB::table('user')->select('user_icon','user_ismodeler')->where('user_id',$v->comment_uid)->first();
          $comments[$k]->user_icon=$user_icon->user_icon?ApiConf::IMG_URI.$this->getOssPath($user_icon->user_icon)->oss_path:'';
          $comments[$k]->ism = $user_icon->user_ismodeler?1:0;
          $user=DB::table('user')->where('user_id',$v->comment_uid)->first();
          $comments[$k]->user_name=$user->user_name.' '.$user->user_lastname;
          $commtime=strtotime($v->comment_create_time);
          $comments[$k]->comment_create_time= $commtime<$todayTime ?date($timeFormat,$commtime):date($timeFormat1,$commtime);
      }
      return response()->json(['count'=>$tot,'comment_list'=>$comments]);
  }
  public function dashboard( Request $request)
  {
    $totalNovaView = PdaData::count();
    $totalInteraction = PdaData::sum('mld_time');
    $data = [];
    $data['totalNova'] = 4;
    $data['totalNovaView'] = $totalNovaView;
    $data['totalNovaInteraction'] = $totalInteraction;
    $data['demo001View'] = PdaData::where('mld_target_id', 'demo001')->count();
    $data['demo002View'] = PdaData::where('mld_target_id', 'demo002')->count();
    $data['demo003View'] = PdaData::where('mld_target_id', 'demo003')->count();
    $data['demo004View'] = PdaData::where('mld_target_id', 'demo004')->count();
    return view('user.dashboard', $data);
  }


  public function edit(Request $request){
    header("Content-type:application/json;charset=utf-8");
    $userInfo=Session::get('userInfo',null);
      if(!$userInfo){
          echo json_encode(['code'=>-1,'msg'=>'not login']);
          exit;
      }
    $phone=$request->get('phone');
    $email=$request->get('email');
    $ck_res=DB::table('user')->where('user_id','!=',$userInfo->user_id)->where('user_email',$email)->count();
    if($ck_res>0){
      echo json_encode(['code'=>-1,'msg'=>'email exists!']);
      exit;
    }
    $edit_data=['user_email'=>$email,'user_tel'=>$phone];
    $res=DB::table('user')->where('user_id',$userInfo->user_id)->update($edit_data);
    if($res){
      echo json_encode(['code'=>0,'msg'=>'success']);
    }else{
      echo json_encode(['code'=>-1,'msg'=>'server error!']);
    }
    
  }
  public function editPrice(Request $request){
    header("Content-type:application/json;charset=utf-8");
    $userInfo=Session::get('userInfo',null);
      if(!$userInfo){
          echo json_encode(['code'=>-1,'msg'=>'not login']);
          exit;
      }
    $id=$request->get('id');
    $price=$request->get('price');
    $edit_data=['element_price'=>$price];
    $res=DB::table('element')->where('element_id',$id)->where('user_id',$userInfo->user_id)->update($edit_data);
    if($res){
      echo json_encode(['code'=>0,'msg'=>'success']);
    }else{
      echo json_encode(['code'=>0,'msg'=>'error']);
    }
  }

  public function analytics( Request $request, $name)
  {
    $data = [];
    $data['targetName'] = $name;
    $data['tatalView'] = PdaData::where('mld_target_id', $data['targetName'])->count();
    $data['tatalInteraction'] = PdaData::where('mld_target_id', $data['targetName'])->sum('mld_time');

    $dateData = DB::select('SELECT sum(mld_time) as st, count(data_time) as cv, dayofmonth(data_time) as t FROM pda_data WHERE mld_target_id = "'.$data['targetName'].'" GROUP BY t');

    $data['dateViewData'] = '[';
    $data['dateInteractionData'] = '[';
    $data['dateTickData'] = '[';
    for ( $i = 10 ; $i < date('d') + 1 ; $i ++) {
      $tmpView = 0;
      $tmpInter = 0;
      foreach( $dateData as $v) {
        if( $v->t == $i) {
          $tmpView = $v->cv;
          $tmpInter = $v->st;
        }
      }
      $data['dateViewData'] .= " [$i, $tmpView],";
      $data['dateInteractionData'] .= " [$i, $tmpInter],";
      $data['dateTickData'] .= " [$i, '$i'],";
    }
    $data['dateTickData'] .= ']';
    $data['dateViewData'] .= ']';
    $data['dateInteractionData'] .= ']';

    $cateData = DB::select('SELECT sum(mld_time) as st, count(data_time) as cv, sum(mld_time) / count(data_time) as pda, mld_media_type, mld_media_name FROM pda_data WHERE mld_target_id = "'.$data['targetName'].'" GROUP BY mld_media_type, mld_media_name ORDER BY pda DESC');

    $data['cateData']['data'] = $cateData;

    return view('user.analytics', $data);
  }
  public function registerForm1(){
		return view('auth.register',['user'=>null,'cart_info'=>[],'notices'=>[],'title'=>'Register']);
	}

  public function registerForm(){
      $info=Session::get('userInfo',null);
      if($info){
          return redirect("/");
          exit;
      }
      $invite_code = Input::get('invite','');
      $ck_data = DB::table('invite')->where(['code'=>$invite_code,'accept'=>null])->orderBy('id','DESC')->first();
      if($ck_data){
          $inviter = DB::table('user')->where(['user_id'=>$ck_data->uid])->select('user_email')->first();
          

          $ck_data->inviter = $inviter->user_email;
      }


        return view('auth.registerNew',['user'=>null,'cart_info'=>[],'notices'=>[],'data'=>$ck_data,'title'=>'Register']);
  }
  public function store(Request $request){
		$regRule=[
			'username'=>'required',
			'password'=>'required',
			'email'=>'email',
			'type'=>'required'
		];
		$res=$this->validate($request,$regRule);
	
	}
  public function register(Request $request){
		$email=$request->get('email');
		$res=DB::table('user')->where('user_email',$email)->get();
		if(!$res){
			$iid=DB::table('user')->insert([
			'user_name' => $request->get('username'),
			'user_email' => $request->get('email'),
			'user_password' => md5($request->get('password')),
			'user_type' => 1,
			'user_status' => 1,
			]);
			if($iid){
        echo json_encode(['code'=>0,'msg'=>'success!']);
      }else{
        echo json_encode(['code'=>-2,'msg'=>'failed!']);
      }
		}else{
      echo json_encode(['code'=>-1,'msg'=>'email exists!']);
    }
	}
  public function loginForm(){
		$userInfo=Session::get('userInfo',null);
    if($userInfo){
      return Redirect::to('/user/profile');
    }

		return view('user.login',['title'=>'Login']);
	}



  /*
  public function login(Request $request){
		$email = $request->get('email');
		$password = $request->get('password');
		
		$res=DB::table('user')->where(['user_email'=>$email,'user_password'=>md5($password)])->select('user_id','user_name','user_status')->first();
		if($res){
      
			Session(['userInfo'=>$res]);
			echo json_encode(['code'=>0,'msg'=>'login success!']);
		}else{
      	echo json_encode(['code'=>-1,'msg'=>'login failed!']);
    }
	}
  */

  private function code(){
      $code ='';
      for($i=0;$i<5;$i++){
        $code.=rand(0,9);
      }
      return $code;
    }
  public function sendmail(Request $request){
      $lang = isset($_COOKIE['lang'])?'zh_cn':'en';
      \App::setLocale($lang);
      $email=$request->get('email','');
      if(!$email){
        return response()->json(['code'=>200,'msg'=>'email invalidate']);
      }
      $ck_email=DB::table('user')->where('user_email',$email)->first();
      if(!$ck_email){
        return response()->json(['code'=>200,'msg'=>'email not register']);
      }
      $code = $this->code();
      //$subject = isset($_COOKIE['lang'])?'找回您的密码！':'Find your novaby password!';
      Session::set('code',$code);
      Session::set('email',$email);
        $flag = Mail::send('home.mail',['code'=>$code],function($message) use ($email){
            $to = $email;
            $subject = isset($_COOKIE['lang'])?'找回您的密码！':'Reset your Novaby password1';
            $message ->to($to)->subject($subject);
        });
        if($flag){
            return response()->json(['code'=>200,'msg'=>'ok']);
        }else{
            return response()->json(['code'=>200,'msg'=>'send mail error!']);
        }
    }
  public function resetpass(Request $request){
      $code = Session::get('code',null);
      $email = Session::get('email',null);
      $gcode = $request->get('code','');
      $gmail = $request->get('email','');
      $pass = $request->get('pass','');
      if($code!==$gcode || $email!==$gmail){
        return response()->json(['code'=>200,'msg'=>'email or validation code error']);
      }
      if(strlen($pass)<5 || strlen($pass)>20){
        return response()->json(['code'=>200,'msg'=>'password must be 5-20 charactar length']);
      }
      $res=DB::table('user')->where('user_email',$email)->update(['user_password'=>md5($pass)]);
      if($res){
        return response()->json(['code'=>200,'msg'=>'ok']);
      }
    return response()->json(['code'=>200,'msg'=>'ok']);

    }
  public function userfollows($id=0){
      if($id==0) $id=1;
      $info=Session::get('userInfo',null);
      $user_info=DB::table('user')->where(['user_id'=>$id])->first();
      if(!$user_info) exit;
      $user_info->user_icon=$user_info->user_icon?ApiConf::IMG_URI.$this->getOssPath($user_info->user_icon)->oss_path:'';
      $user_info-> ism =  $user_info->user_ismodeler?1:0;
      $cart_info=[];
      $notices=[];
      //$user_info->works=DB::table('element')->where('user_id'=>$user_info->user_id)->where('element_show','!=',2)->count();
      $works=DB::select("SELECT COUNT(*) AS tot FROM element WHERE user_id=".$user_info->user_id." AND element_show!=2");
      $user_info->works=$works[0]->tot;
      $user_info->following=DB::table('following')->where(['from_uid'=>$user_info->user_id,'followed'=>1])->count();
      $user_info->follower=DB::table('following')->where(['to_uid'=>$user_info->user_id,'followed'=>1])->count();
      $user_info->likes=DB::table('likes')->where(['like_uid'=>$user_info->user_id,'liked'=>1])->count();
      $hasFollow=0;
      $isme = 0;
      if($info){
        $isme=$info->user_id==$id?1:0;
          $notices=$this->getNoticesLists($info->user_id);
          $cart_info=$this->getCart($info->user_id);
          $ck_follow=DB::table('following')->where(['from_uid'=>$info->user_id,'to_uid'=>$id])->first();
          $hasFollow=$ck_follow?$ck_follow->followed:0;
      }
      $user_info->hasFollow=$hasFollow;
      $user_info->isme=$isme;
      return view('home.follows',['user'=>$info,'userinfo'=>$user_info,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>$user_info->user_name.' '.$user_info->user_lastname]);
    }




    public function surveySuccess(){
        $cart_info=[];
        $notices=[];
        $email = Session::get('surveyEmail');
        return view('nova.surveySuccess',['user'=>null,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Apply to join Novaby','mail'=>$email]);
    }
    public function surveysucc(){
        $cart_info=[];
        $notices=[];
        $email = Session::get('surveyEmail');
        return view('nova.surveySuccess1',['user'=>null,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Apply to join Novaby','mail'=>$email]);
    }

    public function forget(){
        $info = Session::get("userInfo",null);
        if($info!=null){
            header('Location: /');
            exit;
        }
        $cart_info=[];
        $notices=[];
        $email = Session::get('surveyEmail');
        return view('auth.findpass',['user'=>null,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'find my password','mail'=>$email]);

    }
    public function ckinvite(Request $req){
        $icode = $req->get('icode');
        $inv_info = DB::table('invite')->select('email','accept')->where(['code'=>$icode])->first();
        if($inv_info){
            $inv_info->accept = $inv_info->accept== NULL ? 0 : 1;
            return response()->json(['code'=>200,'data'=>$inv_info]);
        }else{
            return response()->json(['code'=>100]);
        }
    }
    public function ckEmailAndCode(Request $req){

        $email = $req->get('email');
        $code = $req->get('code');
        $semail = Session::get('email','');
        $scode = Session::get('code','');
        if($email==$semail && $code==$scode){
            echo 1;
        }else{
            echo 0;
        }
    }

    public function expressreg(){

        return view('auth.expressreg',['user'=>null,'title'=>'express register']);

    }
    public function  expressregister(Request $req){
        $email = $req->get('email','');
        if(!$email){
            echo -1;
            return;
        }
        $ck_email = DB::table('user')->where(['user_email'=>$email])->count();
        if($ck_email>0){
            echo 0;
            exit;
        }
        $pass = '';
        for ($i = 0; $i < 8; $i++)
        {
            $pass .= chr(mt_rand(33, 126));
        }
        $iid=DB::table('user')->insertGetId([
            'user_name' => 'Novaby',
            'user_lastname' => '',
            'user_email' => $email,
            'user_password' => md5($pass),
            'user_type' => 1,
            'user_status' => 1,
            'user_isvalidate'=>1,
        ]);
        if($iid){
            $flag = Mail::send('emailtpl.expressreg',['code'=>$pass,'email'=>$email,'url'=>'https://www.novaby.com/#login'],function($message) use ($email){
                $to = $email;
                $subject = 'Welcome to Novaby';
                $message ->to($to)->subject($subject);
            });
        }
        echo $iid;
    }
    public function expressregResult(){
        return view('auth.expressregSuccess',['user'=>null,'title'=>'express register']);
    }

    public function homePage($home)
    {
        $user = User::where('user_page_id',$home)->first();
        $baseUrl = $_SERVER['REDIRECT_URL'];
        return redirect($baseUrl.'personal/'.$user->user_id.'/about');
    }


}
