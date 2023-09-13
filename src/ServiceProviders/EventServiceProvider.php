<?php

namespace Daalder\Exact\ServiceProviders;

use Daalder\Exact\Listeners\DailyEventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Daalder\Exact\Listeners\PaymentConfirmedListener;
use Pionect\Daalder\Events\Interval\DailyEvent;
use Pionect\Daalder\Events\Payment\PaymentConfirmed;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        PaymentConfirmed::class => [
            PaymentConfirmedListener::class,
        ],
        DailyEvent::class => [
            DailyEventListener::class
        ]
    ];
}
