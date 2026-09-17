<?php

use App\Http\Controllers\Client\PasswordRecoveryController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('forgot-password', [PasswordRecoveryController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordRecoveryController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordRecoveryController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordRecoveryController::class, 'update'])
        ->middleware('throttle:10,1')->name('password.reset.store');
});
