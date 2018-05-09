<?php

namespace App\Http\Controllers\NewAdmin;

use App\libs\AliApi;
use App\libs\OSSManager;
use App\libs\QiNiuManager;
use App\libs\Tools;
use App\Model\Banner;
use App\Model\BuildPay;
use App\Model\Cate;
use App\Model\Config;
use App\Model\Country;
use App\Model\Feedback;
use App\Model\MarkResponse;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\UserPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function  index()
    {
        return view('newadmin.index');
    }

    /**
     * banner列表
     * @return
     */
    public function banner()
    {
//        $datas = explode("},",file_get_contents('id.txt'));
//        dd($datas);
//        foreach ($datas as $k=>$v)
//        {
//           echo ($k+2)."\n";
//        }
//        dd();
//        $country = null;
//        foreach ($datas as $data) {
//            $country .=$data;
//        }
//        $citys = null;
//        foreach ($datas as $item)
//        {
//            $citys[] = explode("\t",$item)[0];
//        }
//        $arr = null;
//        foreach ($citys as $key=> $value)
//        {
//            $arr .= '("'.$value.'",37750),';
//        }
//        $sql = 'insert into dict_country_cn(id,name,pid)value'.$country;
//        DB::statement($sql);
//        dd('successful');
//        $countrys = Country::where('pid',0)->get();
//        $coun = null;
//        $cit = null;
//        foreach ($countrys as $country)
//        {
//            $id = DB::table('dict_country1')->insertGetId(
//                ['name' => $country->name, 'pid' => 0]
//            );
//            while($id){
//                $citys = Country::where('pid',$country->id)->get();
//                foreach ($citys as $city)
//                {
//                    $cit .= '("'.$city->name.'",'.$id.'),';
//                }
//                $sql2 = 'insert into dict_country1(name,pid)value'.rtrim($cit,',');
//                DB::statement($sql2);
//            }
//        }
//        dd('successful');
        $banners = Banner::paginate(5);
        $banner = $banners->map(function($item){
            $item->photo = $this->ossPath($item->photo);
            $item->words = nl2br($item->words);
            return $item;
        });
        $title = '';
        $subtitle = '';
        $res = Config::whereIn('name',['title','subtitle'])->get();

        if(count($res)>0){
            $title=($res[0]->value);
            $subtitle=$res[1]->value;
        }

        return view('newadmin.banner.index')->with(['banners'=> $banners,'banner'=>$banner,'title'=>$title,'subtitle'=>$subtitle]);
    }
    /**
     * 项目支付列表
     */
    public function pay()
    {
        $paies = BuildPay::orderBy('id','desc')->paginate(15);
        $paie = $paies->map(function ($item){
            $project = Project::where('prj_id',$item->pid)->first();
            $apply = PrjApply::where(['prj_id'=>$item->pid,'user_id'=>$project->prj_modeler])->first();
            $item->price = $project->prj_models_tot*$apply->apply_price;
            return $item;
        });
        return view('newadmin.pay.index')->with(['paies'=>$paies,'paie'=>$paie]);
    }

    /**
     * 服务支付列表
     * @return
     */
    public function planPay()
    {
        $paies = UserPlan::orderBy('id','desc')->paginate(15);
        return view('newadmin.pay.plan')->with('paies',$paies);
    }
    /**
     * 返回分类列表
     * @return
     */
    public function category()
    {
        $categories =Cate::select('cate_id','cate_name','cate_order','cate_name_cn')->where('cate_pid',1)->where('cate_active',0)->where('cate_isgame','=',null)->orderBy('cate_order','ASC')->paginate('15');
        return view('newadmin.category.index')->with('categories',$categories);
    }

    /**
     * 创建新分类
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function createCategory(Request $request)
    {
        $this->validate($request,[
            'name' => 'required',
        ]);
        $cate = new Cate();
        $cate->cate_name = $request->get('name');
        $cate->cate_name_cn = $request->get('name_cn');
        $cate->cate_active = 1;
        $cate->cate_order = 11;
        $cate->cate_pid = 1;
        $cate->save();
        return redirect('admin/category/index');
    }
    /**
     * 分类保存
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $category = Cate::find($request->get('id'));
        if($request->get('cate_name')) {
            $this->validate($request,[
                'cate_name' => 'required',
            ]);
            $category->cate_name = $request->get('cate_name');
        }
        if($request->get('cate_name_cn')) {
            $this->validate($request,[
                'cate_name_cn' => 'required',
            ]);
            $category->cate_name_cn = $request->get('cate_name_cn');
        }
        if($request->get('cate_order')){
            $this->validate($request,[
                'cate_order' => 'required|integer',
            ]);
            $category->cate_order = $request->get('cate_order');
        }
        if($request->get('cate_active') == 0 || $request->get('cate_active')){
            $category->cate_active = $request->get('cate_active');
        }
        $category->save();
        return ['code'=>1,'msg'=>'successful'];
    }

    /**
     * 留言列表
     * @return
     */
    public function feedback()
    {
        $feedbacks = Feedback::orderBy('feed_id','DESC')->paginate(15);
        return view('newadmin.feedback.index')->with('feedbacks',$feedbacks);
    }

    /**
     * 留言详情
     * @param $id
     * @return
     */
    public function feedDetail($id)
    {
        $feedback = Feedback::find($id);
        return view('newadmin.feedback.detail')->with('feedback',$feedback);
    }
    /**
     * 创建banner
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('newadmin.banner.create');
    }

    /**
     * 保存banner
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function save(Request $request)
    {
        $this->validate($request,[
            'model_id' => 'required|integer',
            'file' => 'required|mimes:jpeg,bmp,png,jpg',
            //'content' => 'required'
        ]);
        $file = $request->file('file');
        $realPath = $file->getRealPath();
        $mime = $file->getClientOriginalExtension();
        $fileSize=$file->getSize();
        $filename = uniqid().'.'.$mime;
        Storage::disk('banner')->put($filename,file_get_contents($realPath));
        $ossmgr = new OSSManager();
        $oss_base_path = date('YmdHis/');
        $target_id    = Tools::guid();
        $oss_file   = $target_id.'.'.$mime;
        $oss_zip_path =$oss_base_path.$oss_file;
        $file_path = Storage::disk('banner')->getAdapter()->getPathPrefix().$filename;
        $result = $ossmgr->upload($oss_zip_path,'elements',$file_path);
        unlink($file_path);
        $photo_id = DB::table('oss_item')->insertGetId([
            'oss_key'=>'elements',
            'oss_path'=>$oss_zip_path,
            'oss_item_uid'=>'0',
            'size'=>$fileSize
        ]);
        $banner = new Banner();
        $banner->model_id = $request->get('model_id');
        $banner->photo = $photo_id;
        $banner->words = $request->get('content');
        $banner->save();
        return redirect('admin/banner/index');

    }

    public function savewords(Request $req){

        $this->validate($req,[
            'title' => 'required',
            'subtitle' => 'required',
            //'content' => 'required'
        ]);
        $title = $req->get('title','');
        $subtitle = $req->get('subtitle','');
        $conf1 = Config::where('name','title')->first();
        $conf2 = Config::where('name','subtitle')->first();
        if($conf1){
            $conf1->value = $title;
            $conf1->save();

        }else{
            $c1 = new Config();
            $c1->name = 'title';
            $c1->value = $title;
            $c1->save();
        }
        if($conf2){
            $conf2->value = $subtitle;
            $conf2->save();
        }else{
            $c2 = new Config();
            $c2->name = 'subtitle';
            $c2->value = $subtitle;
            $c2->save();
        }
        return redirect('admin/banner/index');
    }

    /**
     * 删除
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        Banner::destroy($id);
        return redirect('admin/banner/index');
    }


}
