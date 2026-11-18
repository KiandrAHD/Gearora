<?php

use App\Http\Controllers\Api\MidtransNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Endpoint publik untuk provider (webhook). Tidak memakai middleware web/CSRF.
|
*/

Route::post('/payments/midtrans/notification', MidtransNotificationController::class)
    ->middleware('throttle:midtrans-webhook')
    ->name('payments.midtrans.notification');
