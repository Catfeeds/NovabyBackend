<?php

namespace App\Model;

use App\Model\Following;
use App\Model\Message;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\User;
use App\Model\Work;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use App\Http\Requests;

class Rai
{
    /**
     * @var
     */
    public $category;
    /**
     * @var
     */
    public $project;
    /**
     * @var int
     */
    public $work;
    /**
     * @var int
     */
    public $message;
    /**
     * @var int
     */
    public $follower;


    /**
     * RAI
     * @param $type
     * @param $user_id
     * @return int
     */
    public function  getRai($type,$user_id)
    {
        $this->setCategory($type);
        $data = $this->getData($user_id); //单人的信息
        $rai = $this->getScore($data);
        return $rai;
    }
    /**
     * 需求类型
     * @param $category
     */
    private function setCategory($category)
    {
        $this->category = $category;
    }
//    /**
//     * 获取报价者
//     * @return mixed
//     */
//    private function getUser()
//    {
//        $users = PrjApply::where('prj_id',$this->project->prj_id)->pluck('user_id');
//            $user = $users->all();
//            $user = User::whereIn('user_id',$user)->with(['project'=>function($query){$query->where('prj_progress',3.5);},'work'=>function($query){$query->where('work_cate',$this->category);},'message','follower'])->get();
//        return $user;
//    }
    /**
     * 获取用户数据
     * @param $id
     * @return mixed
     */
    private function getData($id)
    {
        $data = array();
        $data['project'] = count(Project::where(['prj_progress' => 3.5, 'prj_modeler' => $id])->get()); //完成的项目
        $data['follower'] = count(Following::where('to_uid', $id)->get()); //被关注数
        $data['message']= count(Message::where('msg_from_uid',$id)->get()); //活跃度
        $data['work']= count(Work::where(['work_uid'=>$id,'work_cate'=>$this->category])->get());//上传作品

        return $data;
    }
//    /**
//     * 平均数据
//     * @param $num
//     * @return array
//     */
//    private function getAverage($num)
//    {
//        $users = $this->getUser();
//        $count = array();
//        foreach ($users as $user)
//        {
//            $this->project += count($user->project); //完成的项目
//
//            $this->follower += count($user->follower); //被关注数
//
//            $this->message += count($user->message); //活跃度
//
//            $this->work += count($user->work);//上传作品
//        }
//        $count['project'] = $this->project/count($users);
//
//        $count['follower'] = $this->follower/$count($users);
//
//        $count['message'] = $this->message/$count($users);
//
//        $count['work'] = $this->work/count($users);
//        return $count;
//    }

    /**
     * 分数
     * @param $values
     * @return int
     */
    private function  getScore($values)
    {
        $score = 0 ;
        foreach ($values as $key => $value)
        {
            if($value == null){
                $score += 0 ;
            }elseif(null<$value && $value<5){
                $score += 10;
            }else{
                $score += 25;
            }
        }
        return $score;
    }

}
