<?php

namespace App\Listeners;

use App\Events\CommentModel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CommentModelListener
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
     * @param  CommentModel  $event
     * @return void
     */
    public function handle(CommentModel $event)
    {
        //
    }
}
