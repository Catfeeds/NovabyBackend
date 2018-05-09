<?php

namespace App\Http\Controllers\Api;

use App\libs\SiteConfig;
use App\Model\Config;
use App\Model\Country;
use App\Model\Job;
use App\Model\Likes;
use App\Model\Project;
use App\Model\User;
use App\Model\UserExplain;
use App\Model\Web;
use App\Model\Work;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Input;

class HomeApiController extends BaseApiController
{
    public function home()
    {
        $banner = [];
        $_baner = DB::table('banners')->select('photo','model_id','words')->orderBy('id','ASC')->limit(5)->get();
        $wids=[];
        foreach($_baner AS $k=>$v){
            $wids[]=$v->model_id;
        }
        $indexWorks=Work::select('work_id','work_uid','work_cover','work_title')->whereIn('work_id',$wids)->get();
        foreach($indexWorks AS $k=>$v){
            $indexWorks[$k]->cover = $this->getOssPath($_baner[$k]->photo,'?x-oss-process=image/resize,m_lfit,w_1440,limit_0/auto-orient,0/quality,q_90');
            $_icon = User::select('user_name','user_lastname')->find($v->work_uid);
            $indexWorks[$k]->user_name = $_icon->user_name.' '.$_icon->user_lastname;
            $has_liked = 0;
            if($this->_user && $this->_user->user_id){
                $ck_liked = Likes::where('like_eid',$v->work_id)->
                where('like_uid',$this->_user->user_id)->
                where('liked',1)->first();
                if($ck_liked){
                    $has_liked=1;
                }
                $indexWorks[$k]->liked=$has_liked;
                unset($indexWorks[$k]->work_cover);
            }
        }
        $banner['lists'] = $indexWorks;
        $confs = Config::whereIn('name',['title','subtitle'])->get();
        $title = [];
        $subtitle = [];
        if(count($confs)){
            $title = explode("\r\n",$confs[0]->value);
            $subtitle = explode("\r\n",$confs[1]->value);
        }

        $banner['abstract']=[
            'title'=>$title,
            'subtitle'=>$subtitle
        ];
        $pick[1] = Work::select('work_id','work_detail','work_cover','work_title','work_views')->where('work_homeRecommend','!=',null)
            ->where(['work_status'=>1,'work_del'=>0])
            ->orderBy('work_homeRecommend','desc')->take(8)->get();
        if(count($pick)<8) {
            $pick[2] = Work::select('work_id','work_detail','work_cover','work_title')->where('work_homeRecommend',null)
                ->where(['work_status'=>1,'work_del'=>0])
                ->orderBy('work_id','desc')->limit(8-count($pick))->get();
            $pick = collect($pick)->collapse()->unique()->all();
        }else{
            $pick = $pick->all();
        }
        foreach($pick AS $k=>$v){
            $pick[$k]['work_cover'] = $this->getOssPath($v['work_cover'],'500');
            $pick[$k]['work_objs'] = $v['work_detail']?1:0;
            $pick[$k]['cover'] = $pick[$k]['work_cover'];
            unset($pick[$k]['work_detail'],$pick[$k]['work_cover']);
        }
        if($this->Mobile()){
            $pick = array_slice($pick,0,3);
        }
        $picks['lists'] = $pick;
        $artists=[];
        if ($this->lang=='zh') {
            $artists['lists']=$this->artist('zh');
        }else{
            $artists['lists']=$this->artist('en');
        }
        $datas = [
            //'model' => $this->getModelFilesById(1419),
            'model'     => $this->model(),
            'banners'   =>  $banner,
//            'intro'     =>  $intro,
            'picks'     =>  $picks,
            'artists'   =>  $artists,
            'partner'   =>  $this->partner()
        ];
        $ip = Web::where('visitor_ip',$this->clientIP())->first();
        $number = Web::find(1);
        $number->visitor_number++;
        $number->save();
        if(!$ip)
        {
            $visitor = new Web();
            $visitor->visitor_number = $number->visitor_number;
            $visitor->visitor_ip = $this->clientIP();
            $visitor->save();
        }else{
            $ip->visitor_number = $number->visitor_number;
            $ip->save();
        }
        return $this->jsonOk('ok',$datas);
    }
    public function getVisitor()
    {
	 $token = Input::get('token');
        if($token=='wunan')
        {
            $data = [];
            $number = Web::find(1);
            $visitor = $number->visitor_number;
            $ips = Web::where('id','!=',1)->pluck('visitor_ip')->all();
            $data['ip'] = $ips;
            $data['visitor'] = $visitor;
            return $data;
        }else{
            return $this->jsonErr('error','Faild Token');
        }
     }

