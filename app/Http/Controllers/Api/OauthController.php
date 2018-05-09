<?php

namespace App\Http\Controllers\Api;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Http\Request;
use App\Model\User;
use Illuminate\Support\Facades\Input;

class OauthController extends BaseApiController
{
    public function twitter(){
        define('CONSUMER_KEY', 'aoySLmoAhusbh2IL7vpv0jceD');
        define('CONSUMER_SECRET', 'c2tTdsMI0hxBXoimrlh0ODUb0Ze9HpSKwelztHFPU2ADrdATpZ');
        define('OAUTH_CALLBACK', 'https://api.novaby.com/oauth/twcb');

        $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));
        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

        $token = Input::get('token');
        $url .='&token='.$token;
        return response()->json(['code'=>200,'msg'=>'ok','url'=>$url]);
    }
    public function outhcb(){
        $user = User::find($this->_user->user_id);
        $user->user_twitter_id=1;
        if($user->save()){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("failed");
        }
    }
    public function twdisconnect(){
        $user = User::find($this->_user->user_id);
        $user->user_twitter_id=0;
        if($user->save()){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("failed");
        }
    }
    public function lkddisconnect(){
        $user = User::find($this->_user->user_id);
        $user->user_linkedin_id=0;
        if($user->save()){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("failed");
        }
    }
    public function pinterestcb(){
        dd(Input::all());
    }
    public function disconnectpin(Request $req){
        $user = User::find($this->_user->user_id);
        $user->user_pinterest_id=0;
        if($user->save()){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("failed");
        }
    }
}
