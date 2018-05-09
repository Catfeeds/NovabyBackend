<?php

namespace App\Http\Controllers\Api;

use App\libs\StaticConf;
use App\Model\Cate;
use App\Model\Country;
use App\Model\Field;
use App\Model\Following;
use App\Model\PrjApply;
use App\Model\Tag;
use App\Model\Project;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Model\User;
use App\Model\Work;
use DB;

class SearchApiController extends BaseApiController
{
    /**
     * 推荐用户和模型和项目
     * @return array
     */
    public function recommendUserModelfunc(){
        $type = Input::get('type','model');
        if($type=='model'){
            $size = Input::get('size',4);
            if($this->_user){
                $my_followings = Following::where('from_uid',$this->_user->user_id)->
                where('followed',1)->
                select('to_uid')->get();
                $_fusers =[];
                foreach($my_followings AS $k=>$v){
                    $_fusers[]=$v->to_uid;
                }
                $_fusers[]=$this->_user->user_id;
                $user = DB::table('user')->select('user_id')->whereNotIn('user_id',$_fusers)->where('user_works','>','0')->orderBy(DB::raw('RAND()'))->limit($size)->get();
                $_user = [];
                foreach($user AS $k=>$v){
                    $_user[]=$v->user_id;
                }
            }else{
                $user = DB::table('user')->select('user_id')->where('user_works','>','0')->orderBy(DB::raw('RAND()'))->limit($size)->get();
                $_user = [];
                foreach($user AS $k=>$v){
                    $_user[]=$v->user_id;
                }
            }
            $r_works = [];
            foreach($_user AS $k=>$v){
                $tmp_work = $this->findUserFirstWorks($v);
                if($tmp_work){
                    $r_works[] =   $tmp_work;
                }
            }
            foreach($r_works AS $k=>$v){
                $r_works[$k]->work_cover = $this->getOssPath($v->work_cover,'500');
                $r_works[$k]->author = $this->getUserInfo($v->work_uid);
                $follow = 0;
                if($this->_user && $this->_user->user_id){
                    $follow = DB::table('following')->where(['to_uid'=>$v->work_uid,'from_uid'=>$this->_user->user_id])->count();
                }
                $r_works[$k]->isfollow = $follow;
            }
            if(count($r_works)>0){
                return ['works'=>$r_works];
            }else{
                return $this->jsonErr("No More Data");
            }
        }elseif($type=='user'){
            $size = Input::get('size',5);
            if($this->_user){
                $my_followings = Following::where('from_uid',$this->_user->user_id)->
                where('followed',1)->
                select('to_uid')->get();
                $fusers =[];
                foreach($my_followings AS $k=>$v){
                    $fusers[]=$v->to_uid;
                }
                $fusers[] = $this->_user->user_id;
                $users = User::select('user_id','user_name','user_lastname','user_country','user_job','user_icon')
                    ->whereIn('user_type',[1,2,3,4])
                    ->whereNotIn('user_id',$fusers)
                    ->orderBy(DB::raw('RAND()'))
                    ->limit($size)
                    ->get();
            }else{
                $users = User::select('user_id','user_name','user_lastname','user_country','user_job','user_icon')
                    ->whereIn('user_type',[2,3])
                    ->orderBy(DB::raw('RAND()'))
                    ->limit($size)
                    ->get();
            }
            foreach($users AS $k=>$v){
                $users[$k] = $this->getUserInfo($v->user_id);
                unset($users[$k]->works,$users[$k]->me);
            }
            if(count($users)>0){
                return ['users'=>$users];
            }else{
                return $this->jsonErr("No More Data");
            }
        }else{
            $size = Input::get('size',3);
            $my_apply_prjs =[];
            $where = 'prj_progress=1 AND prj_permission =1 ';
            if($this->_user && $this->_user->user_id){
                $where .=' AND prj_uid!='.$this->_user->user_id;
                $_prjs = PrjApply::where('user_id',$this->_user->user_id)->select('prj_id')->get();
                foreach($_prjs AS $v){
                    $my_apply_prjs[]=$v->prj_id;
                }
            }
            $projects = Project::select('prj_id','prj_name','prj_photos','created_at','prj_time_day')
                ->whereRAW($where)
                ->whereNotIn('prj_id',$my_apply_prjs)
                ->orderBy(DB::raw('RAND()'))
                ->get();
            foreach($projects AS $item){
                $_covers = explode(',',$item->prj_photos);
                $item->prj_cover = $this->getOssPath($_covers[0],'300');
                unset($item->prj_photos,$item->prj_time_day,$item->created_at);
            }
            $projects = $projects->take($size);
            $project = [];
            foreach ($projects as $item)
            {
                $project[] = $item;
            }
            if(count($project)>0){
                return ['projects'=>$project];
            }else{
                return $this->jsonErr("No More Data");
            }
        }
    }
    /**
     * 获取用户信息
     * @param $uid
     * @return mixed
     */
    private function getUserInfo($uid){
        $user = User::where('user_id',$uid)->select('user_id','user_name','user_lastname','user_type','user_icon','company_name')->first();
        $user->user_avatar = $this->getAvatar($user->user_icon,'100');
        $user->user_name = $user->user_name.' '.$user->user_lastname;
        if($user->user_type==4)
        {
            $user->user_name = $user->company_name;
        }
        unset($user->user_lastname,$user->company_name,$user->user_icon);
        return $user;

    }
    /**
     * 获取用户的第一个作品
     * @param $uid
     * @return mixed
     */
    private function findUserFirstWorks($uid){
        $work = Work::select('work_id','work_title','work_cover','work_uid')
            ->where('works.work_uid',$uid)
            ->where('work_status',1)
            ->where('work_privacy',0)
            ->where('work_del',0)
            ->first();
        return $work;
    }

