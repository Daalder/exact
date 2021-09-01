<?php

namespace Daalder\Exact;

use Daalder\Exact\ServiceProviders\EventServiceProvider;
use Illuminate\Support\ServiceProvider;
use Daalder\Exact\ServiceProviders\ConnectionServiceProvider;
use Daalder\Exact\ServiceProviders\ObservationServiceProvider;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\StockCount;
use Picqer\Financials\Exact\WebhookSubscription;

class ExactServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/daalder-exact.php' => config_path('daalder-exact.php')
        ]);

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'daalder-exact-migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/exact.php');

        if ($this->app->runningInConsole()) {
//            $this->commands([
//                PullExactVatRates::class,
//            ]);
        }

//        try {
//            /** @var Connection $client */
//            $connection = app(\Picqer\Financials\Exact\Connection::class);
//            $webhook = new WebhookSubscription($connection);
//            $webhook->Topic = 'StockPositions';
//            $webhook->CallbackURL = config('app.url').'/exact/webhook-stockposition';
//            $webhook->save();

//            $stock = new StockCount();
//            $stock->filter('', '', 'Description, StockCountLines')[0]->StockCountLines[0]->QuantityInStock;
//        } catch (\Exception $e) {
//            echo "bad";
//        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/daalder-exact.php', 'daalder-exact'
        );

        $this->app->register(ConnectionServiceProvider::class);
        $this->app->register(ObservationServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }
}
