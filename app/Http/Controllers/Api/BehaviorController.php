<?php

namespace App\Http\Controllers\Api;

use App\Model\Comment;
use App\libs\Tools;

use App\Model\Work;
use Facebook\Facebook;
use Illuminate\Http\Request;
use App\Model\User;
use DB;

class BehaviorController extends BaseApiController
{

    private function followuser($uid){
        $ck=DB::table('following')->where(['from_uid'=>$this->_user->user_id,'to_uid'=>$uid])->first();
        if(!$ck){
            $iid=DB::table('following')->insert(
                [
                    'from_uid'=>$this->_user->user_id,
                    'to_uid'=>$uid,
                    'followed'=>1,
                    'op_time'=>time(),
                ]
            );
            if($iid){
                $res = $this->addMsg($this->_user->user_id,$uid,1,0);
                //event(new PostSaved());
                return ['msg'=>'ok','result'=>1];
            }
        }else{
            if($ck->followed){
                DB::table('following')->where('id',$ck->id)->update(['followed'=>0]);
                return ['msg'=>'ok','result'=>0];

            }else{
                DB::table('following')->where('id',$ck->id)->update(['followed'=>1,'op_time'=>time()]);
                $res = $this->addMsg($this->_user->user_id,$uid,1,0);

                return ['msg'=>'ok','result'=>1];
            }
        }


    }

    public function follow(Request $req){
        $users = $req->get('users');

        if(!is_array($users) || count($users)<1){
            return $this->jsonErr("no users");
        }
        foreach($users AS $v){
            $res = $this->followuser($v);
        }
        return $this->jsonOk('ok',$res);
    }



    public function comemnt(Request $req){

        $modelid = $req->get('model_id',0);
        $reply_uid = $req->get('reply_uid',0);
        $comment_content = $req->get('comment_content','');
        $comment_reply_pid = $req->get('reply_pid','');
        $work = Work::find($modelid);
        if(!$comment_content){
            return $this->jsonErr("content can't be blank");
        }
        if(!$work){
            return $this->jsonErr("error");
        }
        $comment = new Comment();

        $comment->comment_uid = $this->_user->user_id;
        $comment->comment_eid=$modelid;
        $comment->to_uid = $work->work_uid;
        $comment->comment_content=$comment_content;
        $comment->comment_pid = $comment_reply_pid;
        $comment->reply_to=$reply_uid;
        if($comment->save()){

            //$this->addMsg($this->_user->user_id,$work->work_uid,4,$work->work_id);

            $user = User::where(['user_id'=>$this->_user->user_id])->select('user_name','user_lastname','user_icon')->first();
            $comment->user_name=$user->user_name.' '.$user->user_lastname;
            $comment->user_avatar = $this->getAvatar($user->user_icon,'100');
            $comment->comment_create_time=time();
            $comment->time = time();
            if($comment->reply_to){
                $_user_reply = User::where(['user_id'=>$comment->reply_to])->select('user_name','user_lastname','user_icon')->first();
                $comment->reply_to_user_name=$_user_reply->name.' '.$_user_reply->user_lastname;

            }else{
                $comment->reply_to_user_name='';
            }
            $comment->author = $this->getAuthorAndWorks1($this->_user->user_id);
            $this->addMsg($this->_user->user_id,$work->work_uid,2,$work->work_id);
            return $this->jsonOk('ok',['comment'=>$comment]);
        }else{
            return $this->jsonErr('comment failed');
        }

    }
    public function comemntreport(Request $req){
        $comm_id = $req->get('comment_id',0);
        $reason = $req->get('reason','');
        if(!$comm_id && !$reason){
            return $this->jsonErr("missing paramater");
        }else{
            return $this->jsonOk('ok',['result'=>'report successfully']);
        }



    }
    public function fbconnect(Request $req){
        $user = User::find($this->_user->user_id);
        $user->user_facebook_id=1;
        if($user->save()){
            return $this->jsonOk("ok",[]);
        }
        else{
            return $this->jsonErr("error");
        }

    }
    public function fbdisconnect(Request $req){
        $user = User::find($this->_user->user_id);
        $user->user_facebook_id=0;
        if($user->save()){
            return $this->jsonOk("ok",[]);
        }
        else{
            return $this->jsonErr("error");
        }

    }
    public function facebookconnect(){
        $fb = new Facebook([
            'app_id' => '171866643244371', // Replace {app-id} with your app id
            'app_secret' => '5e1e3d890c9173a951529301a4e83816',
            'default_graph_version' => 'v2.2',
        ]);
        $helper = $fb->getRedirectLoginHelper();

        $permissions = ['email']; // Optional permissions
        echo $loginUrl = $helper->getLoginUrl('http://testapi.novaby.com/social/facebookcb?token=8adfda122c96d791e5492645d20800a0', $permissions);
        exit;
        return $this->jsonOk("ok",['url'=>$loginUrl]);
        //echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
    }
    public function facebookcb(){
        $appid = '171866643244371';
        $app_secret = '5e1e3d890c9173a951529301a4e83816';
        $fb = new Facebook([
            'app_id' => $appid, // Replace {app-id} with your app id
            'app_secret' => $app_secret,
            'default_graph_version' => 'v2.2',
        ]);
        $helper = $fb->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (! isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
            }
            exit;
        }

        echo '<h3>Access Token</h3>';
        var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        echo '<h3>Metadata</h3>';
        var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId($appid); // Replace {app-id} with your app id
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
$tokenMetadata->validateExpiration();

if (! $accessToken->isLongLived()) {
    // Exchanges a short-lived access token for a long-lived one
    try {
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
        exit;
    }

    echo '<h3>Long-lived</h3>';
    var_dump($accessToken->getValue());
}

echo (string) $accessToken;

// User is logged in with a long-lived access token.
// You can redirect them to a members-only page.
//header('Location: https://example.com/members.php');

    }

}
