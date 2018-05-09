<?php

namespace App\Http\Controllers\NewAdmin;

use App\Events\BuildingEvent;
use App\Model\BuildDaily;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Storage;
use ZipArchive;
use App\libs\Tools;
use App\libs\OSSManager;
use DB;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }
    /**
     * 项目列表
     */
    public function index()
    {

        $projects = Project::orderBy('updated_at','DESC')->paginate(20);
        $projects->map(function ($item){
            $item->user = $this->userType($item->user);
            return $item;
        });
        return view('newadmin.project.index')->with('projects',$projects);
    }

    /**
     *
     * @param $id
     * @return
     */
    public function trans($id)
    {
        $project = Project::find($id);
        $project->prj_photos = $this->pathArr($project->prj_photos);
        $user_ids = PrjApply::where('prj_id',$project->prj_id)->pluck('user_id')->all();
        if(count($user_ids)>1) {
            $project->prj_users = User::whereIn('user_id',$user_ids)->get()->all();
        }elseif(count($user_ids)==1){
            $project->prj_user = User::where('user_id',$user_ids)->first();
        }else{

        };
        if($project->prj_attachment==null) {
            $project->prj_files = null;
        }else{
            $project->prj_files = $this->pathArr($project->prj_attachment);
        }
        $buildDaly = BuildDaily::where('bd_pid',$project->prj_id)->orderBy('bd_id','DESC')->first();
        $bid = 0;
        if($buildDaly && !$buildDaly->bd_attachment_trans){
           $bid = $buildDaly->bd_id;
            //$_oss = $this->getOssPath($buildDaly->bd_attachment);
           $project->day_attachment =$this->ossPath($buildDaly->bd_attachment);

        }
        $project->bdid = $bid;

        return view('newadmin.project.trans')->with('project',$project);
    }
    public function addUser(Request $request)
    {
        $this->validate($request,[
            'user'=>'required|exists:User,user_id',
            'day'=>'required',
            'hour'=> 'required',
            'price'=> 'required'
        ]);
        $apply = new PrjApply();
        $apply->prj_id = $request->get('id');
        $apply->user_id = $request->get('user');
        $apply->apply_time = time();
        $apply->apply_cost_time = $request->get('time');
        $apply->apply_cost_hour = $request->get('hour');
        $apply->apply_price = $request->get('price');
        $apply->apply_status = 1;
        $apply->save();
        return redirect('/admin/project/trans/'.$request->get('id'));
    }
    /**
     *项目详情
     * @param $id
     * @return
     */
    public function detail($id)
    {
        $project = Project::find($id);
        $project->prj_photos = $this->pathArr($project->prj_photos);
        $project->user = $this->userType($project->user);
        if($project->prj_modeler!=null)
        {
            $apply = PrjApply::where(['prj_id' => $project->prj_id,'user_id'=>$project->prj_modeler])->first();
            $project->price = $project->prj_models_tot*$apply->apply_price;
            $project->endtime = $apply->apply_cost_time*3600*24;
        }
        $user_applys = PrjApply::where('prj_id',$project->prj_id)->get();
        if($user_applys) {
            $project->prj_users = $user_applys->map(function ($item){
                $item->user = $this->userType($item->user);
                return $item;
            })->all();
        }else{

        };
        if($project->prj_attachment==null) {
            $project->prj_files = null;
        }else{
            $project->prj_files = $this->pathArr($project->prj_attachment);
        }

        $buildDalys = BuildDaily::where('bd_pid',$project->prj_id)->orderBy('bd_id','DESC')->get();
        if($buildDalys){
            $arr = array();
            foreach ($buildDalys as $buildDaly)
            {
                $buildDaly->bd_attachment = $this->ossPath($buildDaly->bd_attachment);
                $arr[]= $buildDaly;
            }
            $project->day_attachment =$arr;
        }
        $project->resolvedMark = $project->mark->filter(function ($item){
            return $item->status_jia == 2;
        });
        return view('newadmin.project.detail')->with('project',$project);
    }

    /**
     * 用户类型不同，信息不同
     * @param $user
     */
    public function userType($user)
    {
        if($user->user_type==4) {
            $user->user_name = $user->company_name;
            $user->homepage = $user->user_page_id;
        }else{
            $user->user_name = $user->user_name.' '.$user->user_lastname;
            $user->homepage = $user->user_page_id!=null?'http://www.novaby.com/homepage/'.$user->user_page_id:'';
        }
        return $user;
    }
    /**
     * 返回图片、模型地址数组
     * @param $strings
     * @return array
     */
    private function pathArr($strings)
    {
        $arr = array();
        $values = explode(',',$strings);
        if(count($values)>1)
        {
            for($i=0;$i<count($values);$i++)
            {
                foreach($values as $photo)
                {

                    $arr[$i] = $this->ossPath($photo);
                }
            }
            return $arr;
        }
        else
        {
            return $this->ossPath($strings);
        }

    }

    public function uploadAndTrans(Request $req){
        $pid = $req->get('pid',0);
        if($pid==0){
            echo "error";
            exit;
        }
        $file = $req->file("file");
        $originalName=$file->getClientOriginalName();


        if(!$file){
            echo "no uploaded file found ";
            exit;
        }
        if ($file->isValid()) {
            $ext = $file->getClientOriginalExtension();
            $exts = ['obj','fbx','stl','zip'];
            if(!in_array($ext,$exts)){
                return response()->json(['code'=>-1,'msg'=>"wrong foramt"]);
            }
            switch($ext){
                case 'zip':
                    $realPath = $file->getRealPath();
                    $filename_base_top = date('YmdHis');
                    $filename_base = $filename_base_top . '/' . uniqid() ;
                    $filename = $filename_base. '.' . $ext;
                    $storeres = Storage::disk('tmp')->put($filename, file_get_contents($realPath));
                    if($storeres){
                        $zip_path = Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename;
                        $extrato_path =Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base;
                        $zip = new ZipArchive;
                        $zipres = $zip->open($zip_path);
                        $imgexts = ['jpg','png','gif','jpeg'];
                        $objs = ['obj','fbx','stl','3dx','dae','3ds'];
                        $model_format='';
                        if($zipres){
                            $zip->extractTo($extrato_path);
                            $files = Tools::read_dir_queue($extrato_path);

                            $has_model = 0;
                            foreach($files AS $k=>$v){
                                if(is_file($v)){
                                    $ck_file = str_replace(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base.'/',"",$v);
                                    $ck_fileinfo = explode('/',$ck_file);
                                    $ck_filename = end($ck_fileinfo);
                                    $ck_exts = explode(".",$ck_filename);
                                    if(in_array(strtolower(end($ck_exts)),$objs)){
//                                        $name = $extrato_path.'/'.$ck_fileinfo[0].'/'.$ck_exts[0].'_test.obj'; //转换后的名字
//                                        $command = 'sudo python3 /opt/export.py export '.$v." ".$name;
//                                        exec($command,$ras,$value);
//                                        if($value==1)
//                                        {
//                                            return ('convert errot ,try again');
//                                        }else{
//                                            $has_model=1;
//                                        }
//                                        unlink($v);
                                        $model_format = end($ck_exts);
                                        $has_model++;
                                    }
                                }
                            }
                            if($has_model==0){
                                return response()->json(['code'=>-1,'msg'=>"Please upload single file(.fbx, .obj) or file + materials(.zip, .rar,.7z)"]);
                            }
                            $ossmgr = new OSSManager();
                            $oss_base_path = date('YmdHis/');
                            $oss_zip_path =$oss_base_path.$originalName;
                            $upres = $ossmgr->upload($oss_zip_path,'targets',$zip_path);
                            if($upres){
                                $_tid = DB::table('oss_item')->insertGetId([
                                    'oss_key'=>'targets',
                                    'oss_path'=>$oss_zip_path,
                                    'oss_item_uid'=>0,
                                    'size'=>0
                                ]);
                                $work_model_id = $_tid;
                            }


                            $work_obj_id=[];
                            $work_mets_id=[];
                            foreach($files AS $k=>$v){
                                if(strpos($v,'.')===false){
                                    continue;
                                }

                                $rfile = str_replace(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base.'/',"",$v);
                                $oss_file_path =$oss_base_path.$rfile;
                                $upres = $ossmgr->upload($oss_file_path,'elements',$v);
                                if($upres){


                                    $cap = filesize($v);
                                    $_fs = explode("/",$v);
                                    $_fname = $_fs[count($_fs)-1];
                                    $_exts = explode(".",$_fname);
                                    $_ext = $_exts[count($_exts)-1];
                                    if(in_array($_ext,$imgexts)){
                                        $size = getimagesize($v);
                                    }else{
                                        $size = [0,0];
                                    }
                                    $_tid = DB::table('oss_item')->insertGetId([
                                        'oss_key'=>'elements',
                                        'oss_path'=>$oss_file_path,
                                        'oss_item_uid'=>0,
                                        'width'=>$size[0],
                                        'height'=>$size[1],
                                        'size'=>$cap,
                                    ]);
                                    if(in_array($_ext,$objs)){
                                        $work_obj_id[]=$_tid;
                                    }else{
                                        $work_mets_id[]=$_tid;
                                    }

                                }
                            }

                            $work_obj_id = implode(",",$work_obj_id);
                            $work_mets_id = implode(",",$work_mets_id);
                            $obj_id = DB::table('work_detail')->insertGetId(
                                [
                                    'w_id'=>0,
                                    'w_objs'=>$work_obj_id,
                                    'w_mets'=>$work_mets_id,
                                    'w_format'=>$model_format
                                ]
                            );
                            $zip->close();
                             Tools::deleteDir(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base_top);
                            rmdir(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base_top);
                            $buildDaly = BuildDaily::find($pid);
                            $buildDaly->bd_attachment_trans = $obj_id;
                            $buildDaly->bd_pub = 1;
                            $buildDaly->save();
                            $project = Project::find($buildDaly->bd_pid);
                            $content = 'Congratulations! Your milestone preview has been uploaded successfully.';
                            $url = $_SERVER['REDIRECT_URL'].'/proposal-b/'.$project->prj_id;
                            \Event::fire(new BuildingEvent($project->prj_id,$project->modeler,$content,$url));
                            return redirect()->back();

                        }
                    }
                    break;
                default:
                    $ossmgr = new OSSManager();
                    $realPath = $file->getRealPath();
                    $filename_base_top = date('YmdHis');
                    $filename_base = $filename_base_top . '/' . uniqid() ;
                    $filename = $filename_base. '.' . $ext;
                    $storeres = Storage::disk('tmp')->put($filename, file_get_contents($realPath));
                    if(!$storeres){
                        die('upload error!');
                    }
                    $file_path = Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename;
                    $oss_base_path = date('YmdHis/');
                    $target_id    = Tools::guid();
                    $oss_file   = $target_id.'.'.$ext;
                    $oss_zip_path =$oss_base_path.$oss_file;
                    $upres = $ossmgr->upload($oss_zip_path,'targets',$file_path);
                    if($upres){
                        $_tid = DB::table('oss_item')->insertGetId([
                            'oss_key'=>'targets',
                            'oss_path'=>$oss_zip_path,
                            'oss_item_uid'=>0,
                            'size'=>0
                        ]);
                        if($_tid){
                            $obj_id = DB::table('work_detail')->insertGetId(
                                [
                                    'w_id'=>0,
                                    'w_objs'=>$_tid,
                                    'w_mets'=>'',
                                ]
                            );
                        }
                        $buildDaly = BuildDaily::find($pid);
                        $buildDaly->bd_attachment_trans = $obj_id;
                        $buildDaly->bd_pub = 1;
                        $buildDaly->save();
                        $project = Project::find($buildDaly->bd_pid);
                        $content = 'Congratulations! Your milestone preview has been uploaded successfully.';
                        $url = $_SERVER['REDIRECT_URL'].'/proposal-b/'.$project->prj_id;
                        \Event::fire(new BuildingEvent($project->prj_id,$project->modeler,$content,$url));
                        return redirect()->back();
                    }
                    break;
            }
        }else{
            die("invalid file ");
        }
    }
}