    //搜索
    /**
     * 搜索
     * @return mixed
     */
    public function getSearch()
    {
        $page = Input::get('page',1);
        $page_size = Input::get('pagesize',10);
        $act = Input::get('act','model');
        $key = Input::get('keywords','');
        if($act!='model' && $act!='user'){
            return $this->jsonErr('error');
        }
        if($act=='model'){
            $category = Input::get('category')?Input::get('category'):0;
            $model_3D = Input::get('model_3D')?Input::get('model_3D'):0;
            $video = Input::get('video')?Input::get('video'):0;
            $tags = Input::get('tags')?Input::get('tags'):[];
            $format = Input::get('format')?Input::get('format'):0;
            $lastUploaded = Input::get('lastUploaded')?Input::get('lastUploaded'):0;
            $download = Input::get('download')?Input::get('download'):0;
            $data = $this->getModel($category,$tags,$format,$lastUploaded,$download,$model_3D,$video,$key,$page,$page_size);
        }elseif($act=='user'){
            $skill = Input::get('skill')?Input::get('skill'):[];
            $rate = Input::get('rate')?Input::get('rate'):0;
            $english = Input::get('english')?Input::get('english'):0;
            $amount = Input::get('amount')?Input::get('amount'):0;
            $country = Input::get('country')?Input::get('country'):0;
            $success = Input::get('success')?Input::get('success'):0;
            $type = Input::get('type')?Input::get('type'):0;
            $active = Input::get('active')?Input::get('active'):0;
            $data = $this->getUser($skill,$rate,$english,$amount,$country,$success,$type,$active,$key,$page,$page_size);
        }else{
            $data['data']=[];
        }
        if(count($data['data'])>0){
            return $this->jsonOk('ok',$data);
        }else {
            return $this->jsonErr('no more data', $data);
        }
    }
    /**
     * 搜索用户
     * @param array $skill
     * @param int $rate
     * @param int $english
     * @param int $amount
     * @param int $country
     * @param int $success
     * @param int $type
     * @param int $active
     * @param string $key
     * @return mixed
     */
    public function getUser($skill=[],$rate=0,$english=0,$amount=0,$country=0,$success=0,$type=0,$active=0,$key='',$page=1,$page_size=10)
    {
        $user = User::select('user_id','user_name','user_lastname','user_country','user_city','user_icon','user_type','hourly_rate','english_level','company_name',
            'user_fileds','user_page_id','user_last_login_time','user_work_exp',
            'user_description','year_founded','company_type')
            ->where('user_name','like','%'.$key.'%')
            ->orWhere('user_lastname','like','%'.$key.'%')
            ->orWhere('company_name','like','%'.$key.'%')
            ->where('user_type','>',0)
            ->with(['info','build'=>function($query){$query->where('prj_progress',3.5);}])
            ->get();
        if($skill){
            $user = $user->filter(function ($item)use($skill){
                $skills = explode(",",$item->user_fileds);
                return array_intersect($skill,$skills);
            });
        }
        if($rate){
            $user = $user->filter(function ($item)use($rate){
                switch ($rate)
                {
                    case 1:
                        return $item->hourly_rate==$this->hourly_rate[1];
                        break;
                    case 2:
                        return $item->hourly_rate==$this->hourly_rate[2];
                        break;
                    case 3:
                        return $item->hourly_rate==$this->hourly_rate[3];
                        break;
                    default:
                        return $item->hourly_rate==$this->hourly_rate[4];
                        break;
                }
            });
        }
        if($english){
            $user = $user->filter(function ($item)use($english){
                return $item->english_level == $english;
            });
        }
        if($amount){
            $user = $user->filter(function ($item)use($amount){
                $user_amount = ceil($item->info->project_amount);
                switch ($amount)
                {
                    case 1:
                        return $user_amount>=1 && $user_amount<100;
                        break;
                    case 2:
                        return $user_amount>=100 && $user_amount<1000;
                        break;
                    case 3:
                        return $user_amount>=1000 && $user_amount<10000;
                        break;
                    case 4:
                        return $user_amount>=10000;
                        break;
                    default:
                        return $user_amount==0;
                        break;
                }
            });
        }
        if($country){
            $user = $user->filter(function ($item)use($country){
                return $item->user_country == $country;
            });
        }
        if($success){
            $user = $user->filter(function ($item)use($success){
                $prj_success = $item->info->project_succes;
                switch ($success)
                {
                    case 1:
                        return $prj_success>=80;
                        break;
                    default:
                        return $prj_success>=90;
                        break;
                }
            });
        }
        if($type){
            $user = $user->filter(function ($item)use($type){
                return $item->user_type == $type;
            });
        }
        if($active) {
            $user = $user->filter(function ($item) use ($active) {
                switch ($active) {
                    case 2:
                        $time = time() - 30 * 3600 * 24;
                        return $item->user_last_login_time >= $time;
                        break;
                    case 3:
                        $time = time() - 60 * 3600 * 30;
                        return $item->user_last_login_time >= $time;
                        break;
                    default:
                        $time = time() - 14 * 3600 * 24;
                        return $item->user_last_login_time >= $time;
                        break;
                }
            });
        }
        $count = $user->count();
        $user = $user->sortByDesc(function ($item){
            return [count($item->build),$item->info->project_success,$item->info->user_type];
        })->values()->forpage($page,$page_size);
        $users = [];
        foreach ($user as $item)
        {
            $item->user_country = $item->user_country?$item->country['name']:'';
            $item->user_city = $item->user_city?$item->city['name']:'';
            $item->name = $item->company_name?$item->company_name:$item->user_name." ".$item->user_lastname;
            $fields = explode(",",$item->user_fileds);
            switch ($this->lang)
            {
                case 'zh':
                    if(count($fields)){
                        $item->field = Field::whereIn('id',$fields)->pluck('name_cn');
                    }else{
                        $item->field =[];
                    }
                    $item->english_level = $item->english_level?StaticConf::$english_level_zh[$item->english_level]:'';
                    $item->hourly_rate = $item->hourly_rate?StaticConf::$hourly_rate_zh[$item->hourly_rate]:'';
                    $item->user_work_exp = $item->user_work_exp?StaticConf::$work_exp_zh[$item->user_work_exp]:'';
                    $item->company_type = $item->company_type?StaticConf::$company_type_zh[$item->company_type]:'';
                    break;

                default:
                    if(count($fields)){
                        $item->field = Field::whereIn('id',$fields)->pluck('name');
                    }else{
                        $item->field =[];
                    }
                    $item->english_level = $item->english_level?StaticConf::$english_level[$item->english_level]:'';
                    $item->hourly_rate = $item->hourly_rate?StaticConf::$hourly_rate[$item->hourly_rate]:'';
                    $item->user_work_exp = $item->user_work_exp?StaticConf::$work_exp[$item->user_work_exp]:'';
                    $item->company_type = $item->company_type?StaticConf::$company_type[$item->company_type]:'';
                    break;
            }
            $build_count = $item->build->count();
            if($item->user_type !=4 ) {
                $home_page = $item->user_page_id?env('CLIENT_BASE').'homepage/'.$item->user_page_id:'';
            }else{
                $home_page = $item->user_page_id?$item->user_page_id:'';
            }
            $item->user_page_id = $home_page;
            $item->user_description = $item->user_description?$item->user_description:'';
            $item->project_success = $item->info?$item->info->project_success:'';
            $item->project_time = $item->info?$item->info->project_time:'';
            $item->project_quality = $item->info?$item->info->project_quality:'';
            $item->project_commucation = $item->info?$item->info->project_commucation:'';
            $item->projects = $build_count>0?$build_count:'';
            $item->avatar = $this->getAvatar($item->user_icon,'100');
            $item->isfollow = 0;
            $item->year_founded = $item->year_founded?$item->year_founded:'';
            $item->project_amount = $item->project_amount?$this->transAmount($item->project_amount):'';
            if($this->_user){
                $item->isfollow = Following::where(['from_uid'=>$this->_user->user_id,'to_uid'=>$item->user_id,'followed'=>1])->count();
            }
            unset($item->build,$item->info,$item->user_icon,$item->country,$item->city,$item->user_fileds,$item->company_name,$item->user_name,$item->user_lastname);
            $users[] = $item;
        }
        $data['data'] = $users;
        $data['count'] = $count;
        $data['page_count'] = ceil($count/10);
        return $data;
    }

