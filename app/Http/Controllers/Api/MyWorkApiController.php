<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ModelTrans;
use App\libs\ApiConf;
use App\Model\BuildDaily;
use App\Model\BuildMark;
use App\Model\Likes;
use App\Model\MarkResponse;
use App\Model\ModelCategory;
use App\Model\ModelImage;
use App\Model\Order;
use App\Model\WorkDetail;
use App\Model\WorkUpload;
use Illuminate\Http\Request;

use App\Model\Work;
use App\Model\Ossitem;
use App\Model\Tag;
use App\Model\Cate;
use App\Model\User;
use DateTime;
use DB;
use ZipArchive;
use Storage;
use App\libs\Tools;
use App\libs\OSSManager;
use Illuminate\Support\Facades\Input;
class MyWorkApiController extends BaseApiController
{
    public function showedit(Request $req){
        $wid = $req->get('wid');
        $work = Work::find($wid);
        $data = [
            'wid'=>$work->work_id,
            'title'=>$work->work_title,
        ];

        $_pics = [];
        if($work->work_photos){
            $_work_photos = explode(",",$work->work_photos);
            foreach($_work_photos AS $k=>$v){
                $pic = Ossitem::find($v);
                $_pics[] = [
                    'src'=>$this->getOssPath($pic->oss_path,'500'),
                    'id'=>$v,
                ];
            }
        }
        $data['photos']=$_pics;

        $attach  = Ossitem::find($work->work_model);
        $_attnames = explode("/",$attach->oss_path);
        $data['attach']=[
            'url'=>$this->getOssPath($attach->oss_path,'300'),
            'name'=>$_attnames[count($_attnames)-1],

        ];
        $data['licence']=[
            'id'=>$work->work_license,
            'txt'=>'free',
        ];
        $_tags = [];
        if($work->work_tags){
            $_work_tags = explode(",",$work->work_tags);
            foreach($_work_tags AS $k=>$v){
                $tag = Tag::find($v);
                $_tags[] = [
                    'name'=>$tag->tag_name,
                    'id'=>$v,
                ];
            }
        }

        $data['tags']=$_tags;
        $data['vertices']=$work->work_vertices;
        $data['faces']=$work->work_faces;
        $_feature = [];
        if($work->work_tags){
            $_work_tags = explode(",",$work->work_feature);
            foreach($_work_tags AS $k=>$v){
                $tag = Tag::find($v);
                $_feature[] = [
                    'name'=>$tag->tag_name,
                    'id'=>$v,
                ];
            }
        }
        $data['feature']=$_feature;
        $data['license']=$work->work_license;
        $data['price']=$work->work_price;
        $data['description']=$work->work_description;

        $_cates = [];
        if($work->work_cate){
            $_work_cates = explode(",",$work->work_cate);
            foreach($_work_cates AS $k=>$v){

                $cate = Cate::find($v);
                $_cates[] = [
                    'name'=>$cate->cate_name,
                    'id'=>$v,
                ];
            }
        }
        $data['cates']=$_cates;

        $_foramts = [];
        if($work->work_formats){
            $_work_formats = explode(",",$work->work_formats);
            foreach($_work_formats AS $k=>$v){
                $format = Cate::find($v);
                $_foramts[] = [
                    'name'=>$format->cate_name,
                    'id'=>$v,
                ];
            }
        }
        $data['foramts']=$_foramts;

        if($work){
            return $this->jsonOk('ok',['work'=>$data]);
        }else{
            return $this->jsonErr('failed');
        }
    }
    public function ossToken_att(){
        $id= ApiConf::OSS_ACCESS_KEY;
        $key= ApiConf::OSS_SECRET_KEY;
        $host = 'https://targets.oss-cn-hongkong.aliyuncs.com';

        $now = time();
        $expire = 100; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        $dir = 'upload/'.date('Ymd').'/';

        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        //echo json_encode($arr);
        //return;
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['OSSAccessKeyId'] = $key;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        return $this->jsonOk('ok',['osstoken'=>$response]);
    }
    public function ossToken(){
        $id= ApiConf::OSS_ACCESS_KEY;
        $key= ApiConf::OSS_SECRET_KEY;
        $host = 'https://element2.oss-cn-shanghai.aliyuncs.com';
        $host1 = 'https://target2.oss-cn-shanghai.aliyuncs.com';
        $now = time();
        $expire = 100; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        $dir = 'upload/'.date('Ymd').'/';

        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        //echo json_encode($arr);
        //return;
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['host1'] = $host1;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['OSSAccessKeyId'] = $key;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        return $this->jsonOk('ok',['osstoken'=>$response]);
    }

