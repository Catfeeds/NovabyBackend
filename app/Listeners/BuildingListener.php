<?php

namespace App\Listeners;

use App\Events\BuildingEvent;
use App\Model\Mail;
use App\Model\Message;
use App\Model\Notify;
use App\Model\Project;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class BuildingListener
{

    /**
     * Handle the event.
     *
     * @param  BuildingEvent  $event
     * @return void
     */
    public function handle(BuildingEvent $event)
    {
        $content=$event->content;
        if($event->user->user_type==4) {
            $user_name = $event->user->company_name;
        }else {
            $user_name = $event->user->user_name.' '.$event->user->user_lastname;
        }
        $project = Project::find($event->id);
        \Illuminate\Support\Facades\Mail::send('newadmin.mail.build', ['user' =>$user_name,'content'=>$content,'project'=>$project->prj_name,'url'=>$event->url], function ($message)use($event,$content){
            $message->to($event->user->user_email)->subject($content);
        });
        $notify = new Notify();
        $notify->content = $content;
        $notify->type = 5;
        $notify->title = $event->user->user_id;
        $notify->save();
        $message = new Message();
        $message->msg_from_uid = 0;
        $message->msg_to_uid = $event->user->user_id;
        $message->msg_action = 3;
        $message->msg_rid = $notify->id;
        $message->msg_time = time();
        $message->save();
    }
}
