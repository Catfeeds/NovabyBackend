<?php
namespace App\libs;
use SignatureBuilder;
use DateTime;
use DateTimeZone;
use HTTP_Request2;
class TargetManager{
	private $request 	= null;
	const ACCESS_KEY 	= ApiConf::VUFORIA_ACCESS_KEY;
	const SECRET_KEY 	= ApiConf::VUFORIA_SECRET_KEY;
	const URL 			= ApiConf::VUFORIA_API_URL;
	const REQUEST_PATH  = ApiConf::VUFORIA_API_PATH;
	public function __construct(){
		$this->request 	= new HTTP_Request2();;
	}
	function execPostNewTarget($jsonRequestObject){

		$this->request->setMethod( HTTP_Request2::METHOD_POST );
		$this->request->setBody( $jsonRequestObject );
		$this->request->setConfig(['ssl_verify_peer' => false]);
		$this->request->setURL( self::URL . self::REQUEST_PATH );
		$this->setHeaders(1);
		return $this->getResult();
	}


	public function execGetTarget($targetId){
		 
		$this->request->setMethod( HTTP_Request2::METHOD_GET );
		$this->request->setConfig( ['ssl_verify_peer' => false ]);
		$this->request->setURL( self::URL . self::REQUEST_PATH.'/'.$targetId );
		$this->setHeaders();
		$this->getResult();
		
	}
	public function execGetAllTargets(){

		$this->request->setMethod( HTTP_Request2::METHOD_GET );
		$this->request->setConfig( ['ssl_verify_peer' => false ]);
		$this->request->setURL( self::URL . self::REQUEST_PATH );
		$this->setHeaders();
		$this->getResult();
		
	}
	public function execDeleteTarget($targetId){

		$path=self::REQUEST_PATH . '/' . $targetId;
		$this->request = new HTTP_Request2();
		$this->request->setMethod( HTTP_Request2::METHOD_DELETE );
		$this->request->setConfig( ['ssl_verify_peer' => false] );
		$this->request->setURL( self::URL . $path );
		$this->setHeaders();
		$this->getResult();

	}

	public function execUpdateTarget( $jsonBody ,$targetId ){
		
		$path = self::REQUEST_PATH . '/' . $targetId;
		$this->request->setMethod( HTTP_Request2::METHOD_PUT );
		$this->request->setBody( $jsonBody );
		$this->request->setConfig(['ssl_verify_peer' => false]);
		$this->request->setURL( self::URL  . $path );
		$this->setHeaders();
		$this->getResult();
		


	}

	private function getResult(){
		try {

			$response = $this->request->send();

			if (200 == $response->getStatus()) {
				$data=json_decode($response->getBody());
				
				return $data;
			} else {
				//echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .$response->getReasonPhrase(). ' ' ;
				$data=json_decode($response->getBody());
				
				return $data;
			}
		} catch (HTTP_Request2_Exception $e) {
			echo 'Error: ' . $e->getMessage();
		}

	}
	private function setHeaders($w=1){
		$sb = 	new \App\libs\SignatureBuilder();
		$date = new DateTime("now", new DateTimeZone("GMT"));
		$this->request->setHeader('Date', $date->format("D, d M Y H:i:s") . " GMT" );
		if($w){
			$this->request->setHeader("Content-Type", "application/json" );
		}
		$this->request->setHeader("Authorization" , "VWS " . self::ACCESS_KEY . ":" . $sb->tmsSignature( $this->request , self::SECRET_KEY ));

	}
	private function setContentType(){
		$this->request->setHeader("Content-Type", "application/json" );
	}

}