    private function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    public function myworks(){
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',10);
        $offset = ($page-1)*$page_size;
        //$sql = "select user_id,GROUP_CONCAT(element_id) ids,DATE_FORMAT(element_create_time,'%Y-%m-%d')  udate,count(element_id) AS tot from `element` group by udate ORDER BY element_id DESC   limit ".$offset.",".$page_size;
        $sql = "select work_uid,GROUP_CONCAT(work_id) ids,DATE_FORMAT(created_at,'%Y-%m-%d')  udate,UNIX_TIMESTAMP(created_at) update_time,count(work_id) AS tot from `works` WHERE work_uid=".$this->_user->user_id." AND work_del=0 AND work_cover!=0  group by udate ORDER BY update_time DESC   limit ".$offset.",".$page_size;
        $data = DB::select($sql);
        foreach($data AS $k=>$v){
            if($v->ids){
                $_ids = explode(",",$v->ids);
                $_models = Work::select('work_id','work_uid','work_title','work_cover','work_privacy','work_status','work_trans','work_model')->with('user')->whereIN('work_id',$_ids)->get();
                foreach($_models AS $k1=>$v1){
                    $_models[$k1]->cover = $this->getOssPath($v1->work_cover,'500');
                    $_models[$k1]->name = $v1->work_title;
                    $_models[$k1]->id = $v1->work_id;
                    $_models[$k1]->isprivacy = $v1->work_privacy;
                    $_models[$k1]->isrelease = $v1->work_status==1?1:0;
                    unset($_models[$k1]->work_title);
                    unset($_models[$k1]->work_cover);
                    if($v1->work_model)
                    {
                        if($v1->work_status==0 && $v1->work_trans==0){
                            $_models[$k1]->status = 0; //待转换
                        }elseif($v1->work_status==0 && $v1->work_trans==2){
                            $_models[$k1]->status = 1;//转换失败
                        }
                        if($v1->work_status==0 && $v1->work_trans==1){
                            if($v1->user)
                            {
                                if($v1->work_id==$v1->user->auth_model) {
                                    $_models[$k1]->status = 2; //模型师转换成功
                                }else {
                                    $_models[$k1]->status = 2; //转换成功
                                }
                            }else{
                                $_models[$k1]->status = 2; //转换成功
                            }
                        }elseif($v1->work_trans==1 && $v1->work_status==3){
                            $_models[$k1]->status = 3; //等待审核
                        }elseif($v1->work_status==1){
                            $_models[$k1]->status = 4; //审核成功
                        }elseif($v1->work_status==2){
                            if($v1->user){
                                if($v1->work_id==$v1->user->auth_model) {
                                    $_models[$k1]->status = 6; //模型师审核失败
                                }else {
                                    $_models[$k1]->status = 5; //审核失败
                                }
                            }else{
                                $_models[$k1]->status = 5; //审核失败
                            }
                        }
                    }else{
                        $_models[$k1]->status = 4; //审核成功
                    }
                    unset($_models[$k1]->work_status,$_models[$k1]->work_privacy,$_models[$k1]->work_uid,$_models[$k1]->user);
                }
            }
            $data[$k]->works = $_models;
            //$data[$k]->works1 = $_models1;
            unset($data[$k]->ids);

        }
        if(count($data)>0){
            return $this->jsonOk('ok',['works'=>$data,'pages'=>1]);
        }else{
            return $this->jsonErr("No More Data");
        }
    }

    public function zip(){
        $storage_path = storage_path().'/app/';
        $zip = new ZipArchive;
        $file = $storage_path.'a.zip';
        var_dump(fstat($file));
        exit;


        $zipres = $zip->open($file);

        if($zipres){

            $zip->extractTo($storage_path.'aaa');
            $zip->close();
        }
        exit;

    }

