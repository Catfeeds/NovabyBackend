<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\NotifyEvent' => [
            'App\Listeners\NotifyListener',
        ],
        'App\Events\MailEvent' => [
            'App\Listeners\MailListener',
        ],
        'App\Events\LikeModel'=>[
            'App\Listeners\LikeModelListener',
        ],
        'App\Events\FollowSomeBody'=>[
            'App\Listeners\FollowSomeBodyListener',
        ],
        'App\Events\CommentModel'=>[
            'App\Listeners\CommentModelListener',
        ],
        'App\Events\BuildingEvent' => [
            'App\Listeners\BuildingListener'
        ]
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
