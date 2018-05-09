<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\libs\Tools;
use App\Models\PdaData;
use DB;
use Session;
use App\libs\ApiConf;
use \App\libs\OSSManager;
use Illuminate\Support\Facades\Redirect;
use \DateTime;
class PostController extends Controller
{
	private $info=null;
	public function __construct(){
		$this->info=Session::get('userInfo',null);
               
		if(!$this->info){
                    header('Location: /user/login');
                    exit;
			return Redirect::to('/user/login');
		}
	}
        public function publish(){
            $cates=DB::table('category')->where('cate_pid',0)->get();
            foreach ($cates as $k => $v) {
                $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
                $cates[$k]->sub=$sub_data;
            }
            
            $datas=(object)[];
            foreach($cates AS $k=>$v){
                $attr=$v->cate_name;
                if(isset($_COOKIE['lang']) && ($attr=='category' || $attr=='Geometry')){
                    foreach($v->sub AS $sk=>$sv){

                        $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                    }
                }
                $datas->$attr=$v->sub;
            }
            $cart_info=[];
            $notices=[];

            if($this->info){
                $notices=$this->getNoticesLists($this->info->user_id);
                $cart_info=$this->getCart($this->info->user_id);
            }
            //dd($datas);
            
            return view('post.publish',['user'=>$this->info,'cates'=>$datas,'path'=>'','notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Publish']);
        }
        public function dopublish(Request $request){
            //dd($request->all());
            $category = $request->get('category');
            $geometry = $request->get('geometry');
            $animation = $request->get('animation');
            $poly = $request->get('poly');
            $texture = $request->get('texture');
            $rigged = $request->get('rigged');
            $uvw = $request->get('uvw');
            $price = $request->get('price');
            $material = $request->get('material');
            $description = $request->get('description');
            $polygons = $request->get('polygons');
            $vertices = $request->get('vertices');
            $title = $request->get('title');
            $covers = $request->get('covers');
            $models = $request->get('models');
            $od = $request->get('od');
            $formats = $request->get('formats');
           // $formats = implode(",", $formats);
            $isedit = $request->get('isedit');
            $eid = $request->get('eid');
            $format_str = '';
            $format_arr = [NULL,NULL,NULL];
            $from = $request->get('from', NULL);
            //dd($formats);
            foreach ($formats AS $k=>$v ){

                $format_itme = explode("-",$v);
                $format_str = $format_str.','.$format_itme[1];
                $format_arr[$k] = $format_itme[0];
            }
            if(is_array($models) && is_array($covers)){
                $cover_id=0;
                $cover_ids=[];
                $tot_size=0;
                $modes_ids=[];
                $format_ids=[];

                foreach($covers AS $k=>$v){
                    if($v['id']==0) {
                        $insert_id = DB::table('oss_item')->insertGetId(
                            [
                                'oss_key' => 'elements',
                                'oss_path' => $v['name'],
                                'oss_item_uid' => $this->info->user_id,
                                'width' => $v['size']['width'],
                                'height' => $v['size']['height'],
                            ]
                        );
                        if ($v['iscover']) {
                            $cover_id = $insert_id;
                        }
                        $cover_ids[]=$insert_id;
                    }else{
                        $cover_ids[]=$v['id'];
                        if ($v['iscover']) {
                            $cover_id = $v['id'];
                        }
                    }


                }

                foreach($models AS $k=>$v){

                    if($v['id']==0) {
                        $insert_m_id = DB::table('oss_item')->insertGetId(
                            [
                                'oss_key' => 'targets',
                                'oss_path' => $v['name'],
                                'oss_item_uid' => $this->info->user_id,
                                'format' => $format_str,
                                'size' => $v['size'],

                            ]
                        );

                    $modes_ids[]=$insert_m_id;


                    }else{
                        $modes_ids[]=$v['id'];

                    }
                    $tot_size+=$v['size'];
                    //$format_ids[]=$v['format'];
                }
            
            $modes_ids_str=  implode(",", $modes_ids);
            $cover_ids_str=  implode(",", $cover_ids);
            //$format_ids_str=  implode(",", $format_ids);
            //$format_id_str=$format_ids[0];
            //$ck_format=DB::table('category')->where(['cate_name'=>$format_id_str])->first();
            //if($cover_id==0) $cover_id=$cover_ids[0];
            //$format_id=$ck_format?$ck_format->cate_id:0;
$data = [
    'user_id'=>$this->info->user_id,
    'element_category'=>$category,
    'element_geometry'=>$geometry,
    'element_poly'=>$poly,
    'element_polygons'=>$polygons,
    'element_rigged'=>$rigged,
    'element_uvw'=>$uvw,
    'element_vertices'=>$vertices,
    'element_name'=>$title,
    'element_models'=>$modes_ids_str,
    'element_style'=>0,
    'element_material'=>$material,
    'element_price'=>$price,
    'element_format'=>$format_arr[0],
    'element_format1'=>$format_arr[1],
    'element_format2'=>$format_arr[2],
    'element_texture'=>$texture,
    'element_level'=>0,
    'element_animation'=>$animation,
    'element_icon_oss_item_id'=>$cover_id,
    'element_cover_id'=>$cover_id,
    'element_description'=>$description,
    'element_images'=>$cover_ids_str,
    'element_size'=>$tot_size,
    'element_od'=>$od,

];
              if($isedit==0) {
                  echo $insert_ele_id = DB::table('element')->insertGetId($data);
                  if($from){
                      DB::table('apply_user')->where(['uid'=>$this->info->user_id])->update(['apply_model_id'=>$insert_ele_id,'status'=>2,'isread'=>0]);
                  }

              }else{
                  $r = DB::table('element')->where(['element_id'=>$eid,'user_id'=>$this->info->user_id])->update($data);
                  if($r){
                      echo $eid;
                  }
              }
            
            }else{
                
            }
            
        }
        public function newposts(){
/*
		$unFinish=DB::table('targets')->where(['target_uid'=>$this->info->user_id,'target_eid'=>0])->first();
		

		if(!$unFinish){
			return view('post.new');
		}else{
			header('Location: /post/newItem/'.$unFinish->id);
		}
*/
        $data=DB::table('category')->get();
        $tmp=[];
        foreach ($data as $k => $v) {
            $tmp[$v->cate_pid][]=$v;
        }
        $cates=[];
        foreach ($tmp[0] as $k => $v) {
            $cates[$v->cate_name][]=$tmp[$k+1];
        }

        return view('post.new',['cates'=>$cates,'title'=>'Post New Element']);
	}
	public function upload(Request $request){
        $dest_path=app_path().'/storage/uploads/tmp/';
        if(!file_exists($dest_path)) mkdir($dest_path,0777,true);
        $category=$request->input('category');
        $style=$request->input('style');
        $level=$request->input('level');
        $format=$request->input('format');
        $texture=$request->input('texture');
        $price=$request->input('price');
        $description=$request->input('description');
        $animation=$request->input('animation');
        
        $uploads['model']=$request->file("model");
        $uploads['img1']=$request->file("img1");
        $uploads['img2']=$request->file("img2");
        $uploads['img3']=$request->file("img3");
        $uploads['img4']=$request->file("img4");
        $uploads['img5']=$request->file("img5");
        $model_size=$uploads['model']->getSize();
        $bucket_elements = ApiConf::OSS_BUKET_NAME_ELEMENTS;
        $bucket_targets = ApiConf::OSS_BUKET_NAME_TARGETS;
        
        foreach($uploads AS $k=>$v){
            $_clientName = $v -> getClientOriginalName();
            $_entension     = $v -> getClientOriginalExtension();
            $v->move($dest_path, $_clientName);
            $_data=$dest_path.$_clientName;
            $_target_id = Tools::guid();
            $_oss_file  = $_target_id.'.'.$_entension;
            $_local_file    = $dest_path.$_clientName;
            $bucket=( $k=='model' ) ? $bucket_targets : $bucket_elements;
            if($this->stroreToOss($_oss_file,$_local_file,$bucket)){
                     $_oss_record_id=DB::table('oss_item')->insertGetId(
                            [
                                'oss_key'=>$bucket,
                                'oss_path'=>$_target_id.'.'.$_entension,
                                'oss_item_uid'=>$this->info->user_id
                            ]
                        );
                    
                    $_images[]=$_oss_record_id;
                }

                unlink($_local_file);
        }
        
        $imgs=implode(",",array_slice($_images,1));

        $insert_id=DB::table('element')->insertGetId([
                    'user_id'=>$this->info->user_id,
                    'element_category'=>$category,
                    'element_style'=>$style,
                    'element_price'=>$price,
                    'element_format'=>$format,
                    'element_texture'=>$texture,
                    'element_level'=>$level,
                    'element_animation'=>$animation,
                    'element_icon_oss_item_id'=>$_images[0],
                    'element_description'=>$description,
                    'element_images'=>$imgs,
                    'element_size'=>$model_size,
                    
                    ]);
                if($insert_id){
                   
                   echo json_encode(['code'=>0,'msg'=>'success']);
                }










/*

        exit;
		if(!file_exists($dest_path)) mkdir($dest_path,0777,true);
    	if ($request->hasFile('file') && $request->file('file')->isValid()) {
			$name=$request->input('name');
        	$file = $request->file('file');
        	$clientName = $file -> getClientOriginalName();
        	$entension = $file -> getClientOriginalExtension();
        	
        	$request->file('file')->move($dest_path, $clientName);
        	$data=$dest_path.$clientName;   		
        	$bucket  = ApiConf::OSS_BUKET_NAME;
        	$target_id=Tools::guid();
        	$oss=new OSSManager();
        	$oss_res=$oss->multiuploadFile($bucket,$target_id.'.'.$entension,$data);
        	if($oss_res){
        			
        		
					//$data=[$target_id,$entension,'',time()];
        		echo $insert_id=DB::table('targets')->insertGetId(
        			['target_id'=>$target_id,
        			'ext_name'=>$entension,
        			'target_uid'=>$this->info->user_id,
        			'create_time'=>time()
        			]
        			);
        		$oss_record_id=DB::table('oss_item')->insertGetId(
        			['oss_key'=>$bucket,
        			'oss_path'=>$target_id.'.'.$entension,
        			'oss_item_uid'=>$this->info->user_id
        			]
        		);
        			//echo $oss_record_id=DB::insertGetId();
       			if($insert_id){
					//echo json_encode(['code'=>'200','datas'=>'success']);
					//Redirect::to("post/newItem");
					header('Location: /post/newItem/'.$insert_id);
				}
			}else{
				echo json_encode(['code'=>-1,'datas'=>'upload to OSS err!']);
			}
    	}else{
    		echo 222;
   		}
        */
	}
	private function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
	public function ossToken(){
		$id= ApiConf::OSS_ACCESS_KEY;
        $key= ApiConf::OSS_SECRET_KEY;
        $host = 'https://elements.oss-cn-hongkong.aliyuncs.com';

    $now = time();
    $expire = 10; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
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
    echo json_encode($response);
	}
	public function newItem($id=0){
		if($id==0) die('error access');
		$my_item_condation=[
			'id'=>$id,
			'target_uid'=>$this->info->user_id,
			'target_eid'=>0,
		];

		$my_item=DB::table('targets')->where($my_item_condation)->first();
		if(!$my_item) die("error access");
		$data=DB::table('category')->get();
		$tmp=[];
		foreach ($data as $k => $v) {
			$tmp[$v->cate_pid][]=$v;
		}
		$cates=[];
		foreach ($tmp[0] as $k => $v) {
			$cates[$v->cate_name][]=$tmp[$k+1];
		}
		return view('post.newItem',['cates'=>$cates]);
	}



