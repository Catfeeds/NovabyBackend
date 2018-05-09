<?php

namespace App\Listeners;

use App\Events\FollowSomeBody;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FollowSomeBodyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  FollowSomeBody  $event
     * @return void
     */
    public function handle(FollowSomeBody $event)
    {
        //
        $noticeuser = $event->followuser;



    }
}
