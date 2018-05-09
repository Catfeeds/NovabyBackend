<?php

namespace App\Http\Controllers\Api\Novahub;

use App\Events\NotifyEvent;
use App\Http\Controllers\Api\BaseApiController;
use App\Jobs\ProjectModelTiming;
use App\Jobs\ProjectTrans;
use App\libs\Tools;
use App\Model\BuildAttach;
use App\Model\BuildDaily;
use App\Model\BuildImage;
use App\Model\BuildMark;
use App\Model\MarkResponse;
use App\Model\Message;
use App\Model\Notify;
use App\Model\Ossitem;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\ProjectInvite;
use App\Model\ProjectUser;
use App\Model\User;
use App\Model\WorkDetail;
use App\Model\WorkUpload;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use Dingo\Api\Exception\ResourceException;

class UploadApiController extends BaseApiController
{
    /**
     * 项目申请
     * @return mixed
     */
    public function apply()
    {
        $id = Input::get('id');
        $project = Project::find($id);
        $apply = PrjApply::where(['prj_id'=>$id,'user_id'=>$this->_user->user_id,'is_apply'=>1])->first();
        if($project->prj_progress>1 || $project->prj_permission==3 || $apply){
            return $this->jsonErr('can`t apply');
        }else {
            $apply2 = PrjApply::where(['prj_id'=>$id,'user_id'=>$this->_user->user_id])->first();
            if($apply2){
                $apply = $apply2;
            }else{
                $apply = new PrjApply();
            }
            $apply->user_id = $this->_user->user_id;
            $apply->prj_id = $id;
            $apply->apply_status = 0;
            $apply->is_apply=1;
            $apply->prj_uptime = time();
            $apply->save();
//            $msg = new Message();
//            $msg->msg_from_uid=$this->_user->user_id;
//            $msg->msg_to_uid=$project->prj_uid;
//            $msg->msg_action=5;
//            $msg->msg_rid=$project->prj_id;
//            $msg->msg_time = time();
//            $msg->save();
            $url = env('CLIENT_BASE').'novahub/project/'.$project->prj_id.'/invite';
            Mail::later(5,'emailtpl.apply', ['user' =>$project->user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($project){
                $message->to($project->user->user_email)->subject('Good news! You\'ve received a apply for'.$project->prj_name.'!');
            });
        }
        return $this->jsonOk('ok','apply successful');
    }
    /**
     *获取NDA
     */
    public function getNDA()
    {
        $id = Input::get('id');
        $project = Project::find($id);
        $project->prj_views+=1;
        $project->save();
        $date = date('F j, Y');
        if($project && $project->prj_delete!=1){
            $name = $this->getName($project->user);
            $content = response(view('NDA')->with(['company_name'=>$name,'user_name'=>$this->getName($this->_user),'date'=>$date]))->getContent();
            return $this->jsonOk('ok',$content);
        }else{
            return $this->jsonErr('error','no this project or project has been deleted');
        }

    }
    /**
     * 签署NDA
     */
    public function NDA()
    {
        $id = Input::get('id');
        $act = Input::get('act',1);
        $project = Project::find($id);
        if($project && $project->prj_delete!=1){
            $apply = PrjApply::where(['prj_id'=>$id,'user_id'=>$this->_user->user_id])->first();
            $invite = ProjectInvite::where(['project_id'=>$id,'user_id'=>$this->_user->user_id])->first();
            if($act==1){        //同意
                if($apply){
                    if($project->prj_progress>1){
                        return $this->jsonErr('error','you have no permission');
                    }
                    if($apply->apply_status>0){
                        return $this->jsonErr('error','you have already completed NDA');
                    }
                    $apply->apply_status = 2;
                    $apply->user_role=9;
                    $apply->save();
                }elseif($invite){
                    if($invite->status==1){
                        return $this->jsonErr('error','you have already agree');
                    }elseif($invite->status==2){
                        return $this->jsonErr('error','you have already disagree');
                    }else{
                        $invite->status =1;
                        $invite->save();
                        $projectUser = new ProjectUser();
                        $projectUser->project_id = $id;
                        $projectUser->user_id = $this->_user->user_id;
                        switch ($project->prj_type)
                        {
                            case 1:
                                $projectUser->user_role = 8;
                                break;
                            default:
                                $projectUser->user_role = 5;
                                break;
                        }
                        $projectUser->save();
                    }
                }else{
                    return $this->jsonErr('error','error');
                }
            }else{      //不同意
                if($apply){
                    $apply->apply_status = 4;
                    $apply->save();
                }elseif($invite){
                    $invite->status=2;
                    $invite->save();
                }else{
                    return $this->jsonErr('error','error');
                }
            }
            return $this->jsonOk('ok','NDA is completed');
        }else{
            return $this->jsonErr('error','no this project');
        }
    }
    /**
     * 判断乙方报价状态
     * @return mixed
     */
    public function applyStatus()
    {
        $prj_id = Input::get('id');
        $user = $this->_user;
        $project = Project::find($prj_id);
        $apply = PrjApply::where(['prj_id'=>$prj_id,'user_id'=>$user->user_id])->first();
        if($apply)
        {
            if($project->prj_modeler!=0){
                $result['status'] = $apply->apply_status==1?5:$apply->apply_status;
            }else{
                $result['status'] = $apply->apply_status;
            }
            $result['apply_time'] = $apply->apply_cost_time?$apply->apply_cost_time:'';
            $result['apply_price'] = $apply->apply_price?$apply->apply_price:'';
        }else{
            return $this->jsonErr('error','no permission');
        }
        return $this->jsonOk('ok',['result'=>$result]);
    }

    /**
     * 报价
     * @param Request $request
     * @return mixed
     */
    public function offer(Request $request)
    {
        $user = User::find($this->_user->user_id);
        $project = Project::where('prj_id',$request->get('id'))->first();
        if($project->prj_progress>1){
            return $this->jsonErr('Project can`t apply');
        }else{
            $apply = PrjApply::where(['prj_id'=>$request->get('id'),'user_id'=>$this->_user->user_id])->first();
            if(!$apply || $apply->apply_status==0 || $apply->apply_status==4){
                return $this->jsonErr('error','You have not agree novaby\'s NDA');
            }else{
                $rules = array(
                    'time'=>'required|numeric|min:1',
                    'price'=>'required|numeric|min:0.01'
                );
                $result = $this->postValidate($request->all(),$rules);
                if($result) {
                    return $this->jsonErr($result);
                }
                $apply->apply_time = time();
                $apply->apply_cost_time = $request->get('time');
                $apply->apply_price = $request->get('price');
                $apply->apply_status = 1;
                $user->save();
                if($apply->save()){
                    $project->prj_update_jia = 1;
                    $project->save();
                    \Event::fire(new NotifyEvent(6,$project->prj_uid));
                    $url = env('CLIENT_BASE').'novahub/project/'.$project->prj_id.'/select';
                    Mail::send('emailtpl.apply', ['user' =>$project->user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($project){
                        $message->to($project->user->user_email)->subject('Good news! You\'ve received a bid for'.$project->prj_name.'!');
                    });
                    return $this->jsonOk('ok','Offer successful');
                }else{
                    return $this->jsonErr('Offer error');
                }
            }
        }

    }
    /**
     * project模型文件上传接口
     * @param Request $req
     * @return mixed
     */
    public function projectModelUpload(Request $req){
        $uid = $this->_user->user_id;
        $maxSize = 1024*1024*100;
        $id = $req->get('id',0);
        $build = BuildDaily::with('upload')->find($id);
        $build->bd_uid = $uid;
        $build->save();
        $file = $req->file('file','');
        if(!$file){
            return $this->jsonErr("no uploaded file found ");
        }
        if ($file->isValid()) {
            $file_size = $file->getSize();
            if($file_size>$maxSize){
                return response()->json(['code'=>-1,'msg'=>"file too large"]);
            }
            $ext = $file->getClientOriginalExtension();
            $exts = ['zip'];
            if(!in_array($ext,$exts)){
                return response()->json(['code'=>-1,'msg'=>"wrong foramt"]);
            }
            switch($ext){
                case 'zip':
                    $realPath = $file->getRealPath();
                    $filename_base_top = date('YmdHis').'/model';
                    $filename_base = $filename_base_top . '/' . explode('.',$file->getClientOriginalName())[0] ;
                    $filename = $filename_base. '.' . $ext;
                    $storeres = Storage::disk('tmp')->put($filename, file_get_contents($realPath));
                    if($storeres){
                        $zip_path = Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename;
                        $extrato_path =Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base;
                        $zip = new ZipArchive();
                        $zipres = $zip->open($zip_path);
                        $objs = ['obj','fbx','stl','3d','gltf','dae','3ds','blend'];
                        if($zipres){
                            $zip->extractTo($extrato_path);  //解压到指定目录
                            $zip->close();
                            $files = Tools::read_dir_queue($extrato_path);   //获取目录下所有文件
                            $has_model = 0;
                            foreach($files AS $k=>$v){  //遍历所有文件，找到模型
                                if(is_file($v)){
                                    $ck_file = str_replace($extrato_path.'/',"",$v);
                                    $ck_fileinfo = explode('/',$ck_file);
                                    $ck_filename = end($ck_fileinfo);
                                    $ck_exts = explode(".",$ck_filename);
                                    if(in_array(strtolower(end($ck_exts)),$objs)) {  //检测模型是否在格式列表里
                                        $has_model = 1;
                                        if($build->upload){
                                            $upload = $build->upload;
                                        }else{
                                            $upload = new WorkUpload();
                                        }
                                        $upload->daily_id = $build->bd_id;
                                        if(count($ck_fileinfo)==1)
                                        {
                                            $model_path = $extrato_path; //解压后只有模型
                                        }else{
                                            $model_path = $extrato_path.'/'.$ck_fileinfo[0];
                                        }
                                        $model_path = $extrato_path; //解压后只有模型
                                        $upload->zip_path = $zip_path;  //压缩包地址
                                        $upload->zip = $filename;       //压缩包文件名
                                        $upload->model_path = $model_path;  //模型路径
                                        $upload->file_size = $file_size;    //模型大小
                                        $upload->path = $filename_base_top; //文件夹
                                        $upload->save();
                                    }
                                }
                            }
                            if($has_model==0){
                                return response()->json(['code'=>-1,'msg'=>"Model format not allowed!"]);
                            }else{
                                $oss_zip_path =$filename;
                                $tid = DB::table('oss_item')->insertGetId([
                                    'oss_key'=>'targets',
                                    'oss_path'=>$oss_zip_path,
                                    'oss_item_uid'=>$build->bd_uid,
                                    'size'=> $file_size
                                ]);
                                $work_detail = new WorkDetail();
                                $work_detail->w_id = $build->bd_id;
                                $work_detail->w_zip = $tid;
                                $work_detail->save();
                            }
                        }
                    }
                    break;
            }
//            $build->bd_3d = 1;
            $build->save();
            return $this->jsonOk('upload successfully',['work_id'=>$build->bd_id]);
        }else{
            return $this->jsonErr("invalid file ");
        }
    }
    /**
     * 乙方上传
     * @param Request $request
     * @return mixed
     */
    public function buildUpload(Request $request)
    {
        $bd_id = $request->get('id');
        $daily = BuildDaily::where('bd_id',$bd_id)->first();
        if(!$daily){
            return $this->jsonErr('error','no this build');
        }else {
            $daily->bd_pubtime = time();
            $daily->bd_final = $request->get('isFinal', 0);
            $daily->save();
            $photos = $request->input('photos', []);
            $files = $request->input('files', []);
            $is_3d = $request->input('is_3d', 1);
            if ($is_3d=='false') {  //2D模型
                $daily->bd_3d=0;
                if ($daily->bd_final == 1) {       //2D最终上传需要上传模型包
                    BuildAttach::where('build_id',$bd_id)->delete();
                    foreach ($files as $item) {
                        $ossItem = new Ossitem();
                        $ossItem->oss_key = 'elements';
                        $ossItem->oss_path = $item['src'];
                        $ossItem->oss_item_uid = $this->_user->user_id;
                        $ossItem->oss_item_eid = 0;
                        $ossItem->size = $item['size'];
                        $ossItem->save();
                        $buildAttach = new BuildAttach();
                        $buildAttach->build_id = $bd_id;
                        $buildAttach->oss_item_id = $ossItem->oss_item_id;
                        $buildAttach->save();
                    }
                }
                $daily->bd_pub = 4;
            } else {
                $daily->bd_3d=1;
                $daily->bd_pub = 1;
                $job = (new ProjectTrans($bd_id))->delay(10);
                $this->dispatch($job);
            }
            BuildImage::where('build_id',$bd_id)->delete();
            if(isset($photos)){
                foreach ($photos as $item) {
                    $ossItem = new Ossitem();
                    $ossItem->oss_key = 'elements';
                    $ossItem->oss_path = $item['src'];
                    $ossItem->oss_item_uid = $this->_user->user_id;
                    $ossItem->oss_item_eid = 0;
                    $ossItem->size = $item['size'];
                    $ossItem->save();
                    $buildImage = new BuildImage();
                    $buildImage->build_id = $bd_id;
                    $buildImage->oss_item_id = $ossItem->oss_item_id;
                    $buildImage->save();
                }
            }
            $daily->save();
            return $this->jsonOk('ok', 'upload successful');
        }
    }

    /**
     * 发布模型给甲方
     * @param Request $request
     * @return mixed
     */
    public function buildPublish(Request $request)
    {
        $bd_id = $request->get('id');
        $daily = BuildDaily::where('bd_id',$bd_id)->first();
        if(!$daily){
            return $this->jsonErr('error','no model');
        }else{
            if($daily->bd_pub==2) {
                $daily->status=0;
                $daily->bd_pub = 4;
                $trans = WorkDetail::where('w_id',$bd_id)->orderBy('id','DESC')->first();
                $daily->bd_attachment = $trans->w_zip;
                $daily->bd_attachment_trans = $trans->id;
                $daily->save();
                $project = Project::find($daily->bd_pid);
                $project->prj_update_jia = 1;
                $project->save();
                if($daily->bd_final==1){
                    $job = (new ProjectModelTiming($bd_id))->delay(time()+3600*24*7);
                    $this->dispatch($job);
                }
            }
                return $this->jsonOk('ok','submit successful');
        }
    }
}
