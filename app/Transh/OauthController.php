<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Redirect;
use Session;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Input;
class OauthController extends Controller
{
    private $userInfo = null;
    public function __construct(){

    	$info = Session::get('userInfo',null);
    	/*
    	if (!$info) {
    		exit;
    	}else{
    		$this->userInfo = $info;
    	}
    	*/
    }
    public function twitter(){
        define('CONSUMER_KEY', 'aoySLmoAhusbh2IL7vpv0jceD');
        define('CONSUMER_SECRET', 'c2tTdsMI0hxBXoimrlh0ODUb0Ze9HpSKwelztHFPU2ADrdATpZ');
        define('OAUTH_CALLBACK', 'https://api.novaby.com/oauth/twcb');

        $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));
        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        //$token = Input::get('token');
        //$url .='&token='.$token;

        return response()->json(['code'=>200,'msg'=>'ok','url'=>$url]);
    }


    public function pinconnect(){
        $token = isset($_COOKIE['token'])?$_COOKIE['token']:'';
        if(!$token){
            echo "error";
            exit;
        }else{
            $ck_user =DB::table('user')->where('user_token',$token)->first();
            if(!$ck_user){
                echo "error";
                exit;
            }
            DB::table('user')->where(['user_id'=>$ck_user->user_id])->update(['user_pinterest_id'=>1]);

            header('Location: '.env('CLIENT_BASE').'personal/'.$ck_user->user_id.'/social');

        }
    }
    public function twittercallback(){

        $token = isset($_COOKIE['token'])?$_COOKIE['token']:'';
        if(!$token){
            echo "error";
            exit;
        }else{
            $ck_user =DB::table('user')->where('user_token',$token)->first();
            if(!$ck_user){
                echo "error";
                exit;
            }
            DB::table('user')->where(['user_id'=>$ck_user->user_id])->update(['user_twitter_id'=>1]);

            header('Location: /#/setting/social');

        }
        exit;
    	if ($this->userInfo->user_id){
    		DB::table('user')->where(['user_id'=>$this->userInfo->user_id])->update(['user_twitter_id'=>1]);
    		echo "ok";
    	}
    }
    public function disconnect(Request $request){
    	$cid = $request->get('cid',NULL);
    	if ($cid === NULL) exit;
    	if($cid < 1 || $cid > 3) exit;
    	$fields = ['user_facebook_id','user_twitter_id','user_linkedin_id'];
    	$data = [$fields[$cid-1]=>NULL];
    	$res = DB::table('user')->where(['user_id'=>$this->userInfo->user_id])->update($data);
    	if ($res) {
    		return response()->json(['code'=>200,'msg'=>'ok']);
    	}

    }

    public function twcb(){
    	if(Input::get('oauth_token') && Input::get('oauth_verifier')){
    	    $token = Input::get('token','');
    	    if(!$token){
    	        return;
            }


    	    //@@@@@@@@@@@@@@@
            $ck_user =DB::table('user')->where('user_token',$token)->first();
            if(!$ck_user){
                echo "error";
                exit;
            }
            DB::table('user')->where(['user_id'=>$ck_user->user_id])->update(['user_twitter_id'=>1]);
            echo "ok";
            //@@@@@@@@@@@@@@@
    	    dd(Input::get('oauth_verifier'));
    		DB::table('user')->where(['user_id'=>$this->userInfo->user_id])->update(['user_twitter_id'=>1]);
    		header('Location: /setting');
    	}
    }
    public function linkedincb(){
    	if(Input::get('code')){
            $token = Input::get('token','');
            if(!$token){
                return;
            }
            //dd(Input::get('code'));

            //@@@@@@
            $ck_user =DB::table('user')->where('user_token',$token)->first();
            if(!$ck_user){
                echo "error";
                exit;
            }
            DB::table('user')->where(['user_id'=>$ck_user->user_id])->update(['user_linkedin_id'=>1]);

            header('Location: '.env('CLIENT_BASE').'personal/'.$ck_user->user_id.'/social');
            //@@@@@@
    		//DB::table('user')->where(['user_id'=>$this->userInfo->user_id])->update(['user_linkedin_id'=>1]);
    		//header('Location: /linkedinAuthOk');
    	}
    }
    public function linkedinAuthOk(){
 
    	return view('user.linkedinAuthOk');
    }
    public function fbconnect(){
		$res = DB::table('user')->where(['user_id'=>$this->userInfo->user_id])->update(['user_facebook_id'=>1]);
		if($res){
			return response()->json(['code'=>200,'msg'=>'ok']);
		}
    }
    
}