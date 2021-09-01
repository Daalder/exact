<?php

namespace Daalder\Exact\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Daalder\Exact\Observers\OrderObserver;
use Pionect\Daalder\Models\Order\Order;

class ObservationServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        Order::observe(OrderObserver::class);
    }
}
