<?php

namespace App\Http\Controllers;

use App\libs\Tools;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use App\libs\ApiConf;
use Session;
use DB;
class ProductController extends Controller
{
    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    public function checkout($id=0){
        if($id==0){
            $order=DB::table('orders')->where(['order_status'=>0,'order_uid'=>$this->info->user_id])->get();
        }else{
            $ck_order = DB::table('orders')->where(['order_id'=>$id,'order_uid'=>$this->info->user_id,'order_status'=>0])->count();
           if($ck_order==0){
               abort(404);
               return;
           }
            $order=DB::table('orders')->where(['order_status'=>0,'order_uid'=>$this->info->user_id,'order_id'=>$id])->get();
        }

        //$order=DB::table('orders')->where(['order_status'=>0,'order_uid'=>$this->info->user_id])->get();
        foreach($order AS $k=>$v){
            $element=DB::table('element')->where('element_id',$v->order_eid)->first();
            $cate=DB::table('category')->where(['cate_id'=>$element->element_category])->first();
            $element->element_category=$cate->cate_name;

            $format=DB::table('category')->where(['cate_id'=>$element->element_format])->first();

            $element->element_format=$format->cate_name;
            $cover=DB::table('oss_item')->where('oss_item_id',$element->element_cover_id)->first();
            $element->cover=$cover->oss_path;
            $element->element_price = Tools::trans_currency($element->element_price,$element->element_currency);
            $order[$k]->element=$element;
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $coupon = 0;
        $coupon_account = DB::table('wallet')->where(['uid'=>$this->info->user_id])->first();
        if($coupon_account){
            $coupon = $coupon_account->coupon;
        }
        return view('user.checkout',['user'=>$this->info,'order'=>$order,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'checkout','coupon'=>$coupon]);
    }
    public function products(){


        $page_size=30;
        $page=Input::get('page')>0 ? intval(Input::get('page')):1;
        $offset=($page-1)*$page_size;

        $mylist=DB::table('element')->where(['user_id'=>$this->info->user_id,'element_show'=>0])->skip($offset)->take($page_size)->orderBy('element_id','DESC')->Paginate($page_size);

        //$novas=DB::table('element')->where($list_condation)->skip($offset)->take($page_size)->orderBy('element_id','DESC')->get();

        foreach($mylist AS $k=>$v){

            $cover=DB::table('oss_item')->where('oss_item_id',$v->element_cover_id)->first();
            $mylist[$k]->cover=ApiConf::IMG_URI.$cover->oss_path;

        }




            $notices=$this->getNoticesLists($this->info->user_id);
            $cart_info=$this->getCart($this->info->user_id);

        return view('user.products',['user'=>$this->info,'lists'=>$mylist,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Products']);
    }
    public function purchases($id=0){
        if($id==1){
            $order=DB::table('orders')->where(['order_uid'=>$this->info->user_id,'order_status'=>1])->orderBy('order_paytime','desc')->get();
        }elseif($id==3){
            $order=DB::table('orders')->where(['order_uid'=>$this->info->user_id,'order_status'=>0])->get();
        }

        foreach($order AS $k=>$v){

            $element=DB::table('element')->where('element_id',$v->order_eid)->first();



            $cover = DB::table('oss_item')->where('oss_item_id', $element->element_cover_id)->first();

            $element->cover=ApiConf::IMG_URI.$cover->oss_path;

            $models=explode(',',$element->element_models);

            $element->modelurl=$this->getOssPath($models[0])->oss_path;
            $user=DB::table('user')->where('user_id',$element->user_id)->first();
            $user->icon=$user->user_icon?$this->getOssPath($user->user_icon)->oss_path:'';

			$order[$k]->user=$user;
            $order[$k]->element=$element;
            $rate=DB::table('rates')->where(['oid'=>$v->order_id])->first();
            $order[$k]->rate=($rate && $rate->stars)?$rate->stars:0;
            $chat=DB::table('chat')->where(['from_uid'=>$this->info->user_id,'to_uid'=>$element->user_id])->orWhere(['to_uid'=>$this->info->user_id,'from_uid'=>$element->user_id])->get();

            foreach($chat AS $ck=>$cv){
                $sender_ico=DB::table('user')->select('user_icon')->where('user_id',$cv->sender)->first();

                $chat[$ck]->icon=$sender_ico->user_icon?$this->getOssPath($sender_ico->user_icon)->oss_path:'';

            }
            $order[$k]->chat=$chat;


        }

        $cart_info=[];
        $notices=[];

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        $downurl='http://'.ApiConf::OSS_BUKET_NAME_ELEMENTS.'.'.ApiConf::OSS_ENDPOINT.'/';

        return view('user.purchases',['user'=>$this->info,'order'=>$order,'channel'=>$id,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Purchases','durl'=>$downurl]);
    }
    public function sales(){

        $mysales=DB::table('orders')->select('*',DB::raw('COUNT(order_eid) as total_sales'))->where('order_owner',$this->info->user_id)->groupBy('order_eid')->get();
        foreach($mysales AS $k=>$v){
            $element=DB::table('element')->select('element_name','element_id','element_price','element_cover_id','element_create_time')->where('element_id',$v->order_eid)->first();
            $mysales[$k]->cover=ApiConf::IMG_URI.$this->getOssPath($element->element_cover_id)->oss_path;
            $mysales[$k]->tot=$v->total_sales*$element->element_price;
            $mysales[$k]->element_time=$element->element_create_time;
            $mysales[$k]->element_name=$element->element_name;
            $mysales[$k]->element_id=$element->element_id;
        }

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        return view('user.sales',['user'=>$this->info,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'salelists'=>$mysales,'title'=>'Sales']);
    }

    public function elementDelete(Request $request){

        $id=$request->get('id');
        $res=DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->update(['element_show'=>2]);
        if($res) echo 1;
    }
    public function saleDetail($id){
        $list=DB::table('orders')->where('order_eid',$id)->get();
        dd($list);
    }

}
