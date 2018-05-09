<?php

namespace App\Http\Controllers\Api;

use App\libs\ApiConf;
use App\libs\QiNiuManager;
use App\libs\Tools;
use App\Model\Cate;
use App\Model\Message;
use App\Model\Permission;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\ProjectUser;
use App\Model\Tag;
use App\Model\WorkDetail;
use App\Model\User;
use App\Model\Work;
use App\Model\Job;
use App\Model\Country;
use App\Model\Following;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use App\Model\Ossitem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Validator;
use Dingo\Api\Exception\ResourceException;

class BaseApiController extends Controller
{
    use Helpers;

    public $_user;

    public $lang;
    /**
     * BaseApiController constructor.
     * @param Request $req
     */
    public function __construct(Request $req){
        $token = $req->header('token')?$req->header('token'):$req->get('token');
        $this->_user = null;
        if($token){
            $user = User::where('user_token',$token)->first();
            if($user){
                $this->_user = $user;
            }
        }
        $this->lang = $req->get('lang','en');
        switch ($this->lang)
        {
            case 'zh':
                App::setLocale('zh_cn');
                break;
            default:
                App::setLocale('en');
                break;
        }
    }
    public function jsonOk($msg,$data,$code=200){
        return $this->response->array(['data'=>$data,'code'=>$code,'msg'=>$msg]);
    }
    public function jsonErr($msg,$data=[],$code=-1){
        return $this->response->array(['data'=>$data,'code'=>$code,'msg'=>$msg]);
    }

