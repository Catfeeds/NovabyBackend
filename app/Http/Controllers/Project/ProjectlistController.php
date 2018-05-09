<?php

namespace App\Http\Controllers\Project;


use App\libs\ApiConf;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Session;
use DB;

class ProjectlistController extends Controller
{
    //
    private $info;


    public function __construct(){
        $this->info=Session::get('userInfo',null);


    }
    public function lists(){
        //if($this->info){
            return view('v1.lists.list',['user_info'=>$this->info,'user'=>$this->info]);
        //}else{
          //  return view('v1.lists.login',['user_info'=>$this->info]);
        //}
    }
    public function prjlists(Request $req){

        if(!$this->info){
            return response()->json(['code'=>-1,'data'=>'','msg'=>'']);
        }
        $page = $req->get('page','');
        if(!$page){
            return response()->json(['code'=>100,'msg'=>'error']);
        }
        $pagesize = 10;
        $offset = ($page-1) * $pagesize;
        $channel = $req->get('channel',1);
        $type =  $req->get('type',1);
        $which =  $req->get('which',1);
        $rawwhere = ' 1=1 ';
        //type 1 ongoing 2 drafts 3 complete
        if($which==2){
            //乙方
            if($type==1){
                $_id_strs = '';
                $d = DB::table('bidding')->select('bid_pid')->where(['bid_uid'=>$this->info->user_id])->get();
                foreach($d AS $k=>$v){
                    $_id_strs .=$v->bid_pid.',';
                }
                $_id_strs=rtrim($_id_strs,",");
                $rawwhere .=' AND prj_final_uid='.$this->info->user_id." OR prj_id IN('$_id_strs') AND  prj_process_status < 8";
            }elseif($type==2){
                $rawwhere .=' AND 1 != 1';
            }elseif($type==3){
                $rawwhere .=' AND prj_final_uid='.$this->info->user_id." AND  prj_process_status = 7";

            }
        }elseif($which==1){
            //甲方
            if($type==1){
                $rawwhere .=' AND prj_uid='.$this->info->user_id.' AND  prj_process_status < 7';

            }elseif($type==2){
                $rawwhere .='AND prj_uid='.$this->info->user_id.' AND ISNULL(prj_process_status)';
            }elseif($type==3){
                $rawwhere .='AND prj_uid='.$this->info->user_id.' AND prj_process_status = 7';
            }
        }


        $lists = DB::table('project')->whereRaw($rawwhere)->skip($offset)->take($pagesize)->orderBy('prj_id','DESC')->get();
        $status = [
            'Drafts',
            'Published',
            'Proposal',
            'Trial',
            'Contract',
            'Payment',
            'Building',
            'Submission',
        ];
        foreach($lists AS $k=>$v){
            //echo $v->prj_status.'#'.$v->prj_process_status;
            if(!$v->prj_process_status){
                $lists[$k]->status = $status[0];
                $url = Route('pub_step1',$v->prj_id);
            }else{
                $lists[$k]->status = $status[$v->prj_process_status];
                if($which==1){
                    $_step = 'prjs'.$v->prj_process_status;
                }elseif($which==2){
                    $_step = 'taskstep'.$v->prj_process_status;
                }
                $url = Route($_step,$v->prj_id);


            }
            $lists[$k]->url = $url;
            $lists[$k]->pubtime = date('Y-m-d H:i:s',$v->prj_uptime);
            $_ps = explode(",",$v->prj_photos);
            $_oss_item =  DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$_ps[0]])->first();
            $lists[$k]->pic = ApiConf::IMG_URI.$_oss_item->oss_path;


        }
        if(!$lists && $page == 1){
            return response()->json(['code'=>-2,'data'=>'']);

        }
        return response()->json(['code'=>200,'data'=>$lists]);

    }
}
