<?php
namespace App\libs;
class ApiConf{
	const  VUFORIA_ACCESS_KEY='065eccc60af1a144e6f2dcdaaea85b458fa995ac';
	const  VUFORIA_SECRET_KEY='365e79b9788f2a490fec67a5c431fff70fe59bab';
	const  VUFORIA_API_URL='https://vws.vuforia.com';
	const  VUFORIA_API_PATH='/targets';
	const  OSS_ACCESS_KEY='WGsJfWhac2ywzeSG';
	const  OSS_SECRET_KEY='ZgmugDWzkXYRV67zglra3hStdYWrlC';
	const  OSS_ENDPOINT='oss-cn-shanghai.aliyuncs.com';
	const  PAGE_SIZE=10;
	const  OSS_BUKET_NAME_ELEMENTS='element2';
	const  OSS_BUKET_NAME_TARGETS='target2';
    const ClientID = 'AaxLo1BmizU0WsH01Kvv61f4Bxy0m5SGVfA7YS_TU-2Jck2l2Bsepo2x27d8lDdxX5zlMudXmHzeXZAL';
    const Secret = 'EKfzKgRbH7nfi7QR6EvI4MlnlAUt6PptO7CPOE21MDYiU4Io8Z5bZKMDEH1sLCuyQGFCP26FvWvCBT7w';
    const mode = 'live';
    const IMG_URI='https://element2.oss-cn-shanghai.aliyuncs.com/';
    const TARGET_URI='https://target2.oss-cn-shanghai.aliyuncs.com/';
    const COUPON=[
        'survey'=>1,
        'invite'=>1,
        'modeler'=>5
    ];
    const SMALL_IMAGE_W    =   '@0o_0l_100w_90q.src';//按比例缩放的小图片
    const MID_IMAGE_W      =   '@0o_0l_300w_90q.src';//按比例缩放的中图片
    const BIG_IMAGE_W      =   '@0o_0l_800w_90q.src';//按比例缩放的大图片
    const SMALL_IMAGE_WH     =   '@';//按宽裁切的小图片
    const MID_IMAGE_WH       =   '@';//按宽裁切的中图片
    const BIG_IMAGE_WH       =   '@';//按宽裁切的大图片
    const DEFAULT_IMG       =   '/images/default.png';



}
