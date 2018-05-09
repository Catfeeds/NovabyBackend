<?php

namespace App\Http\Controllers;

use App\libs\Tools;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use DB;
use Session;
use App\Model\Ossitem;
use App\libs\ApiConf;

class Controller extends BaseController
{use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    public function getNoticesLists($uid=1){

          $timeFormat = isset($_COOKIE['lang'])?'m-d':'j,F';
          $notice_times=DB::table('user')->select('user_read_news_time','user_read_event_time')->where('user_id',$uid)->first();
          $notices_num=0;
          $n_nums=['news'=>0,'you'=>0];
          $myfollow=DB::table('following')->select('to_uid')->where(['from_uid'=>$uid,'followed'=>1])->get();
          $my_follows=[];
          foreach($myfollow AS $k=>$v){
              $my_follows[]=$v->to_uid;
          }

          $news=DB::table('element')->select('element_id','user_id','element_create_time',DB::raw('GROUP_CONCAT(element_id) AS eids'))->whereIn('user_id',$my_follows)->groupBy('user_id')->orderBy('element_id','DESC')->get();
          foreach($news AS $k=>$v){
              $news[$k]->latest_time_str=strtotime($v->element_create_time)>strtotime(date('Y-m-d')) ? date('H,i',strtotime($v->element_create_time)) : date($timeFormat,strtotime($v->element_create_time));
              $user=DB::table('user')->where('user_id',$v->user_id)->first();
              $news[$k]->user_name=$user->user_name.' '.$user->user_lastname;
              $news[$k]->user_icon=$user->user_icon?$this->getOssPath($user->user_icon)->oss_path:'';
              $news[$k]->ism = $user->user_ismodeler ? 1 : 0;
              $eids=explode(',',$v->eids);
              $eids=array_slice($eids,0,3);
              $elements=DB::table('element')->select('element_id','element_cover_id')->whereIn('element_id',$eids)->get();
              foreach($elements AS $ke=>$ve){
                  $elements[$ke]->element_cover=$this->getOssPath($ve->element_cover_id)->oss_path;
              }
              $news[$k]->elements=$elements;
              $news[$k]->read=0;
              //echo 'database'.$v->element_create_time.'#<br/>'.'php'.strtotime($v->element_create_time).'#<br/>read:'.$notice_times->user_read_news_time.'<br/>'.date('Y-m-d H:i:s',$notice_times->user_read_news_time)."<hr/>";
              //echo $v->element_create_time.'#'.strtotime($v->element_create_time).'#'.$notice_times->user_read_news_time.'#'.date('Y-m-d H:i:s',$notice_times->user_read_news_time);

              if(strtotime($v->element_create_time)>($notice_times->user_read_news_time+8*3600)){
                  $notices_num++; 
                  $news[$k]->read=1; 
                  $n_nums['news']++;
              }
                
          }

          $follow_data=DB::table('following')->where('to_uid',$uid)->orderBy('id','DESC')->limit(4)->get();
          foreach($follow_data AS $k=>$v){
              $users=DB::table('user')->select('user_id','user_name','user_icon','user_ismodeler')->where('user_id',$v->from_uid)->first();
              $users->icon=$users->user_icon?$this->getOssPath($users->user_icon)->oss_path:'';
              $users->ism = $users->user_ismodeler?1:0;
              $follow_data[$k]->users=$users;
              
          }

         if($follow_data){
             $follow_data[0]->latest_time_str = strtotime($follow_data[0]->create_time) > strtotime(date('Y-m-d')) ? date('H,i',strtotime($follow_data[0]->create_time)) : date($timeFormat,strtotime($follow_data[0]->create_time));
         }

         $_isnew=0;
         if($follow_data && strtotime($follow_data[0]->create_time)>($notice_times->user_read_event_time+8*3600)){
              $_isnew=1;
              $notices_num++;
              $n_nums['you']++;
         }

         $comment_data=DB::select("select comment_eid,group_concat(comment_uid) AS ids,group_concat(comment_create_time) AS ctimes from comments WHERE to_uid=? group by comment_eid ASC",[$uid]);

         foreach($comment_data AS $k=>$v){
              $comm_times=explode(",",$v->ctimes);


              foreach ($comm_times as $key => $value) {
                $comm_times[$key]=strtotime($value);
              }

              if($comm_times) {
                  rsort($comm_times);
                  $comment_data[$k]->latest_time = $comm_times[0];
                  $comment_data[$k]->latest_time_str = $comm_times[0] > strtotime(date('Y-m-d')) ? date('H,i', $comm_times[0]) : date($timeFormat, $comm_times[0]);
              }

              $_isnew_comm=0;

              foreach($comm_times AS $_v1){
                  if($_v1>$notice_times->user_read_event_time){
                      $_isnew_comm=1;
                      $notices_num++;
                      $n_nums['you']++;
                      break;
                  }
              }

              /*
              if($comm_times[0]>$notice_times->user_read_event_time){
                $_isnew_comm=1;
                $notices_num++;
                $n_nums['you']++;
              }
              */

              $element_title=DB::table('element')->select('element_name')->where(['element_id'=>$v->comment_eid])->first();
              if($element_title){
                  $comment_data[$k]->title=$element_title->element_name;
                  $comment_data[$k]->eid=$v->comment_eid;
                  $comment_data[$k]->isnew=$_isnew_comm;
                  $ids=explode(",", $v->ids);
                  $ids=array_unique($ids);
                  $comment_data[$k]->tot=count($ids);
              }else{
                  $comment_data[$k]->title='';
                  $comment_data[$k]->eid=0;
                  $comment_data[$k]->isnew=0;

                  $comment_data[$k]->tot=0;
              }

                //$users=DB::table('user')->select('user_id','user_name','user_icon')->whereIn('user_id',$ids)->get();
                $users=DB::table('comments')->select('user.user_id','user.user_name','user.user_icon','user.user_ismodeler')->leftJoin('user','comments.comment_uid','=','user.user_id')->where(['comments.comment_eid'=>$v->comment_eid])->orderBy('comments.comment_id','DESC')->limit(4)->get();
              
              
              foreach($users AS $uk=>$uv){
                $users[$uk]->icon=$uv->user_icon?$this->getOssPath($uv->user_icon)->oss_path:'';
                $users[$uk]->ism = $uv->user_ismodeler?1:0;
              }
              
              $comment_data[$k]->users=$users;
              $comment_data[$k]->cate='2';
            }


           
            $likes_data=DB::select("select like_eid,group_concat(like_to_uid) AS ids,group_concat(like_time) AS ctimes from likes  where like_to_uid=? group by like_eid ASC",[$uid]);
            
            foreach($likes_data AS $k=>$v){
              $like_times=explode(",",$v->ctimes);
              foreach ($like_times as $key => $value) {
                $like_times[$key]=strtotime($value);
              }
              
              rsort($like_times);
              $likes_data[$k]->latest_time=$like_times[0];
              //$likes_data[$k]->latest_time_str=date('j,F',$like_times[0]);
              $likes_data[$k]->latest_time_str = $likes_data[0]->latest_time > strtotime(date('Y-m-d')) ? date('H,i',$like_times[0]) : date($timeFormat,$like_times[0]);
              $_isnew_comm=0;
              if($like_times[0]>($notice_times->user_read_event_time+8*3600)){
                    $_isnew_comm=1;
                    $notices_num++;
                    $n_nums['you']++;
              }
              $element_title=DB::table('element')->select('element_name')->where(['element_id'=>$v->like_eid])->first();
              if($element_title){
                $likes_data[$k]->title=$element_title->element_name;
              }else{
                  $likes_data[$k]->title='';
              }
              $likes_data[$k]->eid=$v->like_eid;
              $likes_data[$k]->isnew=$_isnew_comm;
              $ids=explode(",", $v->ids);
              $ids=array_unique($ids);
              $likes_data[$k]->tot=count($ids);
              $users=DB::table('user')->select('user_id','user_name','user_icon','user_ismodeler')->whereIn('user_id',$ids)->get();
              $users=DB::table('likes')->select('user.user_id','user.user_name','user.user_icon','user_ismodeler')->leftJoin('user','likes.like_uid','=','user.user_id')->where(['likes.like_eid'=>$v->like_eid])->orderBy('likes.id','DESC')->limit(4)->get();
              foreach($users AS $uk=>$uv){
                $users[$uk]->icon=$uv->user_icon?$this->getOssPath($uv->user_icon)->oss_path:'';
                $users[$uk]->ism=$uv->user_ismodeler?1:0;
              }
              
              $likes_data[$k]->users=$users;
              $likes_data[$k]->cate='1';
            }
            
           $you_data = array_merge($comment_data,$likes_data);
          
           $sort = [  
              'direction' => 'SORT_DESC',  
              'field'     => 'latest_time',  
            ];
           $arrSort = array();  
           foreach($you_data AS $uniqid => $row){  
            foreach($row AS $key=>$value){  
              $arrSort[$key][$uniqid] = $value;  
            }   
          }  
          if($you_data && $sort['direction']){  

            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $you_data);  
          }  

           
           $event_list=[
               'follow'=>$follow_data,
               'comment'=>$comment_data,
               'likes'=>$likes_data,
               'you_data'=>$you_data,
               
           ];
 