    /**
     * $type 0:转换  1:审核
     * @param Request $req
     * @return mixed
     */
    public function pubwork(Request $req){
        $key_map = [
            //2D图
            'rphotos',
            //模型授权类型
            'licence',
            //视频url
            'video_url',
            //模型分类
            'category',
            //模型标签
            'tags',
        ];
        $wid = $req->get('wid',0);
        $pics = $req->get('rphotos',[]);
        $name = $req->get('name','');
        $video = $req->get('video_url',NULL);
        $is_convert = $req->get('is_convert',0);
        if(!$name){
            return $this->jsonErr("name can't be blank");
        }
        $tags = $req->get('tags',[]);
        $license = $req->get('license',1);
        if($license==0){
            return $this->jsonErr("please select license");
        }
        if($video){
            $this->validate($req, [
                'video_url'=>'url'
            ]);
        }
        $description = $req->get('description',NULL);
        $permit = $req->get('permit',0);
        $privacy  = $req->get('privacy ',0);
        $type = $req->get('type');
        if($wid){
            $work = Work::find($wid);
            if($work->upload && $is_convert==1)
            {
                $job = (new ModelTrans($work->work_id))->delay(10);
                $this->dispatch($job);
            }
            if($work->work_model)
            {
                if($work->work_trans ==1)
                {
                    $work->work_status=1;
                }else{
                    $work->work_status=0;
                }
            }
        }else{
            $work = new Work();
            $work->work_status=1;
            $work->save();
        }
        $category = $req->get('category',0);
        if(!count($category)){
            return $this->jsonErr("category can\'t be empty");
        }else{
            $data = null;
            if(count($category)==1){
                $data = '("'.$work->work_id.'",'.$category[0].')';
            }else{
                foreach ($category as $item){
                    $data .= '("'.$work->work_id.'",'.$item.'),';
                }
            }
            ModelCategory::where('work_id',$work->work_id)->delete();
            $sql = 'insert into model_categories(work_id,category_id)value'.rtrim($data,',');
            DB::statement($sql);
        }
        if(!count($pics)){
            $cover = '1';
        }else{
            $_pics = [];
            $cover = 1;
            foreach($pics AS $k=>$v){
                $item = new Ossitem();
                $item->oss_key ='elements';
                $item->oss_path = $v['src'];
                $item->oss_item_uid = $this->_user->user_id;
                $item->size=0;
                $item->width=$v['width'];
                $item->height=$v['height'];
                if($item->save()){
                    $_pics[] = $item->oss_item_id;
                    if(isset($v['iscover']) && ($v['iscover']=='true'|| $v['iscover']==1)){
                        $cover=$item->oss_item_id;
                    }
                }
            }
            if(count($_pics)<1){
                $_pics[] = $cover;
            }
            $w_pics = implode(",",$_pics);
            $work->work_photos=$w_pics;
        }
        $work->work_uid= $this->_user->user_id;
        $work->work_cover=$cover;
        $work->work_title = $name;
//        $work->work_cate = $category;
        $work->work_tags = implode(",",$tags);
        $work->work_rigged = 0;
        $work->work_license = $license;
        $work->work_description = $description;
        $work->work_privacy = $privacy; //是否隐私,1是,0否
        $work->work_permit = $permit;   //是否允许下载，1是,0否
        $work->work_video=$video;
        $work->created_at = time();
        $res = $work->save();
        $user = User::find($this->_user->user_id);
        $auth = 0;
        if($req->get('auth')=='true'){
            $user->auth_model = $work->work_id;
            $auth = 1;
            $user->save();
        }else{
            if($wid == $user->auth_model){   //认证模型和传的模型参数一致，为申请模型师认证
                $user->user_type=2;
                $auth = 1;
                $user->save();
            }
        }
        if($res){
            if($work->work_privacy==0){
                $u = User::find($this->_user->user_id);
                $u->user_works +=1;
                $u->save();
            }
            $detail = $this->workDetail($work->work_id);
            return $this->jsonOk('publish successfully',['work'=>$detail,'wid'=>$work->work_id,'auth'=>$auth]);
        }else{
            return $this->jsonErr("publish failed");
        }
    }
    public function preview(){
        $wid = Input::get('wid',0);
        if(!$wid){
            return $this->jsonErr("not found");
        }
        $detail = $this->workDetail($wid);
        if(!$detail){
            return $this->jsonErr("not found");
        }
        return $this->jsonOk('ok',['work'=>$detail]);
    }
    public function wkDetail($id=1){
        $detail = $this->workDetail($id);
        if($detail){
            return $detail;
        }else{
            return [];
        }
    }
    private  function   workDetail($work_id){
        $work = Work::with('category')->find($work_id);
        if(!$work){
            return null;
        }
        $data = [
            'wid'=>$work->work_id,
            'title'=>$work->work_title,
        ];
        $auth = 0;
//        if($this->_user->auth_model == $work_id)
//        {
//            $auth = 1;
//        }
        $_pics = [];
        $_ps = $work->work_photos;
        if(count($_ps)){
            $_work_photos = explode(",",$_ps);
            $_work_photos = array_values((array_unique($_work_photos)));
            foreach($_work_photos AS $k=>$v){
                $pic = Ossitem::find($v);
                $_pics[] = [
                    'src'=>$this->getOssPath($pic->oss_item_id,'500'),
                    'url'=>$pic->oss_path,
                    'size'=>$pic->size,
                    'id' => $pic->oss_item_id,
                    'width'=>$pic->width?$pic->width:1000,
                    'height'=>$pic->height?$pic->height:1000,
                    'iscover'=>$v==$work->work_cover?1:0,
                ];
            }
        }
        $data['photos'] = $this->getOssPath($work->work_cover);
        $data['photos']=$_pics;
        if($work->work_model!=0){
            $attach = Ossitem::find($work->work_model);
            $_attnames = explode("/",$attach->oss_path);
            $data['model']=[
            'url'=>$this->getOssPath($attach->oss_item_id,'-1'),
            'name'=>end($_attnames),
            'src'=>$attach->oss_path,
            'id' => $work->work_model
        ];
        }else{
            $data['model']='';
        }
        $data['licence']=[
            'id'=>$work->work_license,
            'txt'=>'free',
        ];
        $tags = [];
        if($work->work_tags){
            $work_tags = explode(",",$work->work_tags);
            foreach($work_tags AS $k=>$v){
                if(is_numeric($v)){
                    $tag = Tag::find($v)?Tag::find($v)->tag_name:$v;
                }else{
                    $tag = $v;
                }
                $tags[] = $tag;
            }
        }
        $data['tags']=$tags;

        $data['vertices']=$work->work_vertices;
        $data['faces']=$work->work_faces;
        $feature = [];
        if($work->work_animation){
            $feature[]=['id'=>1,'name'=>'Animation'];
        }
        if($work->work_texture){
            $feature[]=['id'=>2,'name'=>'Texture'];
        }
        if($work->work_rigged){
            $feature[]=['id'=>3,'name'=>'Rigged'];
        }
        if($work->work_lowpoly){
            $feature[]=['id'=>4, 'name'=>'Low-poly'];
        }
        if($work->work_uvmap){
            $feature[]=['id'=>5, 'name'=>'UV-mapping'];
        }
        if($work->work_material){
            $feature[]=['id'=>6, 'name'=>'Material'];
        }
        $data['feature']=$feature;

        $data['license']=$work->work_license;
        $data['price']=$work->work_price?$work->work_price:0;
        $data['description']=$work->work_description?$work->work_description:'';
        $data['permit']=$work->work_permit;
        if($work->work_model)
        {
            if($work->work_status==0 && $work->work_trans==0){
                $data['status'] = 0; //待转换
            }elseif($work->work_status==0 && $work->work_trans==2){
                $data['status'] = 1;} //转换失败
            if($work->work_status==0 && $work->work_trans==1){
                $data['status'] = 2; //转换成功
            }elseif($work->work_trans==1 && $work->work_status==3){
                $data['status'] = 3; //等待审核
            }elseif($work->work_status==1){
                $data['status'] = 4; //审核成功
            }elseif($work->work_status==2){
                $data['status'] = 5; //审核失败
            }
        }else{
            $data['status'] = 4; //审核成功
        }
        //$data['status']=$work->work_status;
        $data['video']=$work->work_video?$work->work_video:'';

        $_cates = [];
        if($work->work_cate){
            $_work_cates = explode(",",$work->work_cate);
            foreach($_work_cates AS $k=>$v){
                $_cates = intval($v);
            }
        }
        if(count($work->category)){
            foreach($work->category AS $k=>$v){
                $_cates[] = intval($v->cate_id);
            }
        }
        $data['cate']=$_cates;
        $_foramts = [];
        if($work->work_formats){
            $_work_formats = explode(",",$work->work_formats);
            foreach($_work_formats AS $k=>$v){
                $format = Cate::find($v);
                $_foramts[] = [
                    'name'=>$format->cate_name,
                    'id'=>$v,
                ];
            }
        }

        $data['foramts']=$_foramts;

        $model_3d='';

        if($work->work_detail!=0){
            $model_3d=$this->getModelFilesById($work->work_detail);
        }
        if($model_3d){
            $model_3d->id = $work->work_detail;
        }
        if($data['model'])
        {
            $data['model']['model_3d']=$model_3d;
        }
        $data['has_error']=0;
        if($work->work_status==2)
        {
            $data['has_error']=1;
            $data['errors']=json_decode($work->review->content);
        }
//        $data['auth_model'] = $auth;
        return $data;
    }
    private function model_errors(){
        return[
            [
            'error_code'=>'ERROR_TITLE',
            'error_msg'=>'title is to short'
            ],
            [
                'error_code'=>'ERROR_CATE',
                'error_msg'=>'category is not corect!',
            ],
        ];
    }

