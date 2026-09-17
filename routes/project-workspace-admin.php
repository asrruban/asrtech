<?php

use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin.permission:support.manage')->group(function () {
    Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::post('inquiries/{inquiry}/quote', [InquiryController::class, 'quote'])->middleware('admin.permission:billing.manage')->name('inquiries.quote');
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('projects/{project}/milestones', [ProjectController::class, 'milestone'])->name('projects.milestones.store');
    Route::patch('projects/{project}/milestones/{milestone}', [ProjectController::class, 'updateMilestone'])->name('projects.milestones.update');
    Route::post('projects/{project}/updates', [ProjectController::class, 'postUpdate'])->name('projects.updates.store');
    Route::post('projects/{project}/files', [ProjectController::class, 'upload'])->middleware('throttle:20,1')->name('projects.files.store');
    Route::get('projects/{project}/files/{file}', [ProjectController::class, 'download'])->name('projects.files.download');
    Route::post('projects/{project}/approvals', [ProjectController::class, 'approval'])->name('projects.approvals.store');
    Route::delete('projects/{project}/approvals/{approval}', [ProjectController::class, 'cancelApproval'])->name('projects.approvals.cancel');
});
