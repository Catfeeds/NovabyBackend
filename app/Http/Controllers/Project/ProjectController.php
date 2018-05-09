<?php

namespace App\Http\Controllers\Project;

use App\libs\ApiConf;
use App\libs\Tools;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Session;
use App\Services\CreateProgress;

class ProjectController extends Controller
{

    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    private function ckstatus($data,$status){
        if(!$data || $data->prj_status<$status){
            abort(404);
            exit;
        }
    }
    private function photos($str, $size){
        $arr = explode(",", $str);
        $_ps = [];
        $_imgs = DB::table('oss_item')->whereIn('oss_item_id',$arr)->get();

        foreach($_imgs AS $k=>$v){
            $_tpm=[];
            $_tmp['id']  = $v->oss_item_id;
            $_tmp['url'] = ApiConf::IMG_URI.$v->oss_path.$size;
            $_ps[]=$_tmp;

        }
        return (object)$_ps;
    }
    public function createNewView($id = 0){
        $has_proj = 0;
        if($id){
            $project = DB::table('project')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();
            $this->ckstatus($project,1);
            $project->nav = CreateProgress::url($project->prj_status,1,$id);
            $has_proj = 1;
        }else{
            $project = (object)[];
            $project->prj_photos=[];
            $project->nav = CreateProgress::url(1,1,0);

        }
        $cates=DB::table('category')->where('cate_pid',0)->get();
        foreach ($cates as $k => $v) {
            $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
            $cates[$k]->sub=$sub_data;

        }
        $my_industry = isset($project->prj_industry) ? $project->prj_industry : $cates[5]->sub[0]->cate_id;
        $my_category = isset($project->prj_cate) ? $project->prj_cate : $cates[0]->sub[0]->cate_id;
        $industry = '<div class="col-790 choose-warp" data-type="radio">';
        foreach ($cates[5]->sub AS $k => $v) {
            $class = $my_industry == $v->cate_id ? 'active' : '';
            $industry .= '<span class="' . $class . '" data-value="' . $v->cate_id . '"> ' . $v->cate_name . '</span>';
        }
        $industry .= '<input type="hidden" name="Industry" value="' . $my_industry . '">';
        $industry .= '</div>';
        $htmls['industry'] = $industry;
        $category = '<div class="col-790 choose-warp" data-type="radio">';
        foreach ($cates[0]->sub AS $k => $v) {
            $class = $my_category == $v->cate_id ? 'active' : '';
            $category .= '<span class="' . $class . '" data-value="' . $v->cate_id . '"> ' . $v->cate_name . '</span>';
        }
        $category .= '<input type="hidden" name="Category" value="' . $my_category . '">';
        $category .= '</div>';
        $htmls['category'] = $category;

        $format = ($cates[3]->sub[0]);
        $format = $format->cate_id;
        if ($project && isset($project->prj_format)) {
            $format = $project->prj_format;
        }
        $formats = explode(",", $format);
        $format_html = '<div class="col-790 choose-warp" data-type="select">';
        foreach ($cates[3]->sub AS $k => $v) {
            if (in_array($v->cate_id, $formats)) {
                $class = 'active';
            } else {
                $class = '';
            }
            if($v->cate_name =='Other' || $v->cate_name =='other'){
                $format_html .= '<span class="' . $class . '" data-value="' . $v->cate_id . '"> '.$v->cate_name . '</span>';

            }else{
                $format_html .= '<span class="' . $class . '" data-value="' . $v->cate_id . '"> ' . '.'.$v->cate_name . '</span>';
            }
        }

        $format_html .= '<input type="hidden" name="Format" value="' . $format . '"></div>';
        $htmls['format_html'] = $format_html;
        $my_acc = isset($project->prj_acc) ? $project->prj_acc : 1;
        $accs = [1 => 'Default', '2' => 'High', '3' => 'Low'];
        $acc_html = '<div class="col-790 choose-warp" data-type="radio">';
        foreach ($accs AS $k => $v) {
            $class = $my_acc == $k ? 'active' : '';
            $acc_html .= '<span class="' . $class . '" data-value="' . $k . '">' . $v . '</span>';
        }
        $acc_html .= '<input type="hidden" name="Accuracy" value="' . $my_acc . '"> </div>';
        $htmls['acc_html'] = $acc_html;
        $my_texture = isset($project->prj_texture) ? $project->prj_texture : 1;
        $textures = [1 => 'Yes', '2' => 'No'];
        $texture_html = '<div class="col-790 choose-warp" data-type="radio">';
        foreach ($textures AS $k => $v) {
            $class = $my_texture == $k ? 'active' : '';
            $texture_html .= '<span class="' . $class . '" data-value="' . $k . '">' . $v . '</span>';
        }
        $texture_html .= '<input type="hidden" name="Texture" value="' . $my_texture . '"> </div>';
        $htmls['texture_html'] = $texture_html;

        $my_rigged = isset($project->prj_rigged) ? $project->prj_rigged : 1;
        $riggeds = [1 => 'Yes', '2' => 'No'];
        $rigged_html = '<div class="col-790 choose-warp" data-type="radio">';

        foreach ($riggeds AS $k => $v) {
            $class = $my_rigged == $k ? 'active' : '';
            $rigged_html .= '<span class="' . $class . '" data-value="' . $k . '">' . $v . '</span>';
        }
        $rigged_html .= '<input type="hidden" name="Rigged" value="' . $my_rigged . '"> </div>';
        $htmls['rigged_html'] = $rigged_html;
        $img_show_html = '';
        $img_form_val = '';

        if ($project->prj_photos) {
            $size = '300';
            $project->prj_photos = $this->photos($project->prj_photos, $size);

        }

        $project->prj_photos=isset($project->prj_photos)?$project->prj_photos:[];
        foreach ($project->prj_photos AS $k => $v) {
            $v = (object)$v;
            //$img_show_html .= '<div id="file-' . $v->id . '" class="imgouter">cover<i id="p-' . $v->id . '" style="position: absolute;left: 50%;display:none;">0%</i><i class="remove" id="in-' . $v->id . '">delete</i><div class="progress" style="height: 3px;position: relative;bottom: -170px;"><div class="progress-bar" id="prs-' . $v->id . '" aria-valuenow="100" style="width: 200px;"></div><b><span>100%</span></b></div><b></b><img src="' . $v->url . '"></div>';
            $img_show_html .= '<li class="img-items" id="file-' . $v->id . '">
                            <span class="delete-icon" id="d-' . $v->id . '"></span>
                            <img src="' . $v->url . '" alt="">
                        </li>';
            $img_form_val .= '<input type="hidden" id="data-' . $v->id . '" name="imgs" value="' . $v->id . '">';
        }

        $htmls['server_pics'] = $img_show_html;
        $htmls['server_pics_vals'] = $img_form_val;

        if($has_proj==1) {


            $htmls=[
                'title'=>$project->prj_name,
                'industry'=>$industry,
                'category'=>$category,
                'format_html'=>$format_html,
                'acc_html'=>$acc_html,
                'texture_html'=>$texture_html,
                'rigged_html'=>$rigged_html,
                'server_pics'=>$img_show_html,
                'server_pics_vals'=>$img_form_val,

            ];

            $project->htmls = (object)$htmls;

        }else{
            $htmls=[
                'title'=>'',
                'industry'=>$industry,
                'category'=>$category,
                'format_html'=>$format_html,
                'acc_html'=>$acc_html,
                'texture_html'=>$texture_html,
                'rigged_html'=>$rigged_html,
                'server_pics'=>'',
                'server_pics_vals'=>'',

            ];
            $project->htmls=(object)$htmls;
            $project->prj_desc='';
            $project->prj_id=0;
        }
        //dd($project);

            return view('v1.projects.new',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project]);
    }
    public function createNew(Request $req){

        $name      = $req->get('title','');
        $industry   = $req->get('Industry',0);
        $category   = $req->get('Category',0);
        $format     = $req->get('Format',0);
        $accuracy   = $req->get('Accuracy',0);
        $texture    = $req->get('Texture',0);
        $rigged     = $req->get('Rigged',0);
        $ref_photos = $req->get('imgs','');
        $desc       = $req->get('desc','');
        $draft      = $req->get('draft',0);
        $pid        = $req->get('pid',0);

        $photos_ids=[];
        foreach($ref_photos AS $k=>$v){
            if(strlen($v) > 11 && !is_numeric($v)) {
                $_v = explode('@',$v);


                $insert_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'elements',
                        'oss_path' => $_v[0],
                        'oss_item_uid' => $this->info->user_id,
                        'width' => $_v[1],
                        'height' => $_v[2],
                    ]
                );

                $photos_ids[]=$insert_id;
            }else{
                $photos_ids[]=$v;

            }


        }
        $photos_ids = implode(",",$photos_ids);
        $_data=[
            'prj_uid'=>$this->info->user_id,
            'prj_name'=>$name,
            'prj_industry'=>$industry,
            'prj_cate'=>$category,
            'prj_format'=>$format,
            'prj_acc'=>$accuracy,
            'prj_texture'=>$texture,
            'prj_rigged'=>$rigged,
            'prj_desc'=>$desc,
            'prj_pubtime'=>time(),
            'prj_photos'=>$photos_ids,
            'prj_uptime'=>time(),
            'prj_draft'=>$draft,




        ];
        if($pid>0){
            $ck_proj = DB::table('project')->select('prj_status')->where(['prj_id'=>$pid])->first();
            if($ck_proj->prj_status<2){
                $status = 2;
            }else{
                $status=$ck_proj->prj_status;
            }
            $_data['prj_status']=$status;

            $res = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->update($_data);
            if($res){
                return response()->json(['msg'=>'publish successfully','err'=>'ok','data'=>'','pid'=>$pid]);
            }else{
                return response()->json(['msg'=>'publish failed!','err'=>'error','data'=>'','pid'=>$pid]);
            }

        }else{
            $_data['prj_status']=2;
            $res = DB::table('project')->insertGetId($_data);
            if($res){
                return response()->json(['msg'=>'update successfully','err'=>'ok','data'=>'','pid'=>$res]);
            }else{
                return response()->json(['msg'=>'update failed!','err'=>'error','data'=>'','pid'=>'']);
            }
        }





    }
    public function contactDetail(Request $req){

    }
    public function settime($id = 0){

        $project = DB::table('project')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();

        //$project = $this->projectDetail($id);
        $this->ckstatus($project,2);
        $project->nav = CreateProgress::url($project->prj_status,2,$id);
        //dd($project);
        $project->selectvalues = Tools::timeZoneShow();


        return view('v1/projects/settime',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project]);

    }
    public function setprjtime(Request $req){
        $pid         = $req->get('pid','0');
        $period     = $req->get('day');
        $period_h     = $req->get('hours');
        $timezone   = $req->get('time-zone');
        $project = $this->projectDetail($pid);
        //print_r($project->prj_process_status);
        $project->prj_period = $period;
        $project->prj_period_h = $period_h;
        $project->prj_timezone = $timezone;
        $project->prj_uptime=time();
        if($project->prj_status<=3){
            $project->prj_status=3;
        }
        if($project->save()){
            return response()->json(['msg'=>'publish successfully','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'publish failed','err'=>'error','data'=>'']);
        }
        /*


        $res = DB::table('project')->where(
            [
                'prj_uid'=>$pid,
                'prj_uid'=>$this->info->user_id
            ])->update(
                [
                    'prj_period'=>$period,
                    'prj_timezone'=>$timezome,
                    'prj_uptime'=>time(),
                    'prj_status'=>3,
                ]
        );

        if($res){
            return response()->json(['msg'=>'publish successfully','err'=>'ok','data'=>'']);
        }else{
            return response()->json(['msg'=>'publish failed','err'=>'error','data'=>'']);
        }
        */





    }
    public function setContact(Request $req){
        $pid         = $req->get('pid','0');
        $project = \App\Project::where('prj_uid',$this->info->user_id)->find($pid);
        if(!$project) exit;


        $country    = $req->get('country','');
        $tel        = $req->get('tel','');
        $ver_code   = $req->get('ver_code','');
        $email      = $req->get('email','');

        if($ver_code && $ver_code=='1234'){
            $project->prj_status=5;
            $project->prj_country = $country;
            $project->prj_tel= $tel;
            $project->prj_uptime= time();
            if($project->save()){
                return response()->json(['msg'=>'update successfully','err'=>'ok','data'=>'']);
            }else{
                return response()->json(['msg'=>'error','err'=>'error','data'=>'']);
            }
            /*

            $res = DB::table('project')->where(
                [
                    'prj_uid'=>$this->info->user_id,
                    'prj_id'=>$pid,
                ]
            )->update(
                [
                    'prj_country'=>$country,
                    'prj_tel'=>$tel,
                    'prj_uptime'=>time(),
                ]
            );
            if($res){
                return response()->json(['msg'=>'update successfully','err'=>'ok','data'=>'']);
            }else{
                return response()->json(['msg'=>'error','err'=>'error','data'=>'']);
            }
            */

        }else{
            return response()->json(['msg'=>'Verification code error!','err'=>'error','data'=>'']);
        }
    }
    public function setTrialView($id = 0){
        $project = DB::table('project')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();
        $this->ckstatus($project,3);
        $project->nav = CreateProgress::url($project->prj_status,3,$id);

        $trial = DB::table('trial_works')->where(['trial_pid'=>$id,'trial_uid'=>$this->info->user_id])->first();
        if($trial){


            $size = '300';
            $_attach = $this->photos($trial->trial_attachment, $size);

            $trial->attach = $_attach;
            $img_show_html='';
            $img_form_val='';
            foreach ($trial->attach AS $k => $v) {
                $v = (object)$v;
                //$img_show_html .= '<div id="file-' . $v->id . '" class="imgouter">cover<i id="p-' . $v->id . '" style="position: absolute;left: 50%;display:none;">0%</i><i class="remove" id="in-' . $v->id . '">delete</i><div class="progress" style="height: 3px;position: relative;bottom: -170px;"><div class="progress-bar" id="prs-' . $v->id . '" aria-valuenow="100" style="width: 200px;"></div><b><span>100%</span></b></div><b></b><img src="' . $v->url . '"></div>';
                $img_show_html .= '<li class="img-items" id="file-' . $v->id . '">
                            <span class="delete-icon" id="d-' . $v->id . '"></span>
                            <img src="' . $v->url . '" alt="">
                        </li>';
                $img_form_val .= '<input type="hidden" id="data-' . $v->id . '" name="imgs" value="' . $v->id . '">';
            }
            $trial->img_show_html=$img_show_html;
            $trial->img_form_val=$img_form_val;
        }





        return view('v1.projects.settrial',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project,'trial'=>$trial]);

    }
    public function setContactView($id = 0){
        $project = DB::table('project')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();
        $this->ckstatus($project,4);
        $project->nav = CreateProgress::url($project->prj_status,4,$id);
        $_email = DB::table('user')->select('user_email')->where(['user_id'=>$this->info->user_id])->first();
        $project->email = $_email->user_email;

        return view('v1.projects.setcontact',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project]);
    }
    public function publishView($id=0){
        $project = DB::table('project')->where(['prj_uid'=>$this->info->user_id,'prj_id'=>$id])->first();
        $this->ckstatus($project,5);
        $project->nav = CreateProgress::url($project->prj_status,5,$id);
        $prj_photos = [];
        $photo_ids = explode(',',$project->prj_photos);
        $photos = DB::table('oss_item')->whereIN('oss_item_id',$photo_ids)->get();
        foreach($photos AS $K=>$v){
            $prj_photos[]=ApiConf::IMG_URI.'/'.$v->oss_path;
        }
        $project->prj_photos =$prj_photos;

        $industry=DB::table('category')->where('cate_id',$project->prj_industry)->first();
        $cate=DB::table('category')->where('cate_id',$project->prj_cate)->first();
        $format=DB::table('category')->select('cate_name')->whereIn('cate_id',explode(",",$project->prj_format))->get();
        $_format = '';
        foreach($format AS $k=>$v){
            $_format.=$v->cate_name." ";
        }

        $project->prj_industry =$industry->cate_name;
        $project->prj_cate =$cate->cate_name;
        $project->prj_format =$_format;
        $_email = DB::table('user')->select('user_email')->where(['user_id'=>$this->info->user_id])->first();

        $project->email =$_email->user_email;
        $accs = ['Default','High','Low'];
        $project->prj_acc = $accs[$project->prj_acc-1];
        $project->email = $_email->user_email;
        return view('v1.projects.publish',['user'=>$this->info,'user_info'=>$this->info,'data'=>$project]);
    }
    public function setTrial(Request $req){


        $id                 = $req->get('pid',0);
        if($id==0) exit;

        $trial_pics   = $req->get('imgs',[]);
        $trial_desc         = $req->get('desc',NULL);
        $draft            = $req->get('draft',0);
        if(is_array($trial_pics) && count($trial_pics)>1){
        $photos_ids=[];
        foreach($trial_pics AS $k=>$v){
            if(strlen($v) > 11 && !is_numeric($v)) {
                $_v = explode('@',$v);
                $insert_id = DB::table('oss_item')->insertGetId(
                    [
                        'oss_key' => 'elements',
                        'oss_path' => $_v[0],
                        'oss_item_uid' => $this->info->user_id,
                        'width' => $_v[1],
                        'height' => $_v[2],
                    ]
                );

                $photos_ids[]=$insert_id;
            }else{
                $photos_ids[]=$v;

            }


        }
        $photos_ids = implode(",",$photos_ids);
        }else{
            $photos_ids='';
        }

        $ck = DB::table('trial_works')->where(['trial_pid'=>$id,'trial_uid'=>$this->info->user_id])->first();
        $_data = [
            'trial_pid'=>$id,
            'trial_uid'=>$this->info->user_id,
            'trial_attachment'=>$photos_ids,
            'trial_desc'=>$trial_desc,
            'trial_draft'=>$draft,
            'trial_pubtime'=>time(),
        ];
        if($ck){
            $result = DB::table('trial_works')->where(['trial_id'=>$ck->trial_id])->update($_data);
        }else{
            $result = DB::table('trial_works')->insertGetId(
                $_data
                );
        }
            if($result){
                $res = DB::table('project')->where(
                    [
                        'prj_uid'=>$id,
                        'prj_uid'=>$this->info->user_id
                    ])->update(
                    [
                        'prj_uptime'=>time(),
                        'prj_status'=>4,
                    ]
                );
                if($res){
                    return response()->json(['msg'=>'publish successfully','err'=>'ok','data'=>'','pid'=>$id]);
                }else{
                    return response()->json(['msg'=>'publish failed','err'=>'error','data'=>'']);
                }
            }


    }
    public function publish(Request $req){
        $pid = $req->get('pid',0);
        if($pid==0){
            exit;
        }
        $tel = $req->get('tel',NULL);
        $project = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        $status2= $project->prj_process_status>=1?$project->prj_process_status:1;
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_status'=>5,'prj_process_status'=>$status2,'prj_uptime'=>time(),'prj_tel'=>$tel]);
        if($res){
            return response()->json(['msg'=>'publish successfully!','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'publish failed!','err'=>'error','data'=>'']);
        }

    }
    public function publishSave(Request $req){
        $pid = $req->get('pid',0);
        if($pid==0){
            exit;
        }
        $project = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        $status2= $project->prj_process_status>=1?$project->prj_process_status:1;
        $res = DB::table('project')->where(['prj_id'=>$pid])->update(['prj_status'=>5,'prj_process_status'=>$status2,'prj_uptime'=>time()]);
        if($res){
            return response()->json(['msg'=>'publish successfully!','err'=>'ok','data'=>'','pid'=>$pid]);
        }else{
            return response()->json(['msg'=>'publish failed!','err'=>'error','data'=>'']);
        }

    }
    public function bidlist(Request $req){

    }
    public function cates(){
        $cates=DB::table('category')->where('cate_pid',0)->get();

        foreach ($cates as $k => $v) {
            $sub_data=DB::table('category')->where(['cate_pid'=>$v->cate_id,'cate_active'=>0])->orderBy('cate_order','ASC')->get();
            $cates[$k]->sub=$sub_data;
        }

        $cateDatas=(object)[];
        foreach($cates AS $k=>$v){
            $attr=$v->cate_name;
            if(isset($_COOKIE['lang']) && ($attr=='category' || $attr=='Geometry')){
                foreach($v->sub AS $sk=>$sv){

                    $v->sub[$sk]->cate_name=$sv->cate_name_cn;

                }
            }
            $cateDatas->$attr=$v->sub;
        }
        return response()->json(['err'=>'ok','code'=>200,'msg'=>'','data'=>$cateDatas]);
    }
    public function projectDetail($id){
        return $project = \App\Project::where('prj_id',$id)->where('prj_uid',$this->info->user_id)->firstOrFail();
    }
    public function modeltest($id = 0){
        $projects = \App\Project::where('prj_id',$id)->where('prj_uid',$this->info->user_id)->firstOrFail();




    }


}
