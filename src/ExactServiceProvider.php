<?php

namespace Daalder\Exact;

use Daalder\Exact\Commands\PushProductToExact;
use Daalder\Exact\ServiceProviders\EventServiceProvider;
use Illuminate\Support\ServiceProvider;
use Daalder\Exact\ServiceProviders\ObservationServiceProvider;

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
            $this->commands([
                PushProductToExact::class,
            ]);
        }
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

        $this->app->register(ObservationServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }
}
