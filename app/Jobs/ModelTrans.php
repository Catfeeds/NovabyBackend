<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\libs\OSSManager;
use App\libs\QiNiuManager;
use App\libs\Tools;
use App\Model\Ossitem;
use App\Model\Work;
use App\Model\WorkDetail;
use App\Model\WorkUpload;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ModelTrans extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $work = Work::find($this->id);
        $upload = WorkUpload::where('work_id',$this->id)->first();
        $files = Tools::read_dir_queue($upload->model_path);   //获取目录下所有文件
        $objs = ['obj','fbx','stl','3d','gltf','dae','3ds','blend'];
        foreach($files AS $k=>$v){  //遍历所有文件，找到模型
            if(is_file($v)){
                $ck_file = str_replace($upload->model_path.'/',"",$v);
                $ck_fileinfo = explode('/',$ck_file);
                $ck_filename = end($ck_fileinfo);
                $ck_exts = explode(".",$ck_filename);
                if(in_array(strtolower(end($ck_exts)),$objs) && strtolower(end($ck_exts))!='gltf'){  //检测模型是否在格式列表里
                    if(count($ck_fileinfo)==1)
                    {
                        $name = $upload->model_path.'/'.'scene.gltf'; //转换后的路径
                    }else{
                        array_pop($ck_fileinfo);
                        $path = implode('/',$ck_fileinfo);
                        $name = $upload->model_path.'/'.$path.'/'.'scene.gltf'; //转换后的路径
                    }
                    $command = 'sudo  /usr/local/bin/assimp export '.str_replace(' ','\ ',$v)." ".str_replace(' ','\ ',$name).' -fgltf2 2>&1  >> log.txt'; //转换命令语句
                    exec($command,$ras,$value);  //执行shell命令
                    if($value==217)   //错误217
                    {
                        $work->work_trans = 2;
                        $upload->error = 'Convert error,try again'.$v;
                    }
                    elseif($value==137)  //错误137
                    {
                        $work->work_trans = 2;
                        $upload->error = 'File too large,conversion failed';
                    }
                    $work->work_trans = 1;
                    unlink($v);
                }elseif(in_array(strtolower(end($ck_exts)),$objs) && strtolower(end($ck_exts))=='gltf'){     //gltf格式直接通过
                    $work->work_trans =1;
                }
            }
        }
        $upload->save();
        $qiNiu1 = new QiNiuManager(1);
        $upres1 = $qiNiu1->upload($upload->zip,$upload->zip_path);
        if($upres1['error'])
        {
            $work->work_model = 0;
        }
        $oss_base_path = $upload->path.'/';
        $work_obj_id=[];
        $work_mets_id=[];
        $files = Tools::read_dir_queue($upload->model_path);
        $qiNiu2 = new QiNiuManager(0);
        foreach($files AS $k=>$v){
            if(strpos($v,'.')===false){
                continue;
            }
            $rfile = str_replace($upload->model_path.'/',"",$v);
            $oss_file_path =$oss_base_path.$rfile;
            if(!is_file($v)){
                continue;
            }
            $upres2 = $qiNiu2->upload($oss_file_path,$v);
            if(!$upres2['error']){
                $cap = filesize($v);
                $_fs = explode("/",$v);
                $_fname = $_fs[count($_fs)-1];
                $_exts = explode(".",$_fname);
                $_ext = $_exts[count($_exts)-1];
                $size=[0,0];
                $oss_item = new Ossitem();
                $oss_item->oss_key = $qiNiu2->key;
                $oss_item->oss_path = $oss_file_path;
                $oss_item->oss_item_uid = $work->work_uid;
                $oss_item->width = $size[0];
                $oss_item->height = $size[1];
                $oss_item->size = $cap;
                $oss_item->save();
                if(in_array(strtolower($_ext),$objs)){
                    $work_obj_id[]=$oss_item->oss_item_id;
                    $model_format = strtolower($_ext);
                }else{
                    $work_mets_id[]=$oss_item->oss_item_id;
                }
            }
        }
        $work_obj_id = implode(",",$work_obj_id);
        $work_mets_id = implode(",",$work_mets_id);
        $obj = new WorkDetail();
        $obj->w_id = 0;
        $obj->w_objs = $work_obj_id;
        $obj->w_mets = $work_mets_id;
        $obj->w_format = $model_format;
        $obj->save();
        $work->work_detail = $obj->id;
        $work->save();
        Tools::deleteDir(Storage::disk('tmp')->getAdapter()->getPathPrefix().$upload->path);
        rmdir(Storage::disk('tmp')->getAdapter()->getPathPrefix().$upload->path);

    }

    /**
     *
     */
    public function failed()
    {
        $work = Work::find($this->id);
        $work->work_trans =2;
        $work->save();
    }
}
