<?php
namespace App\Http\Controllers\Api;

use App\Model\Project;
use App\Model\Work;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class ShareController extends BaseApiController
{
    private $site;
    private $project_site;
    public function __construct(Request $req)
    {
        parent::__construct($req);
        $this->site = env('APP_URL').'/model/detail/';
        $this->project_site = env('APP_URL').'/project/detail/';
    }

    /**
     * 项目和模型分享
     * @return mixed
     */
    public function share(){
        $id = Input::get('id');
        $type =  Input::get('type',1);
        if($type==1){
            $work = Work::find($id);
            if(!$work){
                return $this->jsonErr("no work found");
            }
            $imageurl=$this->getOssPath($work->work_cover,'-1');
            $data=[
                'facebook'=>'http://www.facebook.com/share.php?u='.$this->site.$id,
                'twitter'=>'https://twitter.com/intent/tweet?text='.$work->work_title.'&url='.$this->site.$work->work_id.'&via=novabycompany',
                'linkedin'=>'http://www.linkedin.com/sharing/share-offsite?mini=true&ro=true&title='.$work->work_title.'&url='.$this->site.$work->work_id.'&summary='.$work->work_description,
                'pinterest'=>'https://www.pinterest.com/pin/create/button/?url='.$this->site.$work->work_id.'&description='.$work->work_description.'&media='.$imageurl,
            ];
            return $this->jsonOk('ok',['share_urls'=>$data]);
        }else{
            $project = Project::find($id);
            if(!$project){
                return $this->jsonErr("no project found");
            }
            $title = $project->prj_name;
            $description = 'new project';
            $media = $this->getOssPath($project->prj_photos,'-1');
            $data=[
                'facebook'=>'http://www.facebook.com/share.php?u='.$this->project_site.$id,
                'twitter'=>'https://twitter.com/intent/tweet?text='.$title.'&url='.$this->project_site.$id.'&via=novabycompany',
                'linkedin'=>'http://www.linkedin.com/sharing/share-offsite?mini=true&ro=true&title='.$title.'&url='.$this->project_site.$id.'&summary='.$description,
                'pinterest'=>'https://www.pinterest.com/pin/create/button/?url='.$this->project_site.$id.'&description='.$description.'&media='.$media,
            ];
            return $this->jsonOk('ok',['share_urls'=>$data]);
        }
    }
    public function preshare(){
        $id = Input::get('id',0);
        $work = Work::find($id);
        if(!$work){
            return $this->jsonErr("no work found");
        }
        $work->work_shares=$work->work_shares+1;
        if($work->save()){
            return $this->jsonOk('ok',[]);
        }
    }

    public function modelerShare()
    {
        $url = 'https://www.novaby.com';
        $title = 'novaby';
        $description = 'novaby';
        $media = '';
        $data=[
            'facebook'=>'http://www.facebook.com/share.php?u='.$url,
            'twitter'=>'https://twitter.com/intent/tweet?text='.$title.'&url='.$url.'&via=novabycompany',
            'linkedin'=>'http://www.linkedin.com/sharing/share-offsite?mini=true&ro=true&title='.$title.'&url='.$url.'&summary='.$description,
            'pinterest'=>'https://www.pinterest.com/pin/create/button/?url='.$url.'&description='.$description.'&media='.$media,
        ];
        return $this->jsonOk('ok',['url'=>$data]);
    }
}
