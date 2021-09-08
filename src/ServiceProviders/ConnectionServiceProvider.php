<?php

namespace Daalder\Exact\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Picqer\Financials\Exact\Connection;

class ConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {}

    public function register() {
        $this->app->singleton(Connection::class, function () {
            $connection = new Connection();
            $connection->setRedirectUrl(config('daalder-exact.callback_url'));
            $connection->setExactClientId(config('daalder-exact.client_id'));
            $connection->setExactClientSecret(config('daalder-exact.client_secret'));
            $connection->setBaseUrl(config('daalder-exact.base_url'));

            $file = '';
            try {
                $file = file_get_contents(storage_path('exact/oauth.json'));
            } catch (\Exception $e) {
                //
            }

            $file = trim(preg_replace('/\s\s+/', ' ', $file));
            $file = json_decode($file);

            if (optional($file)->authorization_code) {
                // Retrieves authorizationcode from database
                $connection->setAuthorizationCode($file->authorization_code);
            }

            if (optional($file)->access_token) {
                // Retrieves accesstoken from database
                $connection->setAccessToken($file->access_token);
            }

            if (optional($file)->refresh_token) {
                // Retrieves refreshtoken from database
                $connection->setRefreshToken($file->refresh_token);
            }

            if (optional($file)->expires_in) {
                // Retrieves expires timestamp from database
                $connection->setTokenExpires($file->expires_in);
            }

            //
            if($connection->needsAuthentication()) {
                return $connection;
            }

            // Make the client connect and exchange tokens
            try {
                $connection->connect();
            } catch (\Exception $e) {
                throw new \Exception('Could not connect to Exact: ' . $e->getMessage());
            }

            $updateOauthFile = false;
            // Save the new tokens for next connections
            if(optional($file)->access_token !== $connection->getAccessToken()) {
                $file->access_token = $connection->getAccessToken();
                $updateOauthFile = true;
            }
            if(optional($file)->refresh_token !== $connection->getRefreshToken()) {
                $file->refresh_token = $connection->getRefreshToken();
                $updateOauthFile = true;
            }
            // Optionally, save the expiry-timestamp. This prevents exchanging valid tokens (ie. saves you some requests)
            if(optional($file)->expires_in !== $connection->getTokenExpires()) {
                $file->expires_in = $connection->getTokenExpires();
                $updateOauthFile = true;
            }

            if($updateOauthFile) {
                // If storage/exact directory doesn't exist yet
                if(file_exists(storage_path('exact')) === false) {
                    // Create directory
                    mkdir(storage_path('exact'));
                }
                file_put_contents(storage_path('exact/oauth.json'), json_encode($file));
            }

            return $connection;
        });
    }
}
