<?php

namespace App\Listeners;

use App\Events\LikeModel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LikeModelListener
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
     * @param  LikeModel  $event
     * @return void
     */
    public function handle(LikeModel $event)
    {
        //
    }
}
