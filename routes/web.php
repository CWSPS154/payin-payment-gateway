<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\CustomerCheckoutController;
use App\Http\Controllers\MerchantDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/admin/login', [WebAuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [WebAuthController::class, 'adminLogin'])->name('admin.login.submit');
Route::get('/merchant/login', [WebAuthController::class, 'showMerchantLogin'])->name('merchant.login');
Route::post('/merchant/login', [WebAuthController::class, 'merchantLogin'])->name('merchant.login.submit');
Route::get('/merchant/register', [WebAuthController::class, 'showMerchantRegistration'])->name('merchant.register');
Route::post('/merchant/register', [WebAuthController::class, 'registerMerchant'])->name('merchant.register.submit');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

Route::get('/pay', [CustomerCheckoutController::class, 'index'])->name('customer.offers');
Route::post('/pay/{merchant}/buy', [CustomerCheckoutController::class, 'buy'])->name('customer.checkout.buy');
Route::get('/checkout/{payment}', [CustomerCheckoutController::class, 'show'])->name('customer.checkout.show');
Route::post('/checkout/{payment}/simulate', [CustomerCheckoutController::class, 'simulate'])->name('customer.checkout.simulate');
Route::post('/checkout/{payment}/trigger-webhook', [CustomerCheckoutController::class, 'triggerWebhook'])->name('customer.checkout.trigger-webhook');
Route::get('/checkout/{payment}/result', [CustomerCheckoutController::class, 'result'])->name('customer.checkout.result');

Route::middleware('role:ADMIN')->group(function () {
    Route::get('/admin/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/admin/payments/{payment}', [PaymentController::class, 'show'])->name('admin.payments.show');
});

Route::middleware('role:MERCHANT')->group(function () {
    Route::get('/merchant/dashboard', [MerchantDashboardController::class, 'show'])->name('merchant.dashboard');
    Route::post('/merchant/dashboard/offer', [MerchantDashboardController::class, 'updateOffer'])->name('merchant.offer.update');
});
