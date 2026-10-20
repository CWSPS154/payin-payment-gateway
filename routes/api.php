<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/payments', [PaymentController::class, 'store']);
Route::post('/payment/webhook', [PaymentWebhookController::class, 'store']);