            $message_list=DB::table('chat')->where(['to_uid'=>$uid])->groupBy('from_uid')->orderBy('id','DESC')->get();


            //dd($message_list);
           foreach($message_list AS $k=>$v){
               $user_info=DB::table('user')->select('user_name','user_lastname','user_icon','user_ismodeler')->where('user_id',$v->from_uid)->first();
               $message_list[$k]->user_icon=$user_info->user_icon ? $this->getOssPath($user_info->user_icon)->oss_path : '';
               $message_list[$k]->user_name=$user_info->user_name.' '.$user_info->user_lastname;
               $message_list[$k]->ism=$user_info->user_ismodeler?1:0;
              // $latest_message=DB::table('chat')->where(['to_uid'=>$uid,'from_uid'=>$message_list[$k]->from_uid,'to_uid'=>$uid])->orderBy('id','DESC')->first();
               $latest_message=DB::table('chat')->where(['from_uid'=>$uid,'to_uid'=>$message_list[$k]->from_uid])->orWhere(['to_uid'=>$uid,'from_uid'=>$message_list[$k]->from_uid])->orderBy('id','DESC')->first();
               //dd($latest_message);
               $message_list[$k]->latest_message=$latest_message->content;
               if($v->read==0){
                $notices_num++;
               }

           }

