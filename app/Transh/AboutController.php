<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
use DB;
use Mail;
class AboutController extends Controller
{
    //
    public function privacy(){
        $info=Session::get('userInfo',null);
        $cart_info=[];
        $notices=[];
        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }

        return view('about.privacy',['user'=>$info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'privacy']);

    }
    public function term(){
        $info=Session::get('userInfo',null);
        $cart_info=[];
        $notices=[];
        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }

        return view('about.term',['user'=>$info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'copyright']);

    }
    public function about(){
        $info=Session::get('userInfo',null);
        $cart_info=[];
        $notices=[];
        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }

        return view('about.about',['user'=>$info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'about us']);

    }
    public function help(){
        $info=Session::get('userInfo',null);
        $cart_info=[];
        $notices=[];

        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);

        }
        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }

        return view('about.help',['user'=>$info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'help']);

    }
    public function feedback(){
        $info=Session::get('userInfo',null);
        $cart_info=[];
        $notices=[];
        $email='';

        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
            $email = DB::table('user')->select('user_email')->where(['user_id'=>$info->user_id])->first();
            $email = $email->user_email;


        }

        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);
        }

        return view('about.feedback',['user'=>$info,'notices'=>$notices,'cart_info'=>$cart_info,'email'=>$email,'title'=>'feedback']);
    }
    public function dofeedback(Request $req){
        $email = $req->get('email');
        $content = $req->get('content');
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";

        $res = DB::table('feedback')->insertGetId(['feed_email'=>$email,'feed_content'=>$content]);
        if ( preg_match( $pattern, $email ) ){
         Mail::send('emailtpl.feedbackmail',[],function($message) use ($email){
               $to = $email;

               $subjects='Novaby thanks for your feedback';
               $message ->to($to)->subject($subjects);
            });
        }
        if($res>0) echo 1;

    }
    public function feedbacksuccess(){
        $info=Session::get('userInfo',null);
        $cart_info=[];
        $notices=[];

        if($info){
            $notices=$this->getNoticesLists($info->user_id);
            $cart_info=$this->getCart($info->user_id);

        }


        return view('about.feedbacksuccess',['user'=>$info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'thanks for feedback']);
    }
}
