<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Model\PrjApply;
use App\Model\ProjectRate;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProjectTiming extends Job implements ShouldQueue
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
        $rate = ProjectRate::where('r_pid',$this->id)->first();
        if($rate->r_result==0)
        {
            if($rate->r_catetime+3600*24*7 <= time())
            {
                $rate->r_result = 2;
                $rate->save();
                $project = \App\Model\Project::where('prj_id',$rate->r_pid)->first();
                $project->prj_progress = 3.5;
                $project->save();
                $apply = PrjApply::where(['prj_id'=>$rate->r_pid,'user_id'=>$project->prj_modeler])->first();
                $apply->prj_status = 3;
                $apply->save();
            }
        }

    }
}