    /**
     * 搜索模型
     * @param int $category
     * @param array $tag
     * @param int $format
     * @param int $last_uploaded
     * @param int $download
     * @param int $model_3D
     * @param int $video
     * @param string $key
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getModel($category=0,$tag=[],$format=0,$last_uploaded=0,$download=0,$model_3D=0,$video=0,$key='',$page=1,$page_size=10)
    {
        $work = Work::select('work_title','work_id','work_cate','work_cover','work_tags','work_license','work_permit','created_at','work_model','work_video','work_detail','work_uid')
            ->where('work_title','like','%'.$key.'%')
            ->where('work_status',1)
            ->where('work_del',0)
            ->with('detail','user','cate','category')
            ->orderBy('work_id','DESC')
            ->get();
        if($category){
            $work = $work->filter(function ($item)use($category){
                return $item->work_cate = $category;
            });
        }
        if($tag){
            $work = $work->filter(function ($item)use($tag){
                $tags = explode(",",$item->work_tags);
                return array_intersect($tag,$tags);
            });
        }
        if($format){
            $work = $work->filter(function ($item) use ($format) {
                if($item->detail)
                {
                    switch ($format) {
                        case 1:
                            return $item->detail->w_old_format == 'max';
                            break;
                        case 2:
                            return $item->detail->w_old_format == 'ma' || $item->detail->w_old_format == 'mb';
                            break;
                        case 3:
                            return $item->detail->w_old_format == 'obj';
                            break;
                        case 4:
                            return $item->detail->w_old_format == '3ds';
                            break;
                        case 5:
                            return $item->detail->w_old_format == 'c4d';
                            break;
                        case 6:
                            return $item->detail->w_old_format == 'fbx';
                            break;
                        default:
                            return !in_array($item->detail->w_old_format, ['max', 'ma', 'mb', 'obj', 'c4d', 'fbx', '3ds']);
                            break;
                    }
                }else{
                    return null;
                }

            });
        };
        if($last_uploaded){
            $work = $work->filter(function ($item)use($last_uploaded){
                $time = Carbon::parse($item->created_at)->timestamp;
                switch ($last_uploaded)
                {
                    case 1:
                        return $time >= time()-7*3600*24;
                        break;
                    case 2:
                        return $time >= time()-30*3600*24;
                        break;
                    default:
                        return $time >= time()-90*3600*24;
                        break;
                }
            });
        }
        if($download){
            $work = $work->filter(function ($item)use($download){
                switch ($download)
                {
                    case 1:
                        return $item->work_permit == 1;
                        break;
                    default:
                        return $item->work_permit == 0;
                        break;
                }
            });
        }
        if($model_3D){
            $work = $work->filter(function ($item)use($model_3D){
                switch ($model_3D)
                {
                    case 1:
                        return $item->work_model != 0;
                        break;
                    default:
                        return $item->work_model == 0;
                        break;
                }
            });
        }
        if($video){
            $work = $work->filter(function ($item)use($video){
                switch ($video)
                {
                    case 1:
                        return $item->work_video != null;
                        break;
                    default:
                        return $item->work_video == null;
                        break;
                }
            });
        }
        $count = $work->count();
        $work = $work->forpage($page,$page_size);
        $works = [];
        foreach ($work as $item)
        {
            $item->cover = $this->getOssPath($item->work_cover,'300');
            $item->work_cate = $this->getModelInfo($item,'category');
            $author['user_type'] = $item->user->user_type;
            $author['name'] = $item->user->company_name?$item->user->company_name:$item->user->user_name." ".$item->user->user_lastname;
            $author['avatar'] = $this->getAvatar($item->user->user_icon,'100');
            $item->author = $author;
            $item->work_model = $item->work_model?1:0;
            if($item->work_tags){
                $tags = [];
                $w_tags = explode(",",$item->work_tags);
                foreach ($w_tags as $k => $v)
                {
                    if(is_numeric($v)){
                        $tag = Tag::find($v)?Tag::find($v)->tag_name:$v;
                    }else{
                        $tag = $v;
                    }
                    $tags[] = $tag;
                }
                $item->work_tags = implode(",",$tags);
            }
            $item->work_license = $item->work_license?$this->getLicense($item->work_license):'';
            unset($item->detail,$item->user,$item->cate,$item->work_uid,$item->work_cover,$item->work_detail);
            $works[] = $item;
        }
        $data['data'] = $works;
        $data['count'] = $count;
        $data['page_count'] = ceil($count/10);
        return $data;
    }
    /**
     * 返回查询user的筛选条件
     * @return mixed
     */
    public function searchUserData()
    {
        $lang = $this->lang;
        switch ($lang){
            case 'zh':
                $skill = Field::select('id','name_cn')->get()->map(function ($item){
                        $item->name = $item->name_cn;
                        unset($item->name_cn);
                        return $item;
                    });
                $country = Country::select('id','name')->where('pid',0)->get()->all();
                $hourly_rate = StaticConf::$hourly_rate_zh;
                $english_level = StaticConf::$english_level_zh;
                $earned_amount = StaticConf::$earned_amount_zh;
                $project_success = StaticConf::$project_success_zh;
                $modeler_type = StaticConf::$modeler_type_zh;
                $last_activity = StaticConf::$last_activity_zh;
                break;
            default:
                $skill = Field::select('id','name')->get();
                $country = Country::select('id','name')->where('pid',0)->get()->all();
                $hourly_rate = StaticConf::$hourly_rate;
                $english_level = StaticConf::$english_level;
                $earned_amount = StaticConf::$earned_amount;
                $project_success = StaticConf::$project_success;
                $modeler_type = StaticConf::$modeler_type;
                $last_activity = StaticConf::$last_activity;
                break;
        }
        $data = [
            'skill' => $skill,
            'hourly_rate' => $hourly_rate,
            'english_level' => $english_level,
            'earned_amount' => $earned_amount,
            'country' => $country,
            'project_success' => $project_success,
            'modeler_type' => $modeler_type,
            'last_activity' => $last_activity
        ];
        return $this->jsonOk('ok',['filter' => $data]);
    }

