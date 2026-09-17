<?php

use App\Http\Controllers\Admin\InquiryNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin.permission:settings.manage')->group(function () {
    Route::get('settings/inquiry-notifications', [InquiryNotificationController::class, 'edit'])->name('settings.inquiry-notifications.edit');
    Route::put('settings/inquiry-notifications', [InquiryNotificationController::class, 'update'])->name('settings.inquiry-notifications.update');
    Route::post('settings/inquiry-notifications/{delivery}/retry', [InquiryNotificationController::class, 'retry'])
        ->middleware('throttle:10,1')->name('settings.inquiry-notifications.retry');
});
