<?php

namespace Daalder\Exact\ServiceProviders;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Daalder\Exact\Listeners\OrderCreatedListener;
use Pionect\Daalder\Events\Order\OrderCreated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OrderCreated::class => [
            OrderCreatedListener::class,
        ]
    ];
}
