<?php

namespace App\Listeners;

use App\Events\MailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class MailListener
{


    /**
     * Handle the event.
     *
     * @param  MailEvent  $event
     * @return void
     */
    public function handle(MailEvent $event)
    {

        $content = \App\Model\Mail::where('type',$event->type)->value('content');
        Mail::send('newadmin.mail.send', ['user' => $event->user->user_name,'content'=>$content], function ($message)use($event,$content){
            $message->to($event->user->user_email)->subject($content);
        });


    }
}
