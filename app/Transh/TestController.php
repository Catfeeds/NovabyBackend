<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use \App\libs\PostNewTarget;
use \App\libs\SignatureBuilder;
use \App\libs\GetAllTargets;
use \App\libs\DeleteTarget;
use OSS\OssClient;
use OSS\Core\OssException;
use \App\libs\TargetManager;
use \App\libs\ApiConf;
use \App\libs\Tools;
class TestController extends Controller
{
    
    public function page(){
    	return view('page');
    }
	public function vuforia(){
		
		$postData=[
			'width'=>320.0 , 
			'name'=>Tools::guid(), 
			'image'=>Tools::getImageAsBase64('../resources/wd.jpg') , 
			'application_metadata'=>base64_encode("test metadata") , 
			'active_flag'=>1 
		];
		$postObj=json_encode($postData);
		$targetMgr=new TargetManager();
		$res=$targetMgr->execPostNewTarget($postObj);
		
		echo $res;
		
	}
	public function getTargets(){
		$targetMgr = new TargetManager();
		$res=$targetMgr->execGetAllTargets();
		var_dump($res);
	}
	public function getTarget(){
		
		$targetMgr = new TargetManager();
		var_dump($targetMgr);
		exit;
		$res=$targetMgr->execGetTarget('54941417-ED53-F2FE-9E7A-B1A53FA39FC2');
		var_dump($res);
	}
	public function updateTarget(){
		$helloBase64 = base64_encode("hello world!");
		$jsonBody = json_encode( array( 'application_metadata' => $helloBase64 ) );
		$targetId = 'e6d3a7ff9a844f0cadab44366e6468d5';
		$targetMgr = new TargetManager();
		
		$res=$targetMgr->execUpdateTarget( $jsonBody , $targetId );
		var_dump($res);
	}
	public function delTarget(){

		$targetId='c7b02310b43b4f0c81a0e4588315317d';
		$targetMgr = new TargetManager();
		
		$res=$targetMgr->execDeleteTarget($targetId);
		var_dump($res);
	}
	public function oss(){
		$accessKeyId = "82HuxDVfPb055FIE"; ;
		$accessKeySecret = "zakmwFCwLKeWwufm38s292lnEZT80Z";
		$endpoint = "oss-cn-shanghai.aliyuncs.com";

		try {
    		$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    		
		} catch (OssException $e) {
    		print $e->getMessage();
    		exit;
		}
		$ossClient->setTimeout(3600);
		$ossClient->setConnectTimeout(10);
		
		$content = "test~!!!~";
		$bucket  = "wztest002";
		$object  = 'test.txt';
		try {
    		$res=$ossClient->putObject($bucket, $object, $content);
    		var_dump($res);
		} catch (OssException $e) {
    		print $e->getMessage();
		}
	}

}
