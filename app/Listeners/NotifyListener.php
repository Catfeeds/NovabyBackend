<?php

namespace App\Listeners;

use App\Events\NotifyEvent;
use App\Model\Message;
use App\Model\Notify;
use App\Model\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyListener
{


    /**
     * Handle the event.
     *
     * @param  NotifyEvent  $event
     * @return void
     */
    public function handle(NotifyEvent $event)
    {
        $notify_id = Notify::where('type',$event->type)->value('id');
        $message = new Message();
        $message->msg_from_uid =  0;
        $message->msg_to_uid = $event->user_id;
        $message->msg_action = 3;
        $message->msg_rid = $notify_id;
        $message->msg_remark = $event->remark;
        $message->msg_time = time();
        $message->save();

    }
}
