<?php

namespace Daalder\Exact\Services;

use Illuminate\Support\Facades\Cache;
use Picqer\Financials\Exact\Connection;

class ConnectionFactory
{
    public static function getConnection()
    {
        // Create Connection instance and setup some config keys
        $connection = new Connection();

        // If the config keys haven't been set, return the fresh (non-functional) $connection
        if(self::exactConfigKeysValid()) {
            return $connection;
        }

        // Load config keys into $connection
        self::loadExactConfigIntoConnection($connection);

        // Load Oauth keys from file
        self::loadOauthKeysIntoConnection($connection);

        // Set callback on connection for storing newly fetched codes/tokens
        $connection->setTokenUpdateCallback(self::tokenUpdateCallback());

        $connection->setRefreshAccessTokenCallback(self::refreshAccessTokenCallback());

        // Set callbacks for locking/unlocking the token callback. This prevents multiple simultaneous requests
        // from messing up the stored tokens.
        $connection->setAcquireAccessTokenLockCallback(self::acquireAccessTokenLockCallback());

        $connection->setAcquireAccessTokenUnlockCallback(self::acquireAccessTokenUnlockCallback());

        try {
            if($connection->needsAuthentication()) {
                // Don't continue, because it will redirect every request to the Exact ouath page (and fail there)
                Logger()->error('Exact - ('.request()->fullUrl().') Could not connect: Authentication (/authenticate-exact) needed.');
                return $connection;
            }

            // If access token is not set or token has expired, acquire new token
            if (empty($connection->getAccessToken()) || ($connection->getTokenExpires() - 10) < time()) {
                Logger()->warning('Exact - ('.request()->fullUrl().') Attempt to do oauth.');
            }

            // Connect and exchange tokens
            $connection->connect();
        } catch (\Exception $e) {
            // Log connection exception
            Logger()->error('Exact - ('.request()->fullUrl().') Could not connect: ' . $e->getMessage());
        }

        // Always return a Connection, even if it didn't authenticate successfully
        return $connection;
    }

    private function exactConfigKeysValid() {
        return
            !is_null(config('daalder-exact.callback_url')) &&
            !is_null(config('daalder-exact.client_id')) &&
            !is_null(config('daalder-exact.client_secret')) &&
            !is_null(config('daalder-exact.base_url'));
    }

    private function loadExactConfigIntoConnection(Connection $connection) {
        $connection->setRedirectUrl(config('daalder-exact.callback_url'));
        $connection->setExactClientId(config('daalder-exact.client_id'));
        $connection->setExactClientSecret(config('daalder-exact.client_secret'));
        $connection->setBaseUrl(config('daalder-exact.base_url'));
    }

    private function loadOauthKeysIntoConnection(Connection $connection) {
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
    }

    public function tokenUpdateCallback(Connection $connection) {
        $file = new \stdClass();
        $file->access_token = $connection->getAccessToken();
        $file->refresh_token = $connection->getRefreshToken();
        $file->expires_in = $connection->getTokenExpires();

        // If storage/exact directory doesn't exist yet
        if (file_exists(storage_path('exact')) === false) {
            // Create directory
            mkdir(storage_path('exact'));
        }
        file_put_contents(storage_path('exact/oauth.json'), json_encode($file));
    }

    public function refreshAccessTokenCallback(Connection $connection) {
        self::loadOauthKeysIntoConnection($connection);
    }

    public function acquireAccessTokenLockCallback(Connection $connection) {
        $lock = Cache::lock('exact-lock', 8);

        // If another thread is currently doing a token request
        if($lock->get() === false) {
            Logger()->warning('Exact - ('.request()->fullUrl().') exact oauth call is locked. Waiting...');

            $startTime = now();

            // Wait for the other thread to unlock the exact-lock
            do {
                // Wait 100ms before testing the lock again
                sleep(0.1);

                // If the wait timeout was exceeded
                if($startTime->diffInSeconds(now()) > 10) {
                    // Fail this thread/request
                    throw new \Exception('Exact - ('.request()->fullUrl().') lock time exceeded');
                }
            } while($lock->get() === false);
        } else {
            Logger()->warning('Exact - ('.request()->fullUrl().') locking exact oauth call.');
        }

        Logger()->warning('Exact - ('.request()->fullUrl().') passed lock check on exact oauth call.');
    }

    public function acquireAccessTokenUnlockCallback(Connection $connection) {
        Logger()->warning('Exact - ('.request()->fullUrl().') releasing lock on exact oauth call');
        // Unlock the exact-lock
        Cache::lock('exact-lock', 8)->release();
    }
}
