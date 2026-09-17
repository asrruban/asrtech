<?php

use App\Http\Controllers\Client\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified'])->prefix('client-area/projects')->name('client.projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
    Route::post('/{project}/updates', [ProjectController::class, 'postUpdate'])->middleware('throttle:30,1')->name('updates.store');
    Route::post('/{project}/files', [ProjectController::class, 'upload'])->middleware('throttle:20,1')->name('files.store');
    Route::get('/{project}/files/{file}', [ProjectController::class, 'download'])->name('files.download');
    Route::post('/{project}/approvals/{approval}', [ProjectController::class, 'decide'])->name('approvals.decide');
});
