<?php

namespace Daalder\Exact\ServiceProviders;

use Daalder\Exact\Observers\ProductObserver;
use Daalder\Exact\Observers\StockObserver;
use Illuminate\Support\ServiceProvider;
use Pionect\Daalder\Models\Product\Product;
use Pionect\Daalder\Models\Product\Stock;

class ObservationServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        Stock::observe(StockObserver::class);
        Product::observe(ProductObserver::class);
    }
}
