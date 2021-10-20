<?php

namespace Daalder\Exact\Jobs;

use Daalder\Exact\Services\ConnectionFactory;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\WebhookSubscription;

class RegisterWebhooks
{
    /**
     * @todo This ServiceProvider makes an Exact API call at every boot. Make this a scheduled running task instead.
     */
    public function handle() {
        // Don't try to register webhooks on calls to a webhook or to the authorization callback
        try {
            /** @var Connection $client */
            $connection = ConnectionFactory::getConnection();
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
