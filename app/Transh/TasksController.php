<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use Illuminate\Http\Request;

use App\Http\Requests;
use DB;
use Illuminate\Support\Facades\Input;
use Session;

class TasksController extends Controller
{
    //
    public function index($cate = 0){

        $user_info = Session::get('userInfo',null);
        if($user_info){
            $user_id=$user_info->user_id;
            $_icon = DB::table('user')->select('user_icon')->where(['user_id'=>$user_info->user_id])->first();
            if($_icon->user_icon==0){
                $user_info->icon = '/images/new/logo.jpeg';
            }else{
                $_oss_item = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_icon->user_icon])->first();
                $user_info->icon = ApiConf::IMG_URI.$_oss_item->oss_path."@0o_0l_30w_90q.src";
            }
        }
        $user_id = 0;
        $cate = Input::get('cate',0);
        $filter = Input::get('filter',0);


        if($cate==0){
            $cond = '1=1 AND prj_ispub=1';

        }else{
            $cond = ' prj_ispub=1 AND prj_cate = '.$cate;
        }
        if($filter==0){
            $cond.=' AND 1=1 ';
        }else{
            if($filter==1){
                $cond.=' AND prj_process_status<7 ';
            }elseif($filter==2){
                $cond.=' AND prj_process_status=7 ';
            }
        }
        $cates = DB::table('category')->where(['cate_pid'=>1])->get();
        $lists = DB::table('project')->whereRAW($cond)->orderby('prj_id','desc')->Paginate(20);
        //echo $cond;
        //exit;
        foreach($lists AS $k=>$v){
            $_covers = explode(",", $v->prj_photos);
            $_cover = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_covers[0]])->first();
            $lists[$k]->cover = ApiConf::IMG_URI.$_cover->oss_path;

            $_industry = DB::table('category')->select('cate_name')->where(['cate_id'=>$v->prj_industry])->first();
            $lists[$k]->industry = $_industry->cate_name;
            $apply_nums = DB::table('prj_apply')->where(['prj_id'=>$v->prj_id])->count();
            $lists[$k]->apply_nums=$apply_nums;

        }
        //dd($lists);
        return view('new.tasks.lists',['user_info'=>$user_info,'user'=>$user_info,'lists'=>$lists,'cates'=>$cates,'cate'=>$cate,'filter'=>$filter]);


    }
    public function detail($id = 0){
        if($id<1){
            exit;
        }
        DB::table('project')->where(['prj_id'=>$id])->increment('prj_views',1);
        $user_info = Session::get('userInfo',null);
        if($user_info){
            $user_id=$user_info->user_id;
            $_icon = DB::table('user')->select('user_icon','user_ismodeler')->where(['user_id'=>$user_info->user_id])->first();
            if($_icon->user_icon==0){
                $user_info->icon = '/images/new/logo.jpeg';
            }else{
                $_oss_item = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_icon->user_icon])->first();
                $user_info->icon = ApiConf::IMG_URI.$_oss_item->oss_path."@0o_0l_30w_90q.src";
            }
            $user_info->ismodeler=$_icon->user_ismodeler;
        }

        $task = DB::table('project')->where(['prj_id'=>$id])->first();
        $_ps = explode(",",$task->prj_photos);
        $_imgs = DB::table('oss_item')->select('oss_path')->whereIn('oss_item_id',$_ps)->get();

        $_photos = [];
        $_photos[]=ApiConf::IMG_URI.$_imgs[count($_imgs)-1]->oss_path;
        foreach($_imgs AS $k=>$v){
            $_photos[]=ApiConf::IMG_URI.$v->oss_path;

        }
         $_photos[]=ApiConf::IMG_URI.$_imgs[0]->oss_path;

        $task->pics = $_photos;
        $task->prj_end_time = $task->prj_pubtime +$task->prj_period*24*3600+$task->prj_period_h*3600;

        $industry=DB::table('category')->where('cate_id',$task->prj_industry)->first();
        $cate=DB::table('category')->where('cate_id',$task->prj_cate)->first();
        $format=DB::table('category')->where('cate_id',$task->prj_format)->first();

        $task->prj_industry =$industry->cate_name;
        $task->prj_cate =$cate->cate_name;
        $task->prj_format =$format->cate_name;
       //dd($user_info);

        $has_wish = 0;
        $has_apply = 0;
        if($user_info) {

            $has_wish = DB::table('prj_wish')->where(['prj_id' => $task->prj_id, 'user_id' => $user_info->user_id])->count();
            $has_apply = DB::table('prj_apply')->where(['prj_id' => $task->prj_id, 'user_id' => $user_info->user_id])->count();


        }
        $task->has_wish= $has_wish;
        $task->has_apply= $has_apply;

        $task->left_time = $task->prj_end_time-time();
        //dd($task);
        return view('new.tasks.detail',['user_info'=>$user_info,'user'=>$user_info,'data'=>$task]);

    }

    public function getlists(){
        $cate = Input::get('cate',0);
        $page = Input::get('page',1);


        if($cate==0){
            $cond = '1=1 AND prj_ispub=1';

        }else{
            $cond = ' prj_ispub=1 AND prj_cate = '.$cate;
        }

        $lists = DB::table('project')->whereRAW($cond)->orderby('prj_id','desc')->paginate(20);
        foreach($lists AS $k=>$v){
            $_covers = explode(",", $v->prj_photos);
            $_cover = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_covers[0]])->first();
            $lists[$k]->cover = ApiConf::IMG_URI.$_cover->oss_path;

            $_industry = DB::table('category')->select('cate_name')->where(['cate_id'=>$v->prj_industry])->first();
            $lists[$k]->industry = $_industry->cate_name;
            $apply_nums = DB::table('prj_apply')->where(['prj_id'=>$v->prj_id])->count();
            $lists[$k]->apply_nums=$apply_nums;

            $cate=DB::table('category')->where('cate_id',$v->prj_cate)->first();
            $lists[$k]->cate =$cate->cate_name;

        }
        return response()->json($lists);


    }
}