    public function getAvatar($id,$size='300'){
        if(!$id){
            return env('APP_URL').ApiConf::DEFAULT_IMG;
        }else{
            $item = Ossitem::find($id);
            $size = $this->qiNiuwebp($size);
            return $this->getOssUrl($item->oss_key).$item->oss_path.$size;
        }
    }
    public function getOssPath($id,$size='-1'){
        if(!$id){
            return env('APP_URL').ApiConf::DEFAULT_IMG;
        }elseif($id==1){
            return env('APP_URL').'/images/model-default.png';
        }else{
            $item = Ossitem::find($id);
            if($size=='-1'){
                $size=null;
            }else{
                $size = $this->qiNiuwebp($size);
            }
            return $this->getOssUrl($item->oss_key).$item->oss_path.$size;
        }
    }
//    private function webp($size){
//        if(strpos($_SERVER['HTTP_USER_AGENT'],"Chrome")>0  && strpos($_SERVER['HTTP_USER_AGENT'],"Edge")< 0){
//            if($size){
//                if($size=='1000'){
//                    $size = '?x-oss-process=style/webp_1000_q90';
//                    if($this->Mobile()){
//                        $size = '?x-oss-process=style/webp_1125_q90';
//                    }
//                }
//                if($size=='1440'){
//                    $size = '?x-oss-process=style/webp_1440_q90';
//                    if($this->Mobile()){
//                        $size = '?x-oss-process=style/webp_800_q90';
//                    }
//                }
//                if(strpos($size,'400')>0){
//                    $size = '?x-oss-process=style/webp_400_q90';
//
//                }
//                if(strpos($size,'500')>0){
//                    $size = '?x-oss-process=style/webp_500_q90';
//                    if($this->Mobile()){
//                        $size = '?x-oss-process=style/webp_300_q90';
//                    }
//                }
//                if(strpos($size,'800')>0){
//                    $size = '?x-oss-process=style/webp_800_q90';
//                    if($this->Mobile()){
//                        $size = '?x-oss-process=style/webp_400_q90';
//                    }
//                }
//                if(strpos($size,'100')>0){
//                    $size = '?x-oss-process=style/webp_100_q90';
//                }
//                if(strpos($size,'200')>0){
//                    $size = '?x-oss-process=style/webp_200_q90';
//                    if($this->Mobile()){
//                        $size = '?x-oss-process=style/webp_100_q90';
//                    }
//                }
//                if(strpos($size,'300')>0){
//                    $size = '?x-oss-process=style/webp_300_q90';
//                    if($this->Mobile()){
//                        $size = '?x-oss-process=style/webp_200_q90';
//                    }
//                }
//
//            }
//            else{
//                $size = '?x-oss-process=style/originwebp';
//            }
//    }elseif($this->Mobile()){
//            if($size){
//                if(strpos($size,'1000')>0){
//                    $size = str_replace("w_1000","w_1125",$size);
//                    $size = str_replace("q_10","q_60",$size);
//
//                }
//                if(strpos($size,'1440')>0){
//                    $size = str_replace("w_1440","w_1125",$size);
//                    $size = str_replace("q_90","q_70",$size);
//
//                }
//                if(strpos($size,'500')>0){
//                    $size = str_replace("w_1440","w_300",$size);
//
//                }
//                if(strpos($size,'300')>0){
//                    $size = str_replace("w_1440","w_200",$size);
//
//                }
//                if(strpos($size,'200')>0){
//                    $size = str_replace("w_1440","w_100",$size);
//
//                }
//            }
//        }else{
//            $size='?x-oss-process=image/resize,m_lfit,w_'.$size.',limit_0/auto-orient,0/quality,q_70';
//        }
//    return $size;
//}
//    private function ossurl($item){
//        $paths = [
//
//            'elements'  =>'element2.oss-cn-shanghai.aliyuncs.com',
//            'targets'   =>'target2.oss-cn-shanghai.aliyuncs.com',
//            'novahub'   =>'novahub.oss-us-west-1.aliyuncs.com',
//            'novacloud' =>'novacloud.oss-us-west-1.aliyuncs.com',
//        ];
//        if(!$item){
//            return '';
//        }
//
//        return 'https://'.$paths[$item]."/";
//    }
    public function getDownPath($id){

        $item = Ossitem::find($id);
        return $this->getOssUrl($item->oss_key).$item->oss_path;

    }
    public function getModelFilesById($id){
        $modelDetail = WorkDetail::where(['id'=>$id])->first();
        if(!$modelDetail) return '';
        $detail = (object)[];
        $model_url = $this->getOssPath($modelDetail->w_objs,'-1');
        if($model_url){
                //$data = file_get_contents($model_url);
                $last = strrpos($model_url,"/");
                $_cks1 = explode("/",$model_url);
                $arr = [
                    'dir'=>substr($model_url,0,$last+1),
                    'file'=>$_cks1[count($_cks1)-1],
                    'size'=>0,
                ];
            $detail->model_format = $modelDetail->w_format;
            $detail->model_url=$arr;


            $mets = [];
            $mtl = (object)[];
            $ids = explode(",",$modelDetail->w_mets);
            //$ids = array_unique(array_merge($ids,$modelDetail->w_objs));
            foreach ($ids AS $k=>$v){
            if($v>0) {
                $__v = $this->getOssPath($v,'-1');
                $mets[] = $__v;
                $_cks = explode("/", $__v);
                $_ckss = explode(".", $_cks[count($_cks) - 1]);
                if (end($_ckss) == 'mtl') {
                    $mtl->file = $_cks[count($_cks) - 1];
                    $last = strrpos($__v, "/");
                    $mtl->dir = substr($__v, 0, $last + 1);
                }
            }

        }
        $detail->model_mets = $mets;
        $detail->mtl = $mtl;
        $detail->id = $id;
            $straighten = [
                'x'=>0,
                'y'=>0,
                'z'=>0,
            ];
            $background = [
                'style'=>'color',
                'value'=>'575761'
            ];
            $light = [
                'brightness'=>0.7
            ];
            $scene['straighten'] = $straighten;
            $scene['background'] = $background;
            $detail->edit['scene'] = $scene;
            $detail->edit['light'] = $light;
            if(!empty($modelDetail->work_model_edit)){
                $detail->edit = json_decode($modelDetail->work_model_edit);
            }
        }
        return $detail;

    }