	private function stroreToOss($name,$data,$bucket){
        $oss 		= new OSSManager();
        $oss_res	= $oss->multiuploadFile($bucket,$name,$data);

        return true;
	}
    public function editHead(Request $request){
        $head_img=$request->get('data');
        if($head_img){
          $datas=explode(",",$head_img);
          $ext=Tools::getImageExtension($datas[0]);
          $dest_path=app_path().'/storage/uploads/tmp/';
          $tmp_file=$dest_path.time().$this->info->user_id.".".$ext;
          file_put_contents($tmp_file, base64_decode($datas[1]));
          $target_id    = Tools::guid();
          $oss_file   = $target_id.'.'.$ext;
          $bucket   =   ApiConf::OSS_BUKET_NAME_ELEMENTS;
          if($this->stroreToOss($oss_file,$tmp_file,$bucket)){
                $_oss_record_id=DB::table('oss_item')->insertGetId(
                                [
                                    'oss_key'=>$bucket,
                                    'oss_path'=>$oss_file,
                                    'oss_item_uid'=>$this->info->user_id
                                ]);
                if($_oss_record_id){
                    $res=DB::table('user')->where('user_id',$this->info->user_id)->update(['user_icon'=>$_oss_record_id]);
                    if($res) echo $oss_file;
                }
          }

          unlink($tmp_file);
        }
  }

