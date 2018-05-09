<?php

namespace App\Http\Controllers\Api;

use App\Model\Plan;
use App\Model\UserPlan;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class PlanController extends BaseApiController
{
    //
    public function plans(){
        $plans = Plan::all();
        $myplans = null;
        $has_login = 0;

        if($this->_user){
            $has_login = 1;
            $myplans = UserPlan::where('user_id',$this->_user->user_id)->where('end_time','>',time())->orderby('id','desc')->first();
        }
        foreach($plans AS $k=>$v){
            if($myplans && $myplans->plan_id == $v->id){
                $plans[$k]->has_order=1;
            }else{
                $plans[$k]->has_order=0;
            }
            if($v['anno_nums']==0){
                $plans[$k]['anno_nums']='UNLIMITED';
            }
            if($v['price']==0){
                $plans[$k]['price']='free';
            }else{
                $plans[$k]['price']=intval($plans[$k]['price'])."$/month";
            }
        }

        return $this->jsonOk('ok',['plans'=>$plans,'login'=>$has_login]);
    }
    public function choose(){
        $id = Input::get('id',0);
        if($id==0){
            return '';
        }
        $plan = Plan::find($id);
        if(!$plan){
            return '';
        }
        if($plan->anno_nums==0){
            $plan->anno_nums='UNLIMITED';
        }
        $months = [
            [
                'id'=>'1',
                'val'=>'1 Month',
            ],
            [
                'id'=>'3',
                'val'=>'3 Months',
            ],
            [
                'id'=>'6',
                'val'=>'6 Months',
            ],
            [
                'id'=>'12',
                'val'=>'12 Months',
            ]
        ];
        return $this->jsonOk("ok",['plan'=>$plan,'time'=>$months]);

    }

    public function myplans(){
        $myplans = UserPlan::where('user_id',$this->_user->user_id)
            ->where('end_time','>',time())
            ->select('start_time','end_time','plan_id','id')
            ->orderby('id','desc')
            ->first();
        if($myplans){
            $myplans->name=$myplans->Plan->name;
            unset($myplans->Plan);
            return $this->jsonOk("ok",['lists'=>$myplans]);
        }
    }
    public function freeupgrade(){
        $user_plan = new UserPlan();
        $user_plan->user_id = $this->_user->user_id;
        $user_plan->plan_id = 1;
        $user_plan->start_time = time();
        $user_plan->end_time = strtotime("+ 10 years");
        $user_plan->pay_time = time();
        $user_plan->pay_method = "free";
        $user_plan->pay_num = 0;
        if($user_plan->save()){
            return $this->jsonOk("ok",['msg'=>'upgrade successfully']);
        }else{
            return $this->jsonErr("upgrade error");
        }
    }

    public function plantime(){
        $user_plan = UserPlan::where('user_id',$this->_user->user_id)
            ->orderBy('id','DESC')
            ->select('start_time')
            ->first();
        if($user_plan || $user_plan->start_time){
            return $this->jsonOk("ok",['start'=>$user_plan->start_time]);
        }else{
            return $this->jsonErr("not found");
        }

    }


}
