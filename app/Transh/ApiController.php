<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Services\AppDataService;
use \App\libs\SignatureBuilder;
use \App\libs\TargetManager;
use \App\libs\OSSManager;
use \App\libs\Tools;
use DB;
use App\libs\ApiConf;
class ApiController extends Controller
{
  /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
	//private $targetMgr=null;
	//private $oss=null;
  public function index(){
	$page_size=ApiConf::PAGE_SIZE;
	$page=isset($_GET['page'])?intval($_GET['page']):1;
	$offset=($page-1)*$page_size;
	$list=$users = DB::table('targets')->skip($offset)->take($page_size)->orderBy('id','DESC')->get();
	$count=DB::table('targets')->count();
	$has_more=$page*$page_size<$count?true:false;
	$result=[
		'code'=>200,
		'datas'=>[
			'hasMore'=>$has_more,
			'target_lists'=>$list,
		]
	];
	echo json_encode($result);
  }
  public function create(Request $request){
    return view('page');
  }
  public function store( Request $request)
  {
    /*
    $data = $request->input('data');
    return AppDataService::save( $data);
    */
	$dest_path=app_path().'/storage/uploads/tmp/';
	if(!file_exists($dest_path)) mkdir($dest_path,0777,true);
    	if ($request->hasFile('pic') && $request->file('pic')->isValid()) {
		      $name=$request->input('name');
        	$file = $request->file('pic');
        	$clientName = $file -> getClientOriginalName();
        	$entension = $file -> getClientOriginalExtension();
        	//$destinationPath=dirname(__FILE__).'/';
        	$request->file('pic')->move($dest_path, $clientName);
        	$image=$dest_path.$clientName;
		
       		$postData=[
          		'width'=>320.0 , 
          		'name'=>Tools::guid(), 
          		'image'=>Tools::getImageAsBase64($image) , 
          		'application_metadata'=>base64_encode("test metadata") , 
          		'active_flag'=>1 
        	];
        	$postObj=json_encode($postData);
        	$targetMgr=new TargetManager();
       		$res=$targetMgr->execPostNewTarget($postObj);
        	$target_id=$res->target_id;
		if(!$target_id){
			echo json_encode(['code'=>-1,'datas'=>'upload to vuforia err!']);
			exit;
		}
        	$bucket  = ApiConf::OSS_BUKET_NAME;
        	$oss=new OSSManager();
       		// $oss->multiuploadFile($clientName,$bucket,$destinationPath);
        	$oss_res=$oss->multiuploadFile($bucket,$target_id.'.'.$entension,$image);
        	if($oss_res){
			$data=[$target_id,$entension,$name,time()];
        		$insert_id=DB::insert('insert into targets (target_id,ext_name,name,create_time) values (?,?,?,?)', $data);
       		 	if($insert_id){
				echo json_encode(['code'=>'200','datas'=>'success']);
			}
		}else{
			echo json_encode(['code'=>-1,'datas'=>'upload to OSS err!']);
		}
    }
  }
  public function show($id=''){
    $item=DB::select('SELECT * FROM targets WHERE target_id=?',[$id]);
	var_dump($item);
  }
  public function edit($id=0){
    echo $id;
	echo __METHOD__;
  }
  public function update($id=0){
    echo $id;
  }
  public function destroy($id=''){
	if(!$id) exit;
	$item=DB::select('SELECT * FROM targets WHERE target_id=?',[$id]);
	if(!$item[0]){
		exit;
	}
	$file=$item[0]->target_id.'.'.$item[0]->ext_name;
	$targetMgr=new TargetManager();
	$delete_result1=$targetMgr->execDeleteTarget($id);
	$oss=new OSSManager();
	$bucket  = ApiConf::OSS_BUKET_NAME;
	$delete2_result2=$oss->delete($file,$buket);
	if($delete_result1 || $delete_result2){
		$res=DB::table('targets')->where('target_id', '=', $id)->delete();
		if($res) echo 1;
	}
    	//echo "WILL DELETE FROM vuforia\n";
	//echo "WILL DELETE FROM OSS\n";
	//echo "WILL DELETE FROM server\n";
  }

}
