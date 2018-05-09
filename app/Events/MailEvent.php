<?php

namespace App\Events;

use App\Events\Event;
use App\Model\Mail;
use App\Model\Notify;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MailEvent extends Event
{
    use SerializesModels;


    /**
     * MailEvent constructor.
     * @param $type
     * @param $user
     */
    public function __construct($type,$user)
    {
        $this->type = $type;
        $this->user = $user;
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
