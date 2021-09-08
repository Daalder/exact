<?php

use Illuminate\Support\Facades\Route;
use Daalder\Exact\Http\Controllers\AuthController;
use Daalder\Exact\Http\Controllers\WebhookController;

Route::group(['prefix' => 'exact'], function () {
    Route::get('auth-callback', AuthController::class.'@callback');

    Route::group(['prefix' => 'webhook'], function() {
        Route::post('stockposition', WebhookController::class.'@stockPosition');
    });
});

Route::group(['middleware' => ['api_client']], function () {
    Route::get('authenticate-exact', AuthController::class.'@authenticateExact');
});
