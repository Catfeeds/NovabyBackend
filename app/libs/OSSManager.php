<?php
namespace App\libs;
use OSS\OssClient;
use OSS\Core\OssException;
class OSSManager{
	private $ossClient=null;
	const ACCESS_KEY 	= ApiConf::OSS_ACCESS_KEY;
	const SECRET_KEY 	= ApiConf::OSS_SECRET_KEY;
	const ENDPOINT 		= ApiConf::OSS_ENDPOINT;

	public function __construct(){
		try {
                	$this->ossClient = new OssClient(SELF::ACCESS_KEY,SELF::SECRET_KEY,SELF::ENDPOINT);
                	$this->ossClient->setTimeout(36000);
			$this->ossClient->setConnectTimeout(10);
		} catch (OssException $e) {
                	print $e->getMessage();
                
                }
	}
	public function multiuploadFile($bucket,$object,$file){
    		$options = array();
    		try{
        		$this->ossClient->multiuploadFile($bucket, $object, $file, $options);
    		} catch(OssException $e) {
        		//printf(__FUNCTION__ . ": FAILED\n");
       		 	//printf($e->getMessage() . "\n");
        		return;
    		}
    		//print(__FUNCTION__ . ":  OK" . "\n");
		return true;
	}
	public function upload($file,$bucket,$filePath){
		$object=$file;
		
    		try{
       			 $this->ossClient->uploadFile($bucket, $object, $filePath);
    		} catch(OssException $e) {
    		    //var_dump($e);
       			 //printf(__FUNCTION__ . ": FAILED\n");
      			 //printf($e->getMessage() . "\n");
        		return false;
   		 }
    		//print(__FUNCTION__ . ": OK" . "\n");
    		return true;
	}
	public function delete($file,$bucket){
    		try{
        	$this->ossClient->deleteObject($bucket, $file);
   		 } catch(OssException $e) {
        		//printf(__FUNCTION__ . ": FAILED\n");
        		//printf($e->getMessage() . "\n");
        		return;
   		 }
    		//print(__FUNCTION__ . ": OK" . "\n");
		return true;
	}
	public function update($id,$data){
	}
}
