<?php

namespace Daalder\Exact\ServiceProviders;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Picqer\Financials\Exact\Connection;

class ConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->app->singleton(Connection::class, function () {
            // Create Connection instance and setup some config keys
            $connection = new Connection();
            $connection->setRedirectUrl(config('daalder-exact.callback_url'));
            $connection->setExactClientId(config('daalder-exact.client_id'));
            $connection->setExactClientSecret(config('daalder-exact.client_secret'));
            $connection->setBaseUrl(config('daalder-exact.base_url'));

            // Get contents of file oauth.json
            $file = '';
            try {
                $file = file_get_contents(storage_path('exact/oauth.json'));
            } catch (\Exception $e) {
                //
            }

            // Format data from file
            $file = trim(preg_replace('/\s\s+/', ' ', $file));
            $file = json_decode($file);

            // Get codes/tokens from file and set them on the Connection
            if (optional($file)->authorization_code) {
                $connection->setAuthorizationCode($file->authorization_code);
            }

            if (optional($file)->access_token) {
                $connection->setAccessToken($file->access_token);
            }

            if (optional($file)->refresh_token) {
                $connection->setRefreshToken($file->refresh_token);
            }

            if (optional($file)->expires_in) {
                $connection->setTokenExpires($file->expires_in);
            }

            // Set callback on connection for storing newly fetched codes/tokens
            $connection->setTokenUpdateCallback(function() use ($file, $connection) {
                $updateOauthFile = false;

                if (optional($file)->access_token !== $connection->getAccessToken()) {
                    $file->access_token = $connection->getAccessToken();
                    $updateOauthFile = true;
                }

                if (optional($file)->refresh_token !== $connection->getRefreshToken()) {
                    $file->refresh_token = $connection->getRefreshToken();
                    $updateOauthFile = true;
                }

                if (optional($file)->expires_in !== $connection->getTokenExpires()) {
                    $file->expires_in = $connection->getTokenExpires();
                    $updateOauthFile = true;
                }

                // If any tokens/codes were changed
                if ($updateOauthFile) {
                    // If storage/exact directory doesn't exist yet
                    if (file_exists(storage_path('exact')) === false) {
                        // Create directory
                        mkdir(storage_path('exact'));
                    }
                    file_put_contents(storage_path('exact/oauth.json'), json_encode($file));
                }
            });

            // Set callbacks for locking/unlocking the token callback. This prevents multiple simultaneous requests
            // from messing up the stored tokens.
            $connection->setAcquireAccessTokenLockCallback(function() {
                // If another thread is currently doing a token request
                if(cache()->get('exact-lock') === true) {
                    $startTime = now();

                    // Wait for the other thread to unlock the exact-lock
                    do {
                        // Wait 100ms before testing the lock again
                        sleep(0.1);

                        // If the wait timeout was exceeded
                        if($startTime->diffInSeconds(now()) > 10) {
                            // Fail this thread/request
                            throw new \Exception('Exact lock time exceeded');
                        }
                    } while(cache()->get('exact-lock') === true);
                }

                // Lock the exact-lock (because this thread will now do a token request)
                cache()->set('exact-lock', true);
            });

            $connection->setAcquireAccessTokenUnlockCallback(function() {
                // Unlock the exact-lock
                cache()->set('exact-lock', false);
            });

            try {
                if($connection->needsAuthentication()) {
                    // Don't continue, because it will redirect every request to the Exact ouath page (and fail there)
                    Logger()->error('Could not connect to Exact: Authentication (/authenticate-exact) needed.');
                    return $connection;
                }

                // Connect and exchange tokens
                $connection->connect();
            } catch (\Exception $e) {
                // Log connection exception
                Logger()->error('Could not connect to Exact: ' . $e->getMessage());
            }

            // Always return a Connection, even if it didn't authenticate successfully
            return $connection;
        });
    }
}