    public function itemDel(Request $request){
        $id=$request->get('id');
        if($id){
            $res=DB::table('element')->where('element_id',$id)->where('user_id',$this->info->user_id)->update(['element_isdel'=>1]);
            if($res){
                echo json_encode(['code'=>0,'msg'=>'success']);
            }else{
                echo json_encode(['code'=>-1,'msg'=>'failed']);
            }
        }
    }
	public function postnewItem(Request $request,$id=0){
		if($id<=0) die("error access");
		$dest_path=app_path().'/storage/uploads/tmp/';
		if(!file_exists($dest_path)) mkdir($dest_path,0777,true);

		$bucket  	= 	ApiConf::OSS_BUKET_NAME;
    	if ($request->hasFile('cover') && $request->file('cover')->isValid()) {
    		$file  		= $request->file('cover');

    		$clientName = $file -> getClientOriginalName();
        	$entension 	= $file -> getClientOriginalExtension();
        	

        	$request->file('cover')->move($dest_path, $clientName);
        	$data=$dest_path.$clientName;
        	$target_id	= Tools::guid();
        	$oss_file	= $target_id.'.'.$entension;
        	$local_file	= $dest_path.$clientName;
        	
        	

        	if($this->stroreToOss($oss_file,$local_file,$bucket)){


        		$_images=[];
        		$images=$request->file('images');
        		foreach($images AS $k=>$v){
        			$_clientName = $v -> getClientOriginalName();
        			$_entension 	= $v -> getClientOriginalExtension();
        			$v->move($dest_path, $_clientName);
        			$_data=$dest_path.$_clientName;
        			$_target_id	= Tools::guid();
        			$_oss_file	= $_target_id.'.'.$_entension;
        			$_local_file	= $dest_path.$_clientName;
        			if($this->stroreToOss($_oss_file,$_local_file,$bucket)){
        				/*
        				$_oss_record_id=DB::insert('insert into oss_item (oss_key,oss_path,oss_item_uid) values (?,?,?)', [$bucket,$_target_id.'.'.$_entension,$this->info->user_id])->GetId();
        				*/
        				$_oss_record_id=DB::table('oss_item')->insertGetId(
        					[
        						'oss_key'=>$bucket,
        						'oss_path'=>$_target_id.'.'.$_entension,
        						'oss_item_uid'=>$this->info->user_id
        					]
        					);
        			
        				$_images[]=$_oss_record_id;
        			}
        		
        		}


        		$cate  		=	$request->get('category');
				$style		=	$request->get('style');
				$level		=	$request->get('level');
				$format		=	$request->get('format');
				$price		=	$request->get('price');
				$texture	=	$request->get('texture');
				$animation	=	$request->get('animation');
				$desc		=	$request->get('description');
				$imgs 		=	implode(",", $_images);
				
/*
				$oss_record_id=DB::insert('insert into oss_item (oss_key,oss_path) values (?,?)', [$bucket,$target_id.'.'.$entension]);
*/
				$oss_record_id=DB::table('oss_item')->insertGetId(
        					[
        						'oss_key'=>$bucket,
        						'oss_path'=>$target_id.'.'.$entension,
        						'oss_item_uid'=>$this->info->user_id
        					]
        					);

        		
        		$insert_id=DB::table('element')->insertGetId([
        			'user_id'=>$this->info->user_id,
        			'element_category'=>$cate,
        			'element_style'=>$style,
        			'element_price'=>$price,
        			'element_format'=>$format,
        			'element_texture'=>$texture,
        			'element_animation'=>$animation,
        			'element_icon_oss_item_id'=>$oss_record_id,
        			'element_description'=>$desc,
        			'element_images'=>$imgs,
        			'element_target_id'=>$id,
        			]);
        		if($insert_id){
        			DB::table('targets')->where(['id'=>$id])->update(['target_eid' => $insert_id]);
        		}

        	}



    	}else{
    		echo 123;
    	}


		

		
	}
    public function getEdit($id = 0){
        if ($id <= 0) {
            return response()->json(['code'=>200,'msg'=>'error']);
        }
        $data = DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->first();
        if (!$data) {
            return response()->json(['code'=>200,'msg'=>'error']);
        }
        $data->formats_str = [];
        if ($data->element_format) {
            $format = DB::table('category')->select('cate_name','cate_id')->where(['cate_id'=>$data->element_format])->first();
            $data->formats_str[]= $format->cate_id.'-'.$format->cate_name;
        }
        if ($data->element_format1) {
            $format = DB::table('category')->select('cate_name','cate_id')->where(['cate_id'=>$data->element_format1])->first();
            $data->formats_str[] = $format->cate_id.'-'.$format->cate_name;
        }
        if ($data->element_format2) {
            $format = DB::table('category')->select('cate_name','cate_id')->where(['cate_id'=>$data->element_format2])->first();
            $data->formats_str[] = $format->cate_id.'-'.$format->cate_name;
        }
        $model = $this->getOssPath($data->element_models);
        $data->model = $model->oss_path;
        $imgs_ids = explode(",", $data->element_images);
        $images = DB::table('oss_item')->select('oss_item_id','oss_path','width','height')->whereIn('oss_item_id', $imgs_ids)->get();
        $data->images = $images;
        $cover = DB::table('oss_item')->select('oss_path')->where(['oss_item_id'=>$data->element_cover_id])->first();
        $serverImages = [];
        foreach($images AS $k=>$v){
            $arr['iscover'] = $v->oss_path==$cover->oss_path?1:0;
            $arr['size'] = ['width'=>$v->width, 'height'=>$v->height];
            $arr['name'] = $v->oss_path;
            $arr['id'] = $v->oss_item_id;
            $serverImages[] = $arr;
        }
        $data->serverImages = $serverImages;
        return response()->json(array($data)[0]);
    }
    public function newpost(Request $request){
        $posts=$request->get('post');
        $posts=explode("|", $posts);
        $oss_ids=[];
        foreach($posts AS $k=>$v){
            $oss_insert_id=DB::table('oss_item')->insertGetId([
                    'oss_key'=>'elements',
                    'oss_path'=>$v,
                    'oss_item_uid'=>$this->info->user_id,
                ]);
            if($oss_insert_id){
                $oss_ids[]=$oss_insert_id;
            }


        }
        $cate =  $request->get('category');
        $style =   $request->get('style');
        $level =   $request->get('level');
        $format     =   $request->get('format');
        $price      =   $request->get('price');
        $texture    =   $request->get('texture');
        $animation  =   $request->get('animation');
        $desc       =   $request->get('description');

        $imgs_arr       =   array_slice($oss_ids, 1);
        $imgs = implode(",", $imgs_arr);
        $insert_id=DB::table('element')->insertGetId([
                    'user_id'=>$this->info->user_id,
                    'element_category'=>$cate,
                    'element_style'=>$style,
                    'element_price'=>$price,
                    'element_format'=>$format,
                    'element_texture'=>$texture,
                    'element_animation'=>$animation,
                    'element_icon_oss_item_id'=>$oss_ids[0],
                    'element_description'=>$desc,
                    'element_images'=>$imgs,
                    'element_target_id'=>0,
                    ]);
        echo $insert_id;

    }
    public function lists(){

        $pageSize=1;
        $list=DB::table('element')->where(['user_id'=>$this->info->user_id])->paginate($pageSize);
        return view('post.list',['lists'=>$list]);
    }
    public function my(){
        return view('post.my');
    }
    public function check_draft(){
        $ck_data = DB::table('element_pub')->where(['uid'=>$this->info->user_id])->first();
        dd($ck_data);
    }
    public function step1_view($id=0){

        if($id!=0){
            $ck_eidt = DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->count();
            if($ck_eidt!=1){
                abort(404);
                exit;
            }
        }

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        $step = 0;
        $unpub =  DB::table('element_pub')->select('id','step')->where(['uid'=>$this->info->user_id,'ispub'=>0])->first();

        if($unpub){
            $step = $unpub->step;
        }
        return view('pub.step1',['notices'=>$notices,'cart_info'=>$cart_info,'user'=>$this->info,'title'=>'step1','step'=>$step,'id'=>$id]);
    }
    public function step2_view($id = 0){
        if($id!=0){
            $ck_eidt = DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->count();

            if($ck_eidt!=1){
                abort(404);
                exit;
            }
        }
        if($id==0){
            $ck_eidt = DB::table('element_pub')->where(['uid'=>$this->info->user_id,'ispub'=>0])->count();
            if($ck_eidt==0){
                abort(404);
                exit;
            }
        }

        $unput = $this->unpubid();
        if($id!=0){
            $unput = (object)['step'=>5];
        }
        $cates=DB::table('category')->where('cate_pid',0)->get();
        foreach ($cates as $k => $v) {
            $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
            $cates[$k]->sub=$sub_data;
        }

        $datas=(object)[];
        foreach($cates AS $k=>$v){
            $attr=$v->cate_name;
            if(isset($_COOKIE['lang']) && ($attr=='category' || $attr=='Geometry')){
                foreach($v->sub AS $sk=>$sv){

                    $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                }
            }
            $datas->$attr=$v->sub;
        }

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pub.step2',['notices'=>$notices,'cart_info'=>$cart_info,'user'=>$this->info,'data'=>$cates,'title'=>'step2','step'=>$unput->step,'id'=>$id]);

    }
    public function step3_view($id = 0){

        if($id!=0){
            $ck_eidt = DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->count();
            if($ck_eidt!=1){
                abort(404);
                exit;
            }
        }
        if($id==0){
            $ck_eidt = DB::table('element_pub')->where(['uid'=>$this->info->user_id,'ispub'=>0])->count();
            if($ck_eidt==0){
                abort(404);
                exit;
            }
        }
        $unput = $this->unpubid();
        if($id!=0){
            $unput = (object)['step'=>5];
        }
        $cates=DB::table('category')->where('cate_pid',0)->get();
        foreach ($cates as $k => $v) {
            $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
            $cates[$k]->sub=$sub_data;
        }

        $datas=(object)[];
        foreach($cates AS $k=>$v){
            $attr=$v->cate_name;
            if(isset($_COOKIE['lang']) && ($attr=='category' || $attr=='Geometry')){
                foreach($v->sub AS $sk=>$sv){

                    $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                }
            }
            $datas->$attr=$v->sub;
        }

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pub.step3',['notices'=>$notices,'cart_info'=>$cart_info,'user'=>$this->info,'data'=>$cates,'title'=>'step3','step'=>$unput->step,'id'=>$id]);

    }
    public function step4_view($id=0){

        if($id!=0){
            $ck_eidt = DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->count();
            if($ck_eidt!=1){
                abort(404);
                exit;
            }
        }
        if($id==0){
            $ck_eidt = DB::table('element_pub')->where(['uid'=>$this->info->user_id,'ispub'=>0])->count();
            if($ck_eidt==0){
                abort(404);
                exit;
            }
        }
        $unput = $this->unpubid();
        if($id!=0){
            $unput = (object)['step'=>5];
        }
        $cates=DB::table('category')->where('cate_pid',0)->get();
        foreach ($cates as $k => $v) {
            $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
            $cates[$k]->sub=$sub_data;
        }

        $datas=(object)[];
        foreach($cates AS $k=>$v){
            $attr=$v->cate_name;
            if(isset($_COOKIE['lang']) && ($attr=='category' || $attr=='Geometry')){
                foreach($v->sub AS $sk=>$sv){

                    $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                }
            }
            $datas->$attr=$v->sub;
        }

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pub.step4',['notices'=>$notices,'cart_info'=>$cart_info,'user'=>$this->info,'data'=>$cates,'title'=>'step4','step'=>$unput->step,'id'=>$id]);

    }

