<?php

namespace App\Http\Controllers;

use App\libs\ApiConf;
use App\Model\Ossitem;
use App\Model\Project;
use App\Model\Work;
use Illuminate\Http\Request;

use App\Http\Requests;

class WorkController extends Controller
{

    public function detail($id=0){
        if($id==0){
            abort(404);
            return;
        }
        $work = Work::find($id);
        if(!$work){
            abort(404);
            return;
        }
        $cover = $this->ossPath($work->work_cover,-1);
        $_work=(object)[];
        $_work->id =$work->work_id;
        $_work->cover =$cover;
        $_work->title =$work->work_title;
        $_work->desc =$work->work_description;
        $_work->host = env("CLIENT_BASE");
        $_work->host1 = env("APP_URL");
        return view('work.detail',['data'=>$_work]);

    }
    public function projectDetail($id=0){
        if($id==0){
            abort(404);
            return;
        }
        $project = Project::find($id);
        if(!$project){
            abort(404);
            return;
        }
        $cover = $this->ossPath($project->prj_photos,-1);
        $prj=(object)[];
        $prj->id =$project->prj_id;
        $prj->cover =$cover;
        $prj->title =$project->prj_name;
        $prj->desc ='There is a new project ,have a look!';
        $prj->host = env("CLIENT_BASE");
        $prj->host1 = env("APP_URL");
        return view('project.detail',['data'=>$prj]);

    }

    public function ossItem($id){
        $item = Ossitem::find($id);
        return ApiConf::IMG_URI.$item->oss_path;
    }

    public function socket(){
        return view('test.web_socket');
    }
}
