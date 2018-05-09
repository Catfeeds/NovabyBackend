<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BuildingEvent extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     * @param id
     * @param user
     * @param content
     * @param url
     * @return void
     */
    public function __construct($id,$user,$content,$url)
    {
        $this->id = $id;
        $this->user = $user;
        $this->content = $content;
        $this->url = $url;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
