<?php

use Illuminate\Support\Facades\Route;
use Daalder\Exact\Http\Controllers\AuthController;
use Daalder\Exact\Http\Controllers\WebhookController;

Route::group(['prefix' => 'exact'], function () {
    Route::get('auth-callback', AuthController::class.'@callback');
    Route::post('webhook-stockposition', WebhookController::class.'@stockPosition');
});
