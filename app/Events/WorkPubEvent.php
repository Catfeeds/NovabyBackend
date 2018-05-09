<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WorkPubEvent extends Event implements ShouldBroadcast
{
    use SerializesModels;
    protected $channel;
    public $msg;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($channel,$msg)
    {
        echo __METHOD__."<br/>";
        $this->channel =$channel;
        $this->msg=$msg;
        print_r($this->channel);
        print_r($this->msg);
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        echo __METHOD__."<br/>";
        return [$this->channel];
    }

    public function broardcastAs(){
        echo __METHOD__."<br/>";
        return '';
    }
}