    /**
     *
     * @param $lang
     * @return array
     */
     private function artist($lang)
     {
         if($lang=='en') {
             $user =[
//                 0=>['name'=>'Carrie Porter','country'=>'USA','job'=>'2D Artist','work_exp'=>'2 Years experience','explain'=>'I got a lot of inspiration from Novaby and found great design work here.','avatar'=>env('APP_URL').'/avatar/Group3.png'],
//                 1=>['name'=>'Sam Chandler','country'=>'England','job'=>'3D modeler','work_exp'=>'5 Years experience','explain'=>'Novahub is pipeline is easy to use, creative and innovative','avatar'=>env('APP_URL').'/avatar/Group2.png'],
//                 2=>['name'=>'Tom Kim','country'=>'USA','job'=>'3D modeler','work_exp'=>'6 Years experience','explain'=>'The feedback process with clients is annoying. NovaHub improves the process.','avatar'=>env('APP_URL').'/avatar/Group.png'],
//                 3=>['name'=>'Jimmy Yen','country'=>'USA','job'=>'3D Artist','work_exp'=>'8 years experience','explain'=>'Novaby is creating an evolutionary way of online workflow, the future of 3D industry.','avatar'=>env('APP_URL').'/avatar/Group.png'],
                 0=>['name'=>'Akshay Raghuvanshi','country'=>'India','job'=>'3D Artist','work_exp'=>'5 years experience','explain'=>'I was able to collaborate with a client to really flesh out all the errors and actually was able to deliver my final project before the deadline.','avatar'=>env('APP_URL').'/avatar/Group4.png'],
                 1=>['name'=>'Nabil Chequeiq','country'=>'USA','job'=>'3D Artist','work_exp'=>'','explain'=>'i was glad to be in NOVABY because you can easily find great artist around you here',env('APP_URL').'/avatar/Group5.png']
             ] ;
         }else{
             $user =[
//                 0=>['name'=>'Carrie Porter','country'=>'美国','job'=>'2D艺术家','work_exp'=>'2年工作经验','explain'=>'在这里我发现了很多非常棒的作品，带给我很多灵感','avatar'=>env('APP_URL').'/avatar/Group3.png'],
//                 1=>['name'=>'Sam Chandler','country'=>'英国','job'=>'3D模型师','work_exp'=>'5年工作经验','explain'=>'我用过NovaHub，操作很简单。虽然还需要一些改进，但非常有创意','avatar'=>env('APP_URL').'/avatar/Group2.png'],
//                 2=>['name'=>'Tom Kim','country'=>'美国','job'=>'3D模型师','work_exp'=>'6年工作经验','explain'=>'与客户之间的反馈是一件很麻烦的事情，NovaHub恰好改进了这一步骤','avatar'=>env('APP_URL').'/avatar/Group.png'],
//                 3=>['name'=>'Jimmy Yen','country'=>'美国','job'=>'3D艺术家','work_exp'=>'8年工作经验','explain'=>'Novaby正在创造一种在线工作流程的演进方式，即3D产业的未来','avatar'=>env('APP_URL').'/avatar/Group.png'],
                 0=>['name'=>'Akshay Raghuvanshi','country'=>'印度','job'=>'3D艺术家','work_exp'=>'5年工作经验','explain'=>'我能够与客户合作真正找到所有的错误，并且实际上能够在截止日期之前交付我的最终项目','avatar'=>env('APP_URL').'/avatar/Group4.png'],
                 1=>['name'=>'Nabil Chequeiq','country'=>'美国','job'=>'3D Artist','work_exp'=>'','explain'=>'我很高兴在NOVABY，因为在这里你可以很容易地找到你身边的伟大的艺术家','avatar'=>env('APP_URL').'/avatar/Group5.png']
             ] ;
         }
         return $user;
     }
    private function partner()
    {
       $partner = [
           '3'=>['url'=>env('APP_URL').'/partner/icon_11.png'],
           '4'=>['url'=>env('APP_URL').'/partner/icon_boo.png'],
           '5'=>['url'=>env('APP_URL').'/partner/icon_leiting.png'],
           '0'=>['url'=>env('APP_URL').'/partner/icon_moyan.png'],
           '1'=>['url'=>env('APP_URL').'/partner/icon_xiangcheng.png'],
           '2'=>['url'=>env('APP_URL').'/partner/icon_yique.png']
       ];
        return $partner;
    }

    private function model()
    {
        $country = $this->checkIp();
        $model = [];
        if($country=='CN'){
            $model['model_url']['dir'] = 'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/';
            $model['model_url']['file'] = 'scene.gltf';
            $model['model_url']['size'] = 0;
            $model['model_mets'] = [
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/scene.bin',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material_emissive.jpeg',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material_diffuse.jpeg',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Eye_diffuse.jpeg',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material1_emissive.jpeg',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material1_diffuse.jpeg',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material1_specularGlossiness.jpeg',
                'https://elements-cn.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material_specularGlossiness.jpeg'];
        }else{
            $model['model_url']['dir'] = 'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/';
            $model['model_url']['file'] = 'scene.gltf';
            $model['model_url']['size'] = 0;
            $model['model_mets'] = [
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/scene.bin',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material_emissive.jpeg',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material_diffuse.jpeg',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Eye_diffuse.jpeg',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material1_emissive.jpeg',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material1_diffuse.jpeg',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material1_specularGlossiness.jpeg',
                'https://elements-us.novaby.com/20180305172559/model/bb-8_animation_repost/textures/Scene_Material_specularGlossiness.jpeg'];
        }
        $model['model_format'] = 'gltf';
        $model['mtl'] = [];
        $model['id'] = 1;
        $straighten = [
            'x'=>0,
            'y'=>0,
            'z'=>0,
        ];
        $background = [
            'style'=>'none',
            'value'=>''
        ];
        $light = [
            'brightness'=>1
        ];
        $scene['straighten'] = $straighten;
        $scene['background'] = $background;
        $model['edit']['scene'] = $scene;
        $model['edit']['light'] = $light;
        return $model;
    }





}
