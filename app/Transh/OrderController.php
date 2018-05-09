<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
use DB;
class OrderController extends Controller
{
    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    public function cart(Request $request){
        $id=$request->get('id');
        $ch_order=DB::table('orders')->where(['order_eid'=>$id,'order_uid'=>$this->info->user_id])->first();

        $element_info=DB::table('element')->select('element_price','user_id','element_models')->where(['element_id'=>$id])->first();

        if(!$ch_order){
            $order=[
                'order_uid'=>$this->info->user_id,
                'order_owner'=>$element_info->user_id,
                'order_eid'=>$id,
                'order_status'=>$element_info->element_price>0?0:1,
                'order_price'=>$element_info->element_price,
            ];
            $oid=DB::table('orders')->insertGetId($order);
        }
        if($ch_order){
            $oid = $ch_order->order_id;
        }

        //$ch_order->order_status=$element_info->element_price>0 ? 0 : 1;

        if($element_info->element_price == 0){
            $url = ApiConf::IMG_URI.$this->getOssPath($element_info->element_models)->oss_path;
            return response()->json(['code'=>200,'oid'=>$oid,'msg'=>'ok','isfree'=>1,'url'=>$url,'r'=>1]);
        }else{
            if($ch_order) {

                if($ch_order->order_status==0){//未付款 跳转购物车

                    $url = route('checkout');

                    return response()->json(['code'=>200,'oid'=>$oid,'msg'=>'ok','isfree'=>0,'url'=>$url,'r'=>1,'s'=>0]);
                }else{
                    $url = ApiConf::IMG_URI.$this->getOssPath($element_info->element_models)->oss_path;
                    return response()->json(['code'=>200,'oid'=>$oid,'msg'=>'ok','isfree'=>0,'url'=>$url,'r'=>1,'s'=>0]);
                }


            }else{

                return response()->json(['code'=>200,'oid'=>$oid,'msg'=>'ok','isfree'=>0,'url'=>'','r'=>0,'s'=>1]);
            }
        }

        exit;



        if ($ch_order) {
            if ($ch_order->order_status == 1 && $element_info->element_price == 0){
                $url = ApiConf::IMG_URI.'/'.$this->getOssPath($element_info->element_models)->oss_path;
                return response()->json(['code'=>200,'msg'=>'ok','isfree'=>1,'url'=>$url]);
            }
            return;

        }

        $order=[
            'order_uid'=>$this->info->user_id,
            'order_owner'=>$element_info->user_id,
            'order_eid'=>$id,
            'order_status'=>$element_info->element_price>0?0:1,
            'order_price'=>$element_info->element_price,
        ];

        $oid=DB::table('orders')->insertGetId($order);
        if($oid){
            if ($element_info->element_price == 0) {
                $url = ApiConf::IMG_URI.'/'.$this->getOssPath($element_info->element_models)->oss_path;
            }else{
                $url = '';
            }
            return response()->json(['code'=>200,'msg'=>'ok','isfree'=>$element_info->element_price>0?0:1,'url'=>$url]);

        } else {
            return response()->json(['code'=>200,'msg'=>'ok','isfree'=>$element_info->element_price>0?0:1,'url'=>$url]);
        }

    }
    public function cartremove(Request $request){
        $id=intval($request->get('id'));
        $result=DB::table('orders')->where(['order_id'=>$id,'order_uid'=>$this->info->user_id])->delete();
        if($result){
            echo 1;
        }
    }



}