    /**
     * 模型文件上传接口
     * @param Request $req
     * @return mixed
     */
    public function doupload(Request $req){
        $uid = $this->_user->user_id;
        $maxSize = 1024*1024*100;
        $id = $req->get('id',0);
        if($id)
        {
            $work = Work::with('upload')->find($id);
        }else{
            $work = new Work();
        }
        $work->work_status = null;
        $work->work_trans = 0;
        $work->work_uid = $uid;
        $work->save();
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
                        $zip = new ZipArchive;
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
                                    $work->work_title = $ck_exts[0];
                                    if(in_array(strtolower(end($ck_exts)),$objs)) {  //检测模型是否在格式列表里
                                        $has_model = 1;
                                        if($work->upload){
                                            $upload = $work->upload;
                                        }else{
                                            $upload = new WorkUpload();
                                        }
                                        $upload->work_id = $work->work_id;
                                        if(count($ck_fileinfo)==1)
                                        {
                                            $model_path = $extrato_path; //解压后只有模型
                                        }else{
                                            $model_path = $extrato_path.'/'.$ck_fileinfo[0];
                                        }
                                        $model_path = $extrato_path;
                                        $upload->zip_path = $zip_path;
                                        $upload->zip = $filename;
                                        $upload->model_path = $model_path;
                                        $upload->file_size = $file_size;
                                        $upload->path = $filename_base_top;
                                        $upload->save();
                                    }
                                }
                            }
                            if($has_model==0){
                                    return response()->json(['code'=>-1,'msg'=>"Model format not allowed!"]);
                            }else{
                                $oss_zip_path =$filename;
                                    $_tid = DB::table('oss_item')->insertGetId([
                                        'oss_key'=>'targets',
                                        'oss_path'=>$oss_zip_path,
                                        'oss_item_uid'=>$work->work_uid,
                                        'size'=> $file_size
                                    ]);
                                    $work->work_model = $_tid;
                            }
                    }
                }
                break;
            }
            $work->save();
            return $this->jsonOk('upload successfully',['work_id'=>$work->work_id]);
        }else{
            return $this->jsonErr("invalid file ");
        }
    }

    public function deleteWork(Request $req){
        $id = $req->get('id',0);
        if($id==0){
            return $this->jsonErr("missing parameter");
        }
        $work = Work::where(['work_uid'=>$this->_user->user_id,'work_id'=>$id,'work_del'=>0])->first();
        if(!$work){
            return $this->jsonErr("not found");
        }
        $work->work_del=1;
        if($work->save()){
           $user = User::find($this->_user->user_id);
           $user->user_works=$user->user_works-1;
           if($user->save()){
               return $this->jsonOk('delete successfully',[]);
           }
        }

        return $this->jsonErr("delete failed");

    }

    public function mydownloads(){
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',10);
        $offset = ($page-1)*$page_size;
        $tot = DB::table('orders')->where(['order_uid'=>$this->_user->user_id])->count();

        $list = DB::table('orders')->leftJoin('works','orders.order_eid','=','works.work_id')->leftJoin('user','orders.order_owner','=','user.user_id')
            ->leftJoin('rates','rates.eid','=','works.work_id')
            ->select('works.work_id','works.work_cover','works.work_title','works.work_price','user.user_name','user.user_lastname','user.user_id','orders.order_time','orders.order_owner','works.work_model','rates.stars')
            ->where(['orders.order_uid'=>$this->_user->user_id])
            ->take($page_size)->skip($offset)
            ->get();


        foreach($list AS $k=>$v){
            if(!$v->work_model || !$v->work_cover){
                continue;
            }
            $list[$k]->work_price = $list[$k]->work_price>0?$list[$k]->work_price:'Free';
            $list[$k]->stars = $list[$k]->stars?$list[$k]->stars:0;
            $list[$k]->downurl = $this->getOssPath($v->work_model);
            $list[$k]->cover = $this->getOssPath($v->work_cover,'300');
            $list[$k]->user_id=$v->order_owner;
            $list[$k]->dl_time_year = date('d,M,Y',strtotime($v->order_time));
            $list[$k]->dl_time_day = date('H:i:s',strtotime($v->order_time));
            unset($list[$k]->works_cover);
            unset($list[$k]->work_model);
            //unset($list[$k]->work_model);
            //unset($list[$k]->work_model);


        }


        if($list){
            //$hasMore = intval($tot != ($page-1)*$page_size + count($list));

            $_c = ($page-1)*$page_size+count($list);
            if($_c<0){
                $hasMore=0;
            }else{
                $hasMore = intval($tot!=($page-1)*$page_size+count($list));
            }
            return $this->jsonOk('ok',['lists'=>$list,'tot'=>$tot,'pages'=>ceil($tot/$page_size),'page_size'=>$page_size,'curr_page'=>$page,'has_more'=>$hasMore]);
        }else{
            return $this->jsonErr('No More Data');
        }
    }
    public function rate(Request $req){
        $rate = $req->get('stars',0);
        $work_id = $req->get('id',0);
        if($rate==0 || $work_id==0){
            return $this->jsonErr("failed");
        }
        $ck = DB::table('rates')->where(['eid'=>$work_id,'uid'=>$this->_user->user_id])->count();
        if($ck){
            return $this->jsonErr("you have rated");
        }
        $res = DB::table('rates')->insertGetId(
            [
                'eid'=>$work_id,
                'uid'=>$this->_user->user_id,
                'stars'=>$rate,
                'oid'=>0,
            ]);
        if($res){
            return $this->jsonOk("rate ok",['rates'=>$rate]);
        }else{
            return $this->jsonErr("rate failed");
        }
    }
    public function delete(Request $req){
        $id = $req->get('id');
        $work = Work::find($id);
        if(count($work)!=1){
            return $this->jsonErr("not found");
        }
        if($work->work_uid!=$this->_user->user_id){
            return $this->jsonErr("not found");
        }
        $work->work_del = 1;
        if($work->save()){
            return $this->jsonOk("delete successfully",[]);

        }else{
            return $this->jsonErr("delete failed!!");
        }
    }

    public function likework(Request $req){
        $work_id = $req->get('work_id',0);
        if($work_id==0){
            return $this->jsonErr("wrong param");
        }
        $work = Work::find($work_id);
        if(!$work){
            return $this->jsonErr("no work found");
        }
        $likes = Likes::where('like_eid',$work_id)->where('like_uid',$this->_user->user_id)->first();

        if($likes){
            if($likes->liked){
                $likes->liked=0;
                if($likes->save()){

                    $like_nums= Likes::where('like_eid',$work_id)->where('liked',1)->count();
                    return $this->jsonOk('ok',['result'=>0,'likenum'=>$like_nums]);
                }
            }else{
                $likes->liked=1;
                if($likes->save()){

                    $like_nums= Likes::where('like_eid',$work_id)->where('liked',1)->count();
                    if($this->_user->user_id!=$work->work_uid){
                        $this->addMsg($this->_user->user_id,$work->work_uid,4,$work->work_id);
                    }
                    return $this->jsonOk('ok',['result'=>1,'likenum'=>$like_nums]);
                }
            }

        }else{
            $likes = new Likes();
            $likes->like_eid=$work_id;
            $likes->like_uid=$this->_user->user_id;
            $likes->like_to_uid=$work->work_uid;
            $likes->liked=1;
            if($likes->save()){
                $like_nums= Likes::where('like_eid',$work_id)->where('liked',1)->count();
                if($this->_user->user_id!=$work->work_uid){
                    $this->addMsg($this->_user->user_id,$work->work_uid,4,$work->work_id);
                }
                return $this->jsonOk('ok',['result'=>1,'likenum'=>$like_nums]);
            }

        }

    }

    /**
     * 保存模型修改
     * @param Request $req
     * @return mixed
     */
    public function modelEdit(Request $req){
        $id = $req->get('id');
        $model = WorkDetail::find($id);
        if(!$model){
            return $this->jsonErr("not found");
        }
        $data = $req->get('data',null);
        $model->work_model_edit=$data;
        if($model->save()){
            return $this->jsonOk("ok",[]);
        }else{
            return $this->jsonErr("save error!");
        }
    }
    /**
     * 获取修改模型信息
     * @return mixed
     */
    public function modelEditData(){
        $id = Input::get('id');
        $model = WorkDetail::find($id);
        if(!$model){
            return $this->jsonErr("not found");
        }
        return $this->jsonOk("ok",['data'=>json_decode($model->work_model_edit)]);
    }
    public function tags(){
        $cate = Input::get('cate',0);
        $where = ' 1 =1 ';
        if($cate){
            if($cate==43){
                $where=' pid = '.$cate;
            }else{
                $where=' pid = 43';
            }
        }
        $tags = Tag::whereRAW($where)->get();
        foreach($tags AS $k=>$v){
            $tags[$k]->status=false;
        }
        return $this->jsonOk("ok",['tags'=>$tags]);
    }

    public function download(Request $req){
        $workid = $req->get('work_id',0);
        if($workid==0){
            return $this->jsonErr("wrong id");
        }
        $work = DB::table('works')->select('work_model','work_uid')->where(['work_id'=>$workid])->first();
        if(!$work){
            return $this->jsonErr("not found");
        }
        if($work->work_model==0){
            return $this->jsonErr("no file not found");
        }
        $down_nums= Order::where('order_eid',$workid)->count();
        //$work = DB::table('works')->select('work_model')->where(['work_id'=>$workid])->first();
        $url = $this->getDownPath($work->work_model);
        $order = Order::where('order_uid',$this->_user->user_id)->where(['order_eid'=>$workid])->first();
        if($order){
            return $this->jsonOk('ok',['zip_url'=>$url,'down_nums'=>$down_nums,'has_download'=>1]);
        }else{
            $order = new Order();
            $order->order_uid=$this->_user->user_id;
            $order->order_owner=$work->work_uid;
            $order->order_eid=$workid;
            if($order->save()){
                return $this->jsonOk('ok',['zip_url'=>$url,'down_nums'=>$down_nums+1,'has_download'=>1]);

            }
        }
    }

    /**
     *demo模型
     */
    public function  demo()
    {
        if(env('APP_URL')=='http://testapi.novaby.com')
        {
            $work = $this->workDetail(587);
        }else{
            $work = $this->workDetail(11049);
        }
        $work['model_3d'] = $work['model']['model_3d'];
        unset($work['model']);
        return $this->jsonOk('ok',['work'=>$work]);
    }

    /**
     * 删除模型图片
     * @param Request $request
     * @return mixed
     */
    public function deleteModelImage(Request $request)
    {
        $work_id = $request->input('work_id');
        $image_id= $request->input('image_id');
        $work = Work::find($work_id);
        if($work){
            $images = explode(',',$work->work_photos);
            unset($images[array_search($image_id,$images)]);
            $work->work_photos = implode(',',$images);
            $work->save();
            return $this->jsonOk('ok','delete successfully');
        }else{
            return $this->jsonErr('error','no this model');
        }
    }

}
