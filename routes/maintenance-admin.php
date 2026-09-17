<?php

use App\Http\Controllers\Admin\MaintenancePlanController;
use App\Http\Controllers\Admin\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin.permission:billing.manage')->group(function () {
    Route::get('maintenance/plans', [MaintenancePlanController::class, 'index'])->name('maintenance.plans.index');
    Route::post('maintenance/plans', [MaintenancePlanController::class, 'store'])->name('maintenance.plans.store');
    Route::patch('maintenance/plans/{plan}', [MaintenancePlanController::class, 'update'])->name('maintenance.plans.update');
});
Route::middleware('admin.permission:support.manage')->group(function () {
    Route::get('maintenance/requests', [MaintenanceRequestController::class, 'index'])->name('maintenance.requests.index');
    Route::get('maintenance/requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->name('maintenance.requests.show');
    Route::patch('maintenance/requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'update'])->name('maintenance.requests.update');
});
