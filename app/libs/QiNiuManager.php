<?php
/**
 * Created by PhpStorm.
 * User: wunan
 * Date: 2018/2/25
 * Time: 下午5:06
 */
namespace App\libs;
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
class QiNiuManager
{
    const ACCESS_KEY 	= '7N1ifOGc_Tehyzp_lirnoiPK_aCk7NPuaT_DnUxd';
    const SECRET_KEY 	= 'xkb3AMSdRs2zD2aBNKXLOQJ_WhKhPpUcG60SVhp4';
    const bucket_cn = [
        0=>[
            'key'=>'elements',
            'value'=>'elements-cn'],
        1=>[
            'key'=>'targets',
            'value'=>'targets-cn'],
        2=>[
            'key'=>'test',
            'value'=>'test-cn'],
    ];
    const bucket_us = [
        0=>[
            'key'=>'elements',
            'value'=>'elements-us'],
        1=>[
            'key'=>'targets',
            'value'=>'targets-us'],
        2=>[
            'key'=>'test',
            'value'=>'test-us'],
    ];
    /**
     * @var UploadManager
     */
    private $uploadManager;
    /**
     * @var Auth
     */
    private $auth;
    /**
     * @var
     */
    private $bucket;
    /**
     * @var
     */
    public $key;

    /**
     * QiNiuManager constructor.
     * @param $item
     */
    public function __construct($item)
    {
        $this->uploadManager = new UploadManager();

        $this->auth = new Auth(self::ACCESS_KEY,self::SECRET_KEY);

        $this->key = $this->selectBuckets($item)['key'];

        $this->bucket = $this->selectBuckets($item)['value'];
    }

    /**
     * 获取七牛云token
     * @param $bucket
     * @return string
     */
    public function getToken($bucket='test-cn')
    {
        $token = $this->auth->uploadToken($bucket);
        return $token;
    }

    /**
     * 七牛云上传
     * @param $name
     * @param $filePath
     * @param null $params
     * @param string $mime
     * @param bool $checkCrc
     * @return mixed
     */
    public function upload($name,$filePath, $params = null, $mime = 'application/octet-stream', $checkCrc = false)
    {
        $upToken = $this->getToken($this->bucket);
        list($ret,$err) = $this->uploadManager->putFile($upToken,$name,$filePath,$params,$mime,$checkCrc);
        $result['result'] = $ret;
        $result['error'] = $err;
        return $result;
    }

    /**
     * 根据ip选择bucket
     * @param $item
     * @return mixed
     */
    public function selectBuckets($item)
    {
        $cIP = getenv('REMOTE_ADDR');
        $cIP1 = getenv('HTTP_X_FORWARDED_FOR');
        $cIP2 = getenv('HTTP_CLIENT_IP');
        $cIP1 ? $cIP = $cIP1 : null;
        $cIP2 ? $cIP = $cIP2 : null;
        $result = @file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$cIP);
        $result = json_decode($result);
        if($result){
            if($result->country=='中国'){
                return QiNiuManager::bucket_cn[$item];
            }else{
                return QiNiuManager::bucket_us[$item];
            }
        }else{
            return QiNiuManager::bucket_us[$item];
        }

    }
}