    /**
     * 返回查询model的筛选条件
     * @return mixed
     */
    public function searchModelData()
    {
        $lang = $this->lang;
        switch ($lang){
            case 'zh':
                 $category =  Cate::select('cate_id','cate_name_cn')
                    ->where('cate_pid',1)
                    ->where('cate_active',0)
                    ->orderBy('cate_order','ASC')
                    ->get()->map(function ($item){
                        $item->cate_name = $item->cate_name_cn;
                         unset($item->cate_name_cn);
                         return $item;
                    });
                $model = StaticConf::$model_3D_zh;
                $video = StaticConf::$video_zh;
                $download_permit = StaticConf::$download_permit_zh;
                $last_uploaded = StaticConf::$last_uploaded_zh;
                $format = StaticConf::$format_zh;
                break;
            default:
                $category =  $category =  Cate::select('cate_id','cate_name')
                    ->where('cate_pid',1)
                    ->where('cate_active',0)
                    ->orderBy('cate_order','ASC')
                    ->get();
                $model = StaticConf::$model_3D;
                $video = StaticConf::$video;
                $download_permit = StaticConf::$download_permit;
                $last_uploaded = StaticConf::$last_uploaded;
                $format = StaticConf::$format;
                break;
        }
        $tags = Tag::select('tag_id','tag_name')->where('pid',43)->orderBy('tag_name','ASC')->get()->all();;
        $data = [
            'category' => $category,
            'tags' => $tags,
            'model_3D' => $model,
            'video' => $video,
            'download_permit' => $download_permit,
            'last_uploaded' => $last_uploaded,
            'format' => $format
        ];
        return $this->jsonOk('ok',['filter' => $data]);
    }
}