    public function getAuthorAndWorks1($uid){
        $user = User::select('user_id','user_name','company_name','user_lastname','user_type','user_icon','user_country','user_job','user_city')
            ->where(['user_id'=>$uid])
            ->first();

        $user->user_name = $user->user_type==4?$user->company_name:$user->user_name.' '.$user->user_lastname;
        unset($user->user_lastname,$user->company_name);

        $_country='';
        if($user->user_country){
            $_country = Country::where(['id'=>$user->user_country])->first();
            $_country=$_country->name;
        }
        $_job='';
        if($user->user_job){
            $_job = Job::where(['id'=>$user->user_job])->first();
            $_job = $_job->name;
        }
        $user->user_country = $_country;
        $user->user_job = $_job;
        if($user->user_city){
            $_city = Country::where(['id'=>$user->user_city])->first();
            $_city = $_city->name;
            $user->user_city = $_city;
        }else{
            $user->user_city = '';
        }

        $user->user_avatar= $this->getAvatar($user->user_icon);

        unset($user->user_icon);
        $_recent_works = Work::select('work_id','work_cover')
            ->where('work_uid',$uid)
            ->orderBy('work_id','desc')
            ->limit(3)
            ->get();

        foreach($_recent_works AS $k=>$v){
            $_recent_works[$k]->work_cover = $this->getAvatar($v->work_cover,'500');
        }
        $user->works = $_recent_works;

        $user->isfollow = 0;
        $user->me = 0;

        if($this->_user){
            $user->isfollow = Following::where(['from_uid'=>$this->_user->user_id,'to_uid'=>$uid,'followed'=>1])->count();
            $user->me = $this->_user->user_id==$uid?1:0;
        }
        return $user;
    }
    public function createToken($uid){
        return md5(time().''.$uid);
    }
    public function addMsg($from,$to,$action,$rid){
        $msg = new Message();
        $msg->msg_from_uid=$from;
        $msg->msg_to_uid=$to;
        $msg->msg_action=$action;
        $msg->msg_rid=$rid;
        $msg->msg_time=time();
        if($msg->save()){
            return true;
        }
        return false;

    }

    public function resourceValidate($rules, $attributes = [])
    {
        $playload = Request::all();
        $validator = Validator::make($playload, $rules, [], $attributes);
        if ($validator->fails()) {
            throw new StoreResourceFailedException('validate_failed', $validator->errors());
        }
    }
    public function prjFormat($str){
        $arr = explode(",",$str);
        $format = Cate::whereIn('cate_id',$arr)->get();
        $str ='';
        foreach($format AS $k=>$v){
            $str.=$v->cate_name.' ';
        }
        return $str;


    }

    /**
     * * 判断是甲方还是乙方调用接口。对某些操作接口进行限制,若甲方接了自己发的任务，判断乙方失效
     *
     * @param $prj_id 项目ID
     *
     * @return int 1 甲方 2 乙方 0 不确定
     */
    public function judgeCaller($prj_id){
        $ret = 0;
        $prj = Project::select('prj_uid','prj_modeler')->find($prj_id);
        if(!$prj || !$this->_user){
            $ret =0;
        }
        if($this->_user && $this->_user->user_id==$prj->prj_uid){
            $ret =  1;
        }

        if($this->_user && $this->_user->user_id==$prj->prj_modeler){
            $ret = 2;
        }
        return $ret;

    }
    public function formatTags($str){
        if(!$str){
            return '';
        }
        $tag_arr = explode(",",$str);
        $tags = Tag::whereIn('tag_id',$tag_arr)->get();
        $ret =[];
        foreach($tags AS $k=>$v){
            $ret[] = $v->tag_name;
        }
        return $ret;


    }
    public function zipinfo($id){
        $oss = Ossitem::find($id);
        if(!$oss){
            return '';
        }
        $_data = explode("/",$oss->oss_path);
        $name = $_data[count($_data)-1];
        $info['name']=$name;
        $info['size']=$oss->size;
        $info['url']=$this->getOssUrl($oss->oss_key).$oss->oss_path;
        $info['src']=explode('/',$oss->oss_path)[0];
        return $info;

    }
//    public function zipinfo2($id){
//        $oss = Ossitem::find($id);
//        if(!$oss){
//            return '';
//        }
//        $_data = explode("/",$oss->oss_path);
//        $name = $_data[count($_data)-1];
//        $info['name']=$name;
//        $info['size']=Tools::sizeConvert($oss->size);
//        $info['url']=$this->getOssUrl($oss->oss_key).$oss->oss_path;
//        $info['src']=explode('/',$oss->oss_path)[0];
//        return $info;
//
//    }

