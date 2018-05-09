<?php

namespace App\Http\Controllers\NewAdmin;

use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\libs\OSSManager;
use App\libs\Tools;
use App\Model\Tag;
use App\Model\User;
use App\Model\Work;
use App\Model\WorkReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use function MongoDB\BSON\toJSON;

class WorkController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 模型列表
     * @param $id
     * @return
     */
    public function index($id)
    {
        if($id==0)
        {
            $works = Work::where(['work_privacy'=>0,'work_detail'=>0])->orderBy('work_id','desc')->paginate(5);
            $work = $works->map(function($item){
                $item->work_cover = $this->ossPath($item->work_cover);
                $item->work_model = $this->downPath($item->work_model);
                return $item;
            });
        }else{
            $works = Work::where('work_privacy',0)->where('work_model','!=',0)->where('work_status','!=',null)->orderBy('created_at','desc')->paginate(5);
            $work = $works->map(function($item){
                $item->work_cover = $this->ossPath($item->work_cover);
                $item->work_model = $this->downPath($item->work_model);
                return $item;
            });
        }

        return view('newadmin.work.index')->with(['works'=>$works,'work'=>$work,'id'=>$id]);

    }

    /**
     * 忽略
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function ignore($id)
    {
        $work = Work::find($id);
        $work->work_trans = 1;
        $work->save();
        \Event::fire(new NotifyEvent(4,$work->work_uid));
        $user = User::find($work->work_uid);
        $url = env('CLIENT_BASE').'model/detail/'.$work->work_id;
        Mail::send('emailtpl.modelConvert', ['user' =>$user->user_name,'status'=>1,'url'=>$url,'model'=>$work->work_title], function ($message)use($user){
            $message->to($user->user_email)->subject(' Your model was converted successfully.');
        });
        return redirect('/admin/work/index/'.'1');
    }
    /**
     * 审核
     * @param $id
     * @param $status
     * @param $type
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function review($id,$status,$type)
    {
        $work = Work::find($id);
        $work->work_status = $status;
        $work->save();
        $user_id = $work->work_uid;
        $user = User::find($user_id);
        $user->user_works += 1;
        $user->save();
        \Event::fire(new NotifyEvent(2,$user_id));
        $url = env('CLIENT_BASE').'model/detail/'.$work->work_id;
        Mail::send('emailtpl.modelReview', ['user' =>$user->user_name,'status'=>1,'url'=>$url,'model'=>$work->work_title], function ($message)use($user){
            $message->to($user->user_email)->subject('Your model verification was successful!');
        });
        return redirect('/admin/work/index/'.$type);
    }
    public function reviewFaild(Request $request)
    {
        $work = Work::find($request->get('auth_id'));
        $work->work_status = 2;
        $work->save();
        $error = $work->review;
        if(isset($error))
        {
            WorkReview::destroy($error->id);
        }
        $review = new WorkReview();
        $data = collect($request->except(['type','_token','auth_id','_url']));
        $content = $data->filter(function ($item){
            return $item!=null;
        })->toJson();
        $review->wid = $request->get('auth_id');
        $review->content = $content;
        $review->save();
        $user_id = $work->work_uid;
        $user = User::find($user_id);
        $url = env('CLIENT_BASE').'model/detail/'.$work->work_id;
        Mail::send('emailtpl.modelReview', ['user' =>$user->user_name,'status'=>0,'url'=>$url,'model'=>$work->work_title], function ($message)use($user){
            $message->to($user->user_email)->subject('Your model verification was not successful.');
        });
        return redirect('/admin/work/index/'.$request->get('type'));
    }

    /**
     * @param Request $req
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function trans(Request $req)
    {
        $id = $req->get('id',0);
        $type = $req->get('type');
        if($id==0){
            echo "error";
            exit;
        }

        $file = $req->file("file");
        if(!$file){
            $work = Work::find($id);
            $work->work_detail = 0;
            $work->work_trans = 2;
            $work->save();
            return redirect('/admin/work/index/'.$type);
        }
        $originalName=$file->getClientOriginalName();

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
                        $zip = new \ZipArchive();
                        $zipres = $zip->open($zip_path);
                        $imgexts = ['jpg','png','gif','jpeg'];
                        $objs = ['obj','fbx','stl','3dx','json'];
                        $model_format='';
                        if($zipres){
                            $zip->extractTo($extrato_path);
                            $files = Tools::read_dir_queue($extrato_path);

                            $has_model = 0;
                            foreach($files AS $k=>$v){
                                if(is_file($v)){
                                    $ck_file = str_replace(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base.'/',"",$v);
                                    $ck_fileinfo = explode('/',$ck_file);
                                    $ck_filename = $ck_fileinfo[count($ck_fileinfo)-1];
                                    $ck_exts = explode(".",$ck_filename);
                                    if(in_array($ck_exts[count($ck_exts)-1],$objs)){
                                        $model_format = $ck_exts[count($ck_exts)-1];
                                        if($model_format=='mtl') $model_format='obj';
                                        $has_model++;
                                    }
                                }
                            }
                            if($has_model==0){
                                return response()->json(['code'=>-1,'msg'=>"Please upload single file(.fbx, .obj) or file + materials(.zip, .rar,.7z)"]);
                            }
                            $ossmgr = new OSSManager();
                            $oss_base_path = date('YmdHis/');
                            $target_id    = Tools::guid();
                            $oss_file   = $target_id.'.'.$ext;
                            $oss_zip_path =$oss_base_path.$oss_file;
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
                            $res = Tools::deleteDir(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base_top);
                            rmdir(Storage::disk('tmp')->getAdapter()->getPathPrefix().$filename_base_top);
                            $work = Work::find($id);
                            $work->work_detail = $obj_id;
                            $work->work_trans = 1;
                            $work->save();
                            \Event::fire(new NotifyEvent(4,$work->work_uid));
                            $user = User::find($work->work_uid);
                            $url = env('CLIENT_BASE').'model/detail/'.$work->work_id;
                            Mail::send('emailtpl.modelConvert', ['user' =>$user->user_name,'status'=>1,'url'=>$url,'model'=>$work->work_title], function ($message)use($user){
                                $message->to($user->user_email)->subject(' Your model was converted successfully.');
                            });
                            return redirect('/admin/work/index/'.$type);

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
                        $work = Work::find($id);
                        $work->work_detail = $obj_id;
                        $work->work_trans = 1;
                        $work->save();
                        \Event::fire(new NotifyEvent(4,$work->work_uid));
                        $user = User::find($work->work_uid);
                        $url = env('CLIENT_BASE').'model/detail/'.$work->work_id;
                        Mail::send('emailtpl.modelConvert', ['user' =>$user->user_name,'status'=>1,'url'=>$url,'model'=>$work->work_title], function ($message)use($user){
                            $message->to($user->user_email)->subject('Your model was converted successfully.');
                        });
                        return redirect('/admin/work/index/'.$type);
                    }
                    break;
            }
        }else{
            $work = Work::find($id);
            $work->work_detail = 0;
            $work->work_trans = 2;
            $work->save();
            $user = User::find($work->work_uid);
            $url = env('CLIENT_BASE').'model/detail/'.$work->work_id;
            Mail::send('emailtpl.modelConvert', ['user' =>$user->user_name,'status'=>0,'url'=>$url,'model'=>$work->work_title], function ($message)use($user){
                $message->to($user->user_email)->subject('Unfortunately, your model conversion failed.');
            });
            return redirect('/admin/work/index/'.$type);
        }
    }
    /**
     * 进入market
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function market($id)
    {
        $work = Work::find($id);
        $work->work_status = 2;
        $work->save();
        return redirect('/admin/work/index/0');
    }

    /**
     * 删除
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        $work = Work::find($id);
        $work->work_del = 1;
        $work->save();
        return redirect('/admin/work/index/0');
    }
    /**
     * market推荐
     * @param $id
     * @param $type
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function recommend($id,$type)
    {
        $work = Work::find($id);
        $work->work_recommend = time();
        $work->save();
        return redirect('/admin/work/index/'.$type);
    }
    /**
     * 首页推荐
     * @param $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function homeRecommend(Request $request)
    {
        $work = Work::find($request->get('id'));
        $work->work_homeRecommend = time();
        $work->save();
        return redirect('/admin/work/index/0');
    }

    /**
     * 模型详情
     * @param $id
     * @return
     */
    public function detail($id)
    {
        $work = Work::find($id);


        $work->work_cover = $this->ossPath($work->work_cover);
        $work->work_photos = $this->pathArr($work->work_photos);

        $tags = Tag::get();

        $work->work_tags =$work->work_tags? $this->Arr($work->work_tags,$tags):'';



        return view('newadmin.work.detail')->with('work',$work);
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

    /**
     * 返回关联数据的数组
     * @param $strings
     * @return array|int
     */
    private function Arr($strings,$model)
    {
        $arr = array();
        $values = explode(',',$strings);

        if(count($values)>1)
        {
            for($i=0;$i<count($values);$i++)
            {
                     $arr[$i] = $model->find($values[$i]);
            }

            return $arr;
        }
        else
        {


            return intval($strings);
        }
    }

}
