<?php

use App\Http\Controllers\Client\MaintenanceCheckoutController;
use App\Http\Controllers\Client\MaintenancePlanController;
use Illuminate\Support\Facades\Route;

Route::get('maintenance', [MaintenancePlanController::class, 'index'])->name('maintenance.index');
Route::get('maintenance/{plan:slug}', [MaintenancePlanController::class, 'show'])->name('maintenance.show');
Route::middleware(['auth', 'auth.session', 'verified'])->group(function () {
    Route::post('maintenance/{plan:slug}/request', [MaintenancePlanController::class, 'store'])->middleware('throttle:10,10')->name('maintenance.request');
    Route::get('client-area/maintenance', [MaintenancePlanController::class, 'requests'])->name('account.maintenance.index');
    Route::get('client-area/maintenance/{maintenanceRequest}', [MaintenancePlanController::class, 'details'])->name('account.maintenance.show');
    Route::post('client-area/maintenance/{maintenanceRequest}/checkout', [MaintenanceCheckoutController::class, 'start'])->name('account.maintenance.checkout');
    Route::get('client-area/maintenance/{maintenanceRequest}/checkout', [MaintenanceCheckoutController::class, 'review'])->name('account.maintenance.checkout.review');
    Route::post('client-area/maintenance/{maintenanceRequest}/checkout/pay', [MaintenanceCheckoutController::class, 'pay'])->middleware('throttle:10,10')->name('account.maintenance.checkout.pay');
    Route::post('client-area/maintenance/{maintenanceRequest}/withdraw', [MaintenancePlanController::class, 'withdraw'])->name('account.maintenance.withdraw');
});
