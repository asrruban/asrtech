<?php

use App\Http\Controllers\Api\InboundEmailController;
use App\Http\Controllers\Api\V1\ResourceController;
use Illuminate\Support\Facades\Route;

// Inbound email webhook for support-ticket email piping. Authenticated with
// a shared secret from config (SUPPORT_INBOUND_TOKEN), not an API token.
Route::post('inbound-email', InboundEmailController::class)
    ->middleware('throttle:30,1')
    ->name('api.inbound-email');

Route::middleware(['api.token', 'throttle:120,1'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('products', [ResourceController::class, 'products'])->name('products');
        Route::get('orders', [ResourceController::class, 'orders'])->name('orders');
        Route::get('orders/{orderNumber}', [ResourceController::class, 'order'])->name('orders.show');
        Route::get('licenses/{key}', [ResourceController::class, 'license'])->name('licenses.show');
        Route::get('invoices/{number}', [ResourceController::class, 'invoice'])->name('invoices.show');
        Route::get('subscriptions', [ResourceController::class, 'subscriptions'])->name('subscriptions');
    });