           //echo $uid;
           //dd($message_list);


           $notices=[
               'news_list'=>$news,
               'event_list'=>$event_list,
               'message_list'=>$message_list,
               'notice_num'=>$notices_num,
               'notice_nums'=>$n_nums,
               ];
            //dd($notices);
           return $notices;
    }
    public function getCart($uid){

        $cart=DB::table('orders')->where(['order_uid'=>$uid,'order_status'=>0])->get();
        $cart_cash=0;
        foreach($cart AS $k=>$v){
            $element=DB::table('element')->select('element_id','element_price','element_cover_id','element_name','user_id','element_currency')->where('element_id',$v->order_eid)->first();
            if($element){
            $cart[$k]->element_id=$element->element_id;
            $cart[$k]->element_price=$element->element_price;
            $oss_item=DB::table('oss_item')->where('oss_item_id',$element->element_cover_id)->first();
            $cart[$k]->element_icon=$oss_item->oss_path;
            $cart[$k]->element_name=$element->element_name;
            $user=DB::table('user')->where('user_id',$element->user_id)->first();
            $cart[$k]->username=$user->user_name.' '.$user->user_lastname;
            $cart[$k]->element_price=Tools::trans_currency($element->element_price,$element->element_currency);
            $cart_cash+=$v->element_price;
            }
        }

        $cart_info=[
            'cart_list'=>$cart,
            'cart_tot'=>count($cart),
            'cart_cash'=>$cart_cash,
        ];


        return $cart_info;
    }
    public function ossPath($id,$size='300'){
        if(!$id){
            return env('APP_URL').ApiConf::DEFAULT_IMG;
        }elseif(1<=$id && $id<=12){
            return env('APP_URL').'/images/novahub/'.$id.'.png';
        }else{
            $item = Ossitem::find($id);
            if($size=='-1'){
                $size=null;
            }else{
                $size = $this->qiNiuwebp($size);
            }
            return $this->getOssUrl($item->oss_key).$item->oss_path.$size;
        }
    }
    public function downPath($id,$size=''){
        if(!$id){
            return '';
        }
        $item = Ossitem::find($id);
        return $this->getOssUrl($item->oss_key).$item->oss_path.$size;

    }
    /**
     * 获取客户端ip
     * @return array|false|string
     */
    public function clientIP()
    {
        $cIP = getenv('REMOTE_ADDR');
        $cIP1 = getenv('HTTP_X_FORWARDED_FOR');
        $cIP2 = getenv('HTTP_CLIENT_IP');
        $cIP1 ? $cIP = $cIP1 : null;
        $cIP2 ? $cIP = $cIP2 : null;
        return $cIP;
    }
    /**
     * 判断浏览器返回webp图片
     * @param $size
     * @return string
     */
    private function qiNiuwebp($size){
        if(strpos($_SERVER['HTTP_USER_AGENT'],"Chrome")>0  && strpos($_SERVER['HTTP_USER_AGENT'],"Edge")== 0){
            return $size = '?imageMogr2/auto-orient/thumbnail/'.$size.'/format/webp/interlace/1/blur/1x0/quality/80';
        }else{
            return $size = '?imageMogr2/auto-orient/thumbnail/'.$size.'/interlace/1/blur/1x0/quality/80';
        }
    }

    /**
     * 根据ip地区获取不同节点的资源
     * @param $item
     * @return string
     */
    private function getOssUrl($item){
        $country = $this->checkIp();
        if($country=='CN'){         //国内节点
            $paths = [
                'elements'  =>'elements-cn.novaby.com',
                'targets'  =>'targets-cn.novaby.com',
                'test'  =>'test-cn.novaby.com',
            ];
        }else{
            $paths = [
                'elements'  =>'elements-us.novaby.com',    //国外节点
                'targets'  =>'targets-us.novaby.com',    //国外节点
                'test'  =>'test-us.novaby.com',    //国外节点
            ];
        }
        return 'https://'.$paths[$item]."/";
    }

    /**
     * 返回ip对应的国家
     * @return mixed
     */
    public function checkIp()
    {
        //geo库判断ip
        $gi = geoip_open('GeoIP.dat',GEOIP_STANDARD);
        $data = geoip_country_code_by_addr($gi,$this->clientIP());
        geoip_close($gi);
        return $data;
        //淘宝接口判断ip
//        $result = @file_get_contents("http://ip.taobao.com/service/getIpInfo.php?ip=".$ip);
//        $result = json_decode($result);
//        return $result->data->country_id;
    }
}