    public function step5_view($id=0){
        if($id==0){
            $ck_eidt = DB::table('element_pub')->where(['uid'=>$this->info->user_id,'ispub'=>0])->count();
            if($ck_eidt==0){
                abort(404);
                exit;
            }
        }
        $unpub= $this->unpubid();

        $data = DB::table('element_pub')->where(['id'=>$unpub->id])->first();
        if($data->model_id!=0){
            $model_info = DB::table('oss_item')->where(['oss_item_id'=>$data->model_id])->first();
            $data->dl_url = ApiConf::IMG_URI.$model_info->oss_path;

        $_model_info = explode("/",$model_info->oss_path);
        $data->mdname = $_model_info[count($_model_info)-1];
        }else{
            $data->dl_url = '';
        }

        $_covers = explode(",",$data->covers);
        $_covers = DB::table('oss_item')->select('oss_path')->whereIn('oss_item_id',$_covers)->get();
        foreach($_covers AS $k=>$v){
            $covers[] = ApiConf::IMG_URI.$v->oss_path;
        }
        $data->covers = $covers;

        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pub.step5',['notices'=>$notices,'cart_info'=>$cart_info,'user'=>$this->info,'data'=>$data,'title'=>'step5','step'=>$unpub->step]);

    }

    public function step1(Request $req){
        $id = $req->get('id','0');

        $covers = $req->get('build_photos');
        $cover_id = 0;
        $cover_ids = [];
        if(!$covers)  exit;
        foreach($covers AS $k=>$v){
            if($v['id']==0) {
                $insert_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'elements',
                        'oss_path' => $v['name'],
                        'oss_item_uid' => $this->info->user_id,
                        'width' => $v['size']['width'],
                        'height' => $v['size']['height'],
                    ]
                );
                if ($v['iscover']) {
                    $cover_id = $insert_id;
                }
                $cover_ids[]=$insert_id;
            }else{
                $cover_ids[]=$v['id'];
                if ($v['iscover']) {
                    $cover_id = $v['id'];
                }
            }


        }
        $images = implode(",",$cover_ids);
        if($cover_id==0) $cover_id=$cover_ids[0];
        if($id>0){
            $res = DB::table('element')->where(['element_id'=>$id,'user_id'=>$this->info->user_id])->update(['element_images' => $images,  'element_cover_id' => $cover_id,'optime'=>time()]);

        }else{
            $ck_data = DB::table('element_pub')->where(['uid'=>$this->info->user_id,'ispub'=>0])->first();
            if(!$ck_data) {
                $res = DB::table('element_pub')->insertGetId(['covers' => $images, 'uid' => $this->info->user_id, 'cover' => $cover_id, 'step' => 1,'optime'=>time()]);
            }else{
                $res = DB::table('element_pub')->where(['id'=>$ck_data->id])->update(['covers' => $images, 'uid' => $this->info->user_id, 'cover' => $cover_id,'optime'=>time()]);
            }
        }
        if($res) echo 1;
    }
    public function step2(Request $req){
        //dd($req->all());
        $id = $req->get('id','0');
        $dp = $req->get('dp',NULL);

        $unpub = $this->unpubid();
        if(!$unpub) exit;
        $models = $req->get('attach');
        $tot_size = 0;
        $formats = $req->get('cates');

        $format_str = '';
        $format_arr = [NULL,NULL,NULL];
        foreach ($formats AS $k=>$v ){

            $format_itme = explode("-",$v);
            $format_str = $format_str.','.$format_itme[1];
            $format_arr[$k] = $format_itme[0];
        }
        $size = 0;

        if($dp!=1){
            foreach($models AS $k=>$v){
                if($v['id']==0) {
                    $insert_m_id = DB::table('oss_item')->insertGetId(
                     [
                        'oss_key' => 'targets',
                        'oss_path' => $v['name'],
                        'oss_item_uid' => $this->info->user_id,
                        'format' => $format_str,
                        'size' => $v['size'],

                    ]
                );

                $modes_ids[]=$insert_m_id;
                $size+=$v['size'];


                }else{
                    $modes_ids[]=$v['id'];

                }
                $tot_size+=$v['size'];
                //$format_ids[]=$v['format'];
            }

            $modes_ids_str =  implode(",", $modes_ids);
        }else{
            $modes_ids_str = 0;
        }
        $step = $unpub->step>2?$unpub->step:3;
        $_data=[
            'format'=>$format_arr[0],
            'format1'=>$format_arr[1],
            'format2'=>$format_arr[2],
            'model_id'=>$modes_ids_str,
            'size'=>$size,
            'optime'=>time(),
            'step'=>$step,

        ];
        if($id==0){
            $res = DB::table('element_pub')->where(['id'=>$unpub->id])->update($_data);
        }else{
            $_data=[
                'element_format'=>$format_arr[0],
                'element_format1'=>$format_arr[1],
                'element_format2'=>$format_arr[2],
                'element_models'=>$modes_ids_str,
                'optime'=>time(),

            ];
            $res = DB::table('element')->where(['user_id'=>$this->info->user_id,'element_id'=>$id])->update($_data);
        }
        echo $res;
    }
    public function step3(Request $req){
        //dd($req->all());
        $id = $req->get('id','0');
        $unpub = $this->unpubid();
        if(!$unpub) exit;
        $name = $req->get('name');
        $cate = $req->get('cate');
        $geo = $req->get('geo');
        $Polygons = $req->get('Polygons');
        $Vertices = $req->get('Vertices');
        $Animate = $req->get('Animate');
        $Textures = $req->get('Textures');
        $Rigged = $req->get('Rigged');
        $Lowpoly = $req->get('Lowpoly');
        $Material = $req->get('Material');
        $uv = $req->get('uv');
        $cp = $req->get('cp');
        $desc = $req->get('desc');
        $step = $unpub->step>3?$unpub->step:4;
        $_data=[
            'name'=>$name,
            'cate'=>$cate,
            'geo'=>$geo,
            'Polygons'=>$Polygons,
            'Vertices'=>$Vertices,
            'Animate'=>$Animate,
            'Textures'=>$Textures,
            'Rigged'=>$Rigged,
            'Lowpoly'=>$Lowpoly,
            'Material'=>$Material,
            'uv'=>$uv,
            'cp'=>$cp,
            'descr'=>$desc,
            'optime'=>time(),
            'step'=>$step,
        ];
        if($id==0){
            $res = DB::table('element_pub')->where(['id'=>$unpub->id])->update($_data);
        }else{
            $_data=[
                'element_name'=>$name,
                'element_category'=>$cate,
                'element_geometry'=>$geo,
                'element_polygons'=>$Polygons,
                'element_vertices'=>$Vertices,
                'element_animation'=>$Animate,
                'element_texture'=>$Textures,
                'element_rigged'=>$Rigged,
                'element_poly'=>$Lowpoly,
                'element_material'=>$Material,
                'element_uvw'=>$uv,
                'element_cp'=>$cp,
                'element_description'=>$desc,
                'optime'=>time(),

            ];
            $res = DB::table('element')->where(['user_id'=>$this->info->user_id,'element_id'=>$id])->update($_data);
        }
        if($res){
            echo 1;
        }


    }
    public function step4(Request $req){
        $id = $req->get('id','0');
        $unpub = $this->unpubid();
        if(!$unpub) exit;
        $price = intval($req->get('price'));
        $display = $req->get('display');
        $step = $unpub->step>4?$unpub->step:2;
        if($id==0){
            if($price){
                $res = DB::table('element_pub')->where(['id'=>$unpub->id])->update(['price'=>$price,'displayonly'=>$display,'step'=>$step,'optime'=>time()]);
            }else{
                $res = DB::table('element_pub')->where(['id'=>$unpub->id])->update(['displayonly'=>$display,'step'=>$step,'optime'=>time()]);
            }
        }else{
            if($price) {
                $res = DB::table('element')->where(['element_id' => $id, 'user_id' => $this->info->user_id])->update(['element_price' => $price, 'element_od' => $display,'optime'=>time()]);
            }else{
                $res = DB::table('element')->where(['element_id' => $id, 'user_id' => $this->info->user_id])->update(['element_od'=>$display,'optime'=>time()]);

            }
        }
        if($res){
            echo 1;
        }

    }
    public function step5(Request $req){
        $data = $this->unpubid();
        $data = DB::table('element_pub')->where(['id'=>$data->id])->first();
        $data->price=$data->price?$data->price:0;
        $data->size=$data->size?$data->size:102400;
        $od = $data->displayonly;
        $_data = [
            'user_id'=>$this->info->user_id,
            'element_category'=>$data->cate,
            'element_geometry'=>$data->geo,
            'element_poly'=>$data->Polygons,
            'element_polygons'=>$data->Polygons,
            'element_rigged'=>$data->Rigged,
            'element_uvw'=>$data->uv,
            'element_vertices'=>$data->Vertices,
            'element_name'=>$data->name,
            'element_models'=>$data->model_id,
            'element_style'=>0,
            'element_material'=>$data->Material,
            'element_price'=>$data->price,
            'element_format'=>$data->format,
            'element_format1'=>$data->format1,
            'element_format2'=>$data->format2,
            'element_texture'=>$data->Textures,
            'element_level'=>0,
            'element_animation'=>$data->Animate,
            'element_icon_oss_item_id'=>$data->cover,
            'element_cover_id'=>$data->cover,
            'element_description'=>$data->descr,
            'element_images'=>$data->covers,
            'element_size'=>$data->size,
            'element_od'=>$od,

        ];
        $res = DB::table('element')->insertGetId($_data);
        DB::table('element_pub')->where(['id'=>$data->id])->update(['ispub'=>1]);
        echo $res;
    }
    private function unpubid(){

        $data = DB::table('element_pub')->select('id','step')->where(['uid'=>$this->info->user_id,'ispub'=>0])->first();
        if(!$data){
            return;
        }
        return $data;
    }

    public function ck_step1(Request $req){
        $id = $req->get('id','0');
        if($id==0) {
            $data = DB::table('element_pub')->select('covers','cover')->where(['uid' => $this->info->user_id, 'ispub' => 0])->first();
        }elseif($id>0){
            $data = DB::table('element')->select('element_images','element_cover_id AS cover')->where(['user_id' => $this->info->user_id, 'element_id' => $id])->first();
            if($data && $data->element_images){
                $data->covers = $data->element_images;
            }
        }
        if($data){
            $_photos = explode(",", $data->covers);
            $_oss_path = DB::table('oss_item')->select('oss_path','oss_item_id')->whereIn('oss_item_id',$_photos)->get();
            $pics = [];
            foreach($_oss_path AS $k=>$v){
                $iscover = $v->oss_item_id==$data->cover ? 1 : 0;
                $tmp = ['id'=>$v->oss_item_id,'url'=>ApiConf::IMG_URI.$v->oss_path,'iscover'=>$iscover];
                $pics[] = $tmp;
            }
            return response()->json(['code'=>200,'data'=>$pics]);

        }
    }
    public function ck_step2(Request $req){
        $id = $req->get('id','0');
        if($id==0) {
            $data = DB::table('element_pub')->select('format', 'format1', 'format2', 'model_id', 'step','displayonly')->where(['uid' => $this->info->user_id, 'ispub' => 0])->first();
        }elseif($id>0){
            $data = DB::table('element')->select('element_format AS format', 'element_format1 AS format1', 'element_format2 AS format2', 'element_models AS model_id','element_od AS displayonly')->where(['user_id' => $this->info->user_id, 'element_id' => $id])->first();
            if($data){
                $data->step=5;
            }
        }
        if($data){
            if($data->step==1){
                exit;
            }
            //$format1 = $data->format;
            //$format2 = $data->format;
            //$format3 = $data->format;
            if($data->model_id!=0){
                $_oss_path = DB::table('oss_item','size')->select('oss_path','oss_item_id','size')->where(['oss_item_id'=>$data->model_id])->first();
                $_names = explode('/',$_oss_path->oss_path);
                $_name = $_names[count($_names)-1];
                $data->attach = ['name'=>$_name,'id'=>$data->model_id,'size'=>$_oss_path->size];
            }else{
                $data->attach = '';
            }
            return response()->json(['code'=>200,'data'=>$data]);

        }
    }
    public function ck_step3(Request $req){
        $id = $req->get('id','0');
        if($id==0) {
            $data = DB::table('element_pub')->select('name', 'cate', 'geo', 'Polygons', 'Vertices', 'Animate', 'Textures', 'Rigged', 'Lowpoly', 'Material', 'uv', 'cp', 'descr')->where(['uid' => $this->info->user_id, 'ispub' => 0])->first();
        }elseif($id>0){
            $data = DB::table('element')->select('element_name AS name', 'element_category AS cate', 'element_geometry AS geo', 'element_polygons AS Polygons', 'element_vertices AS Vertices', 'element_animation AS Animate', 'element_texture AS Textures', 'element_rigged AS Rigged', 'element_poly AS Lowpoly', 'element_material AS Material', 'element_uvw AS uv', 'element_cp AS cp', 'element_description AS descr')->where(['user_id' => $this->info->user_id, 'element_id' => $id])->first();

            if($data){
                $data->step=5;
            }
        }
        if($data){

            return response()->json(['code'=>200,'data'=>$data]);

        }
    }

    public function ck_step4(Request $req){
        $id = $req->get('id','0');
        if($id==0) {
            $data = DB::table('element_pub')->select('price', 'displayonly')->where(['uid' => $this->info->user_id, 'ispub' => 0])->first();
        }elseif($id>0){
            $data = DB::table('element')->select('element_price AS price', 'element_od AS displayonly')->where(['user_id' => $this->info->user_id, 'element_id' => $id])->first();
        }
        return response()->json(['code'=>200,'data'=>$data]);

    }
}
