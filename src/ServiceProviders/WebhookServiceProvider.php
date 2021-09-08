<?php

namespace Daalder\Exact\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\WebhookSubscription;

class WebhookServiceProvider extends ServiceProvider
{
    public function register() {
        // Don't try to register webhooks on calls to a webhook or to the authorization callback
        if(
            Str::contains(request()->url(), 'exact/webhook/') === false &&
            Str::contains(request()->url(), 'exact/auth-callback') === false
        ) {
            try {
                /** @var Connection $client */
                $connection = app(Connection::class);
                // If the connection is not authenticated, don't attempt to register webhooks. Otherwise, this would
                // break the entire application (including the authenticate-exact endpoint), since this method is
                // called for every request the user makes.
                if($connection->needsAuthentication()) {
                    return;
                }

                $webhook = new WebhookSubscription($connection);

                $stockSubscription = collect($webhook->get())->firstWhere('Topic', 'StockPositions');

                if(is_null($stockSubscription)) {
                    $webhook = new WebhookSubscription($connection);
                    $webhook->Topic = 'StockPositions';
                    $webhook->CallbackURL = config('app.url').'/exact/webhook/stockposition';
                    $webhook->save();
                }
            } catch (\Exception $e) {
                throw new \Exception('Exception while connecting to Exact: '. $e->getMessage());
            }
        }
    }
}
