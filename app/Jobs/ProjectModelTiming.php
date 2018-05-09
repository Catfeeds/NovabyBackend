<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Model\BuildDaily;
use App\Model\Project;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProjectModelTiming extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $build = BuildDaily::where('bd_id',$this->id)->first();
        if($build->bd_pub==4 && $build->bd_finaly==1){
            $build->status=1;
            $build->save();
            $status = $this->modelStatus($build->bd_pid);
            if($status==0)
            {
                $build->project->prj_progress=3;
                $build->project->prj_success=1;
                $build->project->updated_at=time();
                $build->project->save();
            }
        }else{
            true;
        }

    }
    /**
     * 模型状态
     * @param $projectId
     * @return int
     */
    private function modelStatus($projectId)
    {
        $project = Project::with('models')->find($projectId);
        $status=0;
        foreach ($project->models as $model)
        {
            if($model->bd_pub==4 && $model->status==1){
                $status+=0;
            }else{
                $status+=1;
            }
        }
        return $status;
    }
}
