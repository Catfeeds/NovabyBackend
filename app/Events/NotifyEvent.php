<?php

namespace App\Events;

use App\Events\Event;
use App\Model\Notify;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotifyEvent extends Event
{
    use SerializesModels;


    /**
     * NotifyEvent constructor.
     * @param $type
     * @param $user_id
     *  * @param $remark
     */
    public function __construct($type,$user_id,$remark=NULL)
    {
        $this->type = $type;
        $this->user_id = $user_id;
        $this->remark = $remark;
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