    /**
     * 移动端判断
     * @return bool
     */
    public function Mobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
        {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT']))
        {
            $clientkeywords = array ('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'oppo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT']))
        {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * 英语级别
     * @param $english
     * @return mixed
     */
    public function getEnglish($english)
    {
        $englishs = [
            [
                'id'=>0,
                'name'=>'Any english level',
            ],
            [
                'id'=>1,
                'name'=>'Basic english',
            ],
            [
                'id'=>2,
                'name'=>'Conversational english',
            ],
            [
                'id'=>3,
                'name'=>'Fluent english',
            ],
            [
                'id'=>4,
                'name'=>'Native or Bilingual english',
            ]

        ];
        foreach($englishs AS $k=>$v){
            if($english == $v['id'])
            {
                $english = $v['name'];
            }
        }
        return $english;
    }

    /**
     * 工作经验
     * @param $exp
     * @return mixed
     */
    public function getExp($exp)
    {
        $exps = [
            [
                'id'=>1,
                'name'=>'1-3 years',
            ],
            [
                'id'=>2,
                'name'=>'3-5 years',
            ],
            [
                'id'=>3,
                'name'=>'more than 5 years',
            ]
        ];
        foreach($exps AS $k=>$v){
            if($exp == $v['id'])
            {
                $exp = $v['name'];
            }
        }
        return $exp;
    }
    /**
     * 获得模型license
     * @param $license
     * @return mixed
     */
    public function getLicense($license)
    {
        $licences = [
            [
                'id'=>1,
                'name'=>'Attribution',
            ],
            [
                'id'=>2,
                'name'=>'Non-Commercial',
            ],
            [
                'id'=>3,
                'name'=>'Non-derivatives',
            ],
            [
                'id'=>4,
                'name'=>'Share alike',
            ],

        ];
        foreach($licences AS $k=>$v){
            if($license == $v['id'])
            {
                $license = $v['name'];
            }
        }
        return $license;
    }
    /**
     * 总利润转换
     * @param $amount
     * @return string
     */
    public function transAmount($amount)
    {

        switch($amount)
        {
            case $amount<1000:
                return $amount;
                break;
            case $amount<10000:
                $amount = substr((string)$amount,0,1).'k+';
                return $amount;
                break;
            case $amount<100000:
                $amount = substr((string)$amount,0,1).'0k+';
                return $amount;
                break;
            case $amount<1000000:
                $amount = substr((string)$amount,0,1).'00k+';
                return $amount;
                break;
            case $amount<10000000:
                $amount = substr((string)$amount,0,1).'000k+';
                return $amount;
                break;
            case $amount<100000000:
                $amount = substr((string)$amount,0,1).'0000k+';
                return $amount;
                break;
            default:
                return $amount;
                break;
        }
    }

    /**
     * 判断用户是否与项目有关
     * @param $project_id
     * @return int
     */
    public function isUserBelongProject($project_id)
    {
        $user = $this->_user;
        if(!$user){
            return false;
        }else{
            $project_user = ProjectUser::where(['project_id'=>$project_id,'user_id'=>$user->user_id])->with('role')->first();
            $apply = PrjApply::where(['prj_id' => $project_id, 'user_id' =>$user->user_id])->first();
            if(!$project_user && !$apply){
                return false;
            } elseif($project_user){
                return $project_user;
            } else{
                return $apply;
            }
        }

    }

    /**
     * 验证方法
     * @param $request
     * @param $rules
     * @return bool
     */
    public function postValidate($request,$rules)
    {
        $validator = Validator::make($request,$rules);
        if ($validator->fails()) {
            $error = $validator->errors();
            return $error;
        }else{
            return false;
        }
    }
    /**
     * 判断用户输入的是否为url
     * @param $url
     * @return bool|string
     */
    public function inputUrl($url)
    {
        if(preg_match('/(http:\/\/|https:\/\/)+www.[0-9a-zA-Z]+_?[0-9a-zA-Z]+.[0-9a-zA-Z]{2,}$/',$url)) {
            return  $url;
        }elseif(preg_match('/^www.[0-9a-zA-Z]+_?[0-9a-zA-Z]+.[0-9a-zA-Z]{2,}$/',$url)){
            return 'http://'.$url;
        }elseif(preg_match('/[0-9a-zA-Z]+_?[0-9a-zA-Z]+.[0-9a-zA-Z]{2,}$/',$url)){
            return 'http://www.'.$url;
        }else{
            return false;
        }
    }

    /**
     * 替换空格
     * @param $name
     * @return mixed
     */
    public function replaceTheSpace($name)
    {
        $result = str_replace('/\ /','',$name);
        return $result;
    }
    /**
     * @param string $lang
     * @param $key
     * @return string
     */
    public function returnErr($lang='en',$key)
    {
        switch ($key)
        {
            case 'name':
                $value = $lang=='zh'?'用户名已存在':'user name has exists';
                break;
            case 'page':
                $value = $lang=='zh'?'主页已存在':'home page  has been used';
                break;
            case 'field':
                $value = $lang=='zh'?'专长不能超过三个':'Fields can not be more then 3';
                break;
            case 'company':
                $value = $lang=='zh'?'公司名已存在':'company name  has been used';
                break;
            default:
                $value = $lang=='zh'?'用户名已存在':'user name has exists';
                break;
        }
        return $value;
    }
    protected function getName($user)
    {
        $name = $user->user_type==4?$user->company_name:$user->user_name." ".$user->user_lastname;
        return $name;
    }

    public function Menu($menus)
    {
        $roots = $menus->filter(function ($item){
            return !$item->pid;
        });
        $menu = $roots->map(function ($root)use($menus){
            $root->image = $root->url;
            $root->value = $menus->filter(function ($item)use($root){
                return $item->pid == $root->id;
            });
            return $root;
        });
        return $menu;
    }
    /**
     * 获取客户端ip
     * @return array|false|string
     */
    public function clientIP()
    {
        $cIP = getenv('REMOTE_ADDR');
        $cIP1 = getenv('HTTP_X_FORWARDED_FOR');
        $cIP2 = getenv('HTTP_CLIENT_IP');
        $cIP1 ? $cIP = $cIP1 : null;
        $cIP2 ? $cIP = $cIP2 : null;
        return $cIP;
    }
    /**
     * 判断浏览器返回webp图片
     * @param $size
     * @return string
     */
    private function qiNiuwebp($size){
        if(strpos($_SERVER['HTTP_USER_AGENT'],"Chrome")>0  && strpos($_SERVER['HTTP_USER_AGENT'],"Edge")== 0){
            return $size = '?imageMogr2/auto-orient/thumbnail/'.$size.'/format/webp/interlace/1/blur/1x0/quality/90';
        }else{
            return $size = '?imageMogr2/auto-orient/thumbnail/'.$size.'/interlace/1/blur/1x0/quality/90';
        }
    }

    /**
     * 根据ip地区获取不同节点的资源
     * @param $item
     * @return string
     */
    public function getOssUrl($item){
        $country = $this->checkIp();
        if($country=='CN'){         //国内节点
            $paths = [
                'elements'  =>'elements-cn.novaby.com',
                'targets'  =>'targets-cn.novaby.com',
                'test'  =>'test-cn.novaby.com',
            ];
        }else{
            $paths = [
                'elements'  =>'elements-us.novaby.com',    //国外节点
                'targets'  =>'targets-us.novaby.com',    //国外节点
                'test'  =>'test-us.novaby.com',    //国外节点
            ];
        }
        return 'https://'.$paths[$item]."/";
    }

    /**
     * 返回ip对应的国家
     * @return mixed
     */
    public function checkIp()
    {
        //geo库判断ip
        $gi = geoip_open('GeoIP.dat',GEOIP_STANDARD);
        $data = geoip_country_code_by_addr($gi,$this->clientIP());
        geoip_close($gi);
        return $data;
          //淘宝接口判断ip
//        $result = @file_get_contents("http://ip.taobao.com/service/getIpInfo.php?ip=".$ip);
//        $result = json_decode($result);
//        return $result->data->country_id;
    }

//    /**
//     * 根据ip获取不同节点
//     * @param $item
//     * @return mixed
//     */
//    public function getBuckets($item)
//    {
//        $country = $this->checkIp();
//        if($country=='CN'){
//            return QiNiuManager::bucket_cn[$item];
//        }else{
//            return QiNiuManager::bucket_us[$item];
//        }
//    }
    public function getPhotos($photos,$size='-1')
    {
        if(count($photos)){
            $photo = $photos->map(function ($item)use($size){
                $item = $this->getOssPath($item->oss_item_id,$size);
                return $item;
            });
            return $photo;
        }else{
            return '';
        }
    }

    public function getFiles($files)
    {
        if(count($files)){
            $file = $files->map(function ($item){
                $item = $this->zipinfo($item->oss_item_id);
                return $item;
            });
            return $file;
        }else{
            return '';
        }
    }

    public function getModelInfo($model,$key)
    {
        switch ($key)
        {
            case 'tag':

                break;
            case 'category':
                if($model->cate)
                {
                    $category = $this->lang=='zh'?$model->cate->cate_name_cn:$model->cate->cate_name;
                }
                if($model->category)
                {
                    $cate= array();
                    foreach ($model->category as $item)
                    {
                        $cate[] = $this->lang=='zh'?$item->cate_name_cn:$item->cate_name;
                    }
                    $category = implode(',',$cate);

                }
                return $category;
                break;
            default:
                break;
        }
    }

    public function basic_info($user)
    {
        $base_info = [
            'user_id'       =>  $user->user_id,
            'user_name'     =>  $this->getName($user),
            'user_avatar'   =>  $this->getAvatar($user->user_icon),
            'user_type'     =>  $user->user_type,
            'user_email'     =>  $user->user_email,
            'user_country' =>$user->country?$user->country->name:'',
            'user_city' =>$user->city?$user->city->name:'',
            'user_work' =>$user->Job?$user->Job->name:'',
            'user_lang' => $user->info->lang,
            'wallet' => $user->wallet ? $user->wallet->USD : 0,
        ];
        return $base_info;
    }

    /**
     * 权限
     * @param $prj_id
     * @param $page
     * @return array|string
     */
    public function userFeaturesPermission($prj_id,$page)
    {
        $user_role = $this->isUserBelongProject($prj_id);
        if(!$user_role || !$user_role->role){
            return ('no permission');
        }else{
            $roles = $user_role->role->rolePermission;
            $permission = Permission::where('display',$page)->with('child')->first();
            if(!$permission){
                return ('no this page');
            }else{
                if(!in_array($permission->id,$roles->pluck('permission_id')->toArray())){
                    return ('you have no permission in this page');
                }
                $data = $permission->child->map(function ($item)use($roles){
                    $item->able = $roles->where('permission_id', $item->id)
                        ->first();
                    unset($item->name_cn);
                    return $item;
                });
                $result = [];
                foreach ($data as $key => $value){
                    $result[$value->display] = [
                        'read' => $value->able->read ?? 0
                        ,'operate' => $value->able->operate ?? 0
                    ];
                }
                return $result;
            }
        }
    }
}
