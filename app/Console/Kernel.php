<?php

namespace App\Console;

use App\Model\PrjApply;
use App\Model\Project;
use App\Model\ProjectRate;
use App\Model\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\ChatSocket::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function (){
            $user = User::select('user_id','project_success','project_time','project_quality','project_commucation','project_amount')
                ->with(['info','build'=>function($query){$query->where('prj_progress',3.5);}])
                ->get();
            $user->map(function ($items){
                $items->info->project_amount = $this->get_amount($items);
                $items->info->project_success = $this->get_success($items);
                $info = $this->get_evaluate($items->build);
                $items->info->project_time = $info[0];
                $items->info->project_quality = $info[1];
                $items->info->project_commucation = $info[2];
                $items->info->save();
            });
        })->dailyAt('02:00');
        $schedule->call(function (){
            if ($handle = opendir(storage_path('logs/'))) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        unlink(storage_path('logs/').$file);
                    }
                }
                closedir($handle);
            }
        })->dailyAt('00:00');
    }
    /**
     * 计算项目成功率
     * @param $user
     * @return int
     */
    private function get_success($user)
    {
        $counts=$user->build->count();
        if($counts<1) {
            return null;
        }
        $success_count=$user->build->where('prj_success',1)->count();
        $success = intval($success_count/$counts*100);
        return $success;
    }
    /**
     * 计算总利润
     * @param $user
     * @return mixed
     */
    private function get_amount($user)
    {
        return $user->build->sum('prj_price');
    }
    /**
     * 计算平均分数
     * @param $build
     * @return mixed
     */
    private function get_evaluate($build)
    {
        $data['0'] = 0;
        $data['1'] = 0;
        $data['2'] = 0;
        if(count($build)>0)
        {
            $info = $build->map(function($item){
                return [$item->rate->r_time,$item->rate->r_quality,$item->rate->r_other];
            })->all();
            foreach($info as $i)
            {
                $data['0']+=$i[0];    //time
                $data['1']+=$i[1];    //quality
                $data['2']+=$i[2];    //commucation
            }
            $data['0'] = floor(($data['0']/count($build))*100)/100;
            $data['1'] = floor(($data['1']/count($build))*100)/100;
            $data['2'] = floor(($data['2']/count($build))*100)/100;
            return $data;
        }
        else{
            return $data;
        }

    }
}
