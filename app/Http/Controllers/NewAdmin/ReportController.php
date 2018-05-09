<?php

namespace App\Http\Controllers\NewAdmin;

use App\Model\Comment;
use App\Model\CommentReport;
use App\Model\Reason;
use App\Model\Work;
use App\Model\WorkReport;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 举报列表
     * @param $id
     * @return
     */
    public function index($id)
    {
        if($id==0){
            $reports = CommentReport::orderBy('id','desc')->paginate(20);
        }else{
           $reports =  WorkReport::orderBy('id','desc')->paginate(20);
        }
        return view('newadmin.report.index')->with(['reports'=> $reports,'id'=> $id]);
    }

    /**
     * 删除
     * @param $id
     * @param $type
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function delete($id,$type)
    {
        if($type == 0)
        {
            $report = CommentReport::find($id);
            Comment::destroy($report->comm_id);
        }else{
            $report = WorkReport::find($id);
            $work = $report->work;
            $work->work_status = 2;
            $work->save();
        }
        $report->status = 1;
        $report->save();
        return redirect('admin/report/index/'.$type);

    }

    /**
     * 忽略
     * @param $id
     * @param $type
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function ignore($id,$type)
    {
        if($type == 0)
        {
            $report = CommentReport::find($id);
        }else{
            $report = WorkReport::find($id);
        }
        $report->status = 1;
        $report->save();
        return redirect('admin/report/index/'.$type);
    }

    /**
     * 举报原因
     * @return
     */
    public function reason()
    {
        $reasons = Reason::paginate(15);
        return view('newadmin.report.reason')->with('reasons',$reasons);
    }

    /**
     *修改
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $reason = Reason::find($request->get('id'));
        $reason->display = $request->get('content');
        $reason->save();
        return ['code'=>1,'msg'=>'successful'];
    }

    /**
     *创建举报原因模版
     */
    public function template(Request $request)
    {
        $this->validate($request,[
            'content'=>'required'
        ]);
        $reason = new Reason();
        $reason->type = $request->get('type');
        $reason->display = 1;
        $reason->content = $request->get('content');
        $reason->content_cn = $request->get('content_cn');
        $reason->save();
        return redirect('/admin/report/reason');
    }

}
