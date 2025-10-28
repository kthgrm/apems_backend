<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ImpactAssessmentController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\ResolutionController;
use App\Http\Controllers\TechTransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Models\Engagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Public authentication routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('password.email');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');
    });

    // User management routes - specific routes MUST come before apiResource
    Route::patch('users/bulk-activate', [UserController::class, 'bulkActivate'])
        ->name('users.bulk-activate');
    Route::patch('users/bulk-deactivate', [UserController::class, 'bulkDeactivate'])
        ->name('users.bulk-deactivate');
    Route::patch('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])
        ->name('users.toggle-admin');
    Route::apiResource('users', UserController::class);

    Route::apiResource('campuses', CampusController::class);
    Route::apiResource('colleges', CollegeController::class);

    // Core resource routes
    Route::apiResource('tech-transfers', TechTransferController::class);
    Route::patch('tech-transfers/{techTransfer}/archive', [TechTransferController::class, 'archive'])
        ->name('tech-transfers.archive');
    Route::get('user/tech-transfers', [TechTransferController::class, 'getUserTechTransfers'])
        ->name('user.tech-transfers');

    Route::apiResource('awards', AwardController::class);
    Route::patch('awards/{award}/archive', [AwardController::class, 'archive'])
        ->name('awards.archive');

    Route::apiResource('engagements', EngagementController::class);
    Route::patch('engagements/{engagement}/archive', [EngagementController::class, 'archive'])
        ->name('engagements.archive');

    Route::apiResource('resolutions', ResolutionController::class);
    Route::patch('resolutions/{resolution}/archive', [ResolutionController::class, 'archive'])
        ->name('resolutions.archive');

    Route::apiResource('modalities', ModalityController::class);
    Route::patch('modalities/{modality}/archive', [ModalityController::class, 'archive'])
        ->name('modalities.archive');

    Route::apiResource('impact-assessments', ImpactAssessmentController::class);
    Route::patch('impact-assessments/{impactAssessment}/archive', [ImpactAssessmentController::class, 'archive'])
        ->name('impact-assessments.archive');

    // Dashboard routes
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/admin-stats', [DashboardController::class, 'getAdminStats'])->name('admin-stats');
    });

    // User dashboard route
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');

    // Relationship management routes
    Route::prefix('relationships')->name('relationships.')->group(function () {
        Route::get('/campuses/{campus}/colleges', [CampusController::class, 'getCollegesFromCampus'])->name('campus.colleges');
    });

    // Report routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/technology-transfers', [ReportController::class, 'technologyTransfers'])->name('technology-transfers');
        Route::get('/technology-transfers/pdf', [ReportController::class, 'technologyTransfersPdf'])->name('technology-transfers.pdf');

        Route::get('/awards', [ReportController::class, 'awards'])->name('awards');
        Route::get('/awards/pdf', [ReportController::class, 'awardsPdf'])->name('awards.pdf');

        Route::get('/engagements', [ReportController::class, 'engagements'])->name('engagements');
        Route::get('/engagements/pdf', [ReportController::class, 'engagementsPdf'])->name('engagements.pdf');

        Route::get('/impact-assessments', [ReportController::class, 'impactAssessments'])->name('impact-assessments');
        Route::get('/impact-assessments/pdf', [ReportController::class, 'impactAssessmentsPdf'])->name('impact-assessments.pdf');

        Route::get('/modalities', [ReportController::class, 'modalities'])->name('modalities');
        Route::get('/modalities/pdf', [ReportController::class, 'modalitiesPdf'])->name('modalities.pdf');

        Route::get('/resolutions', [ReportController::class, 'resolutions'])->name('resolutions');
        Route::get('/resolutions/pdf', [ReportController::class, 'resolutionsPdf'])->name('resolutions.pdf');

        Route::get('/users', [ReportController::class, 'users'])->name('users');
        Route::get('/users/pdf', [ReportController::class, 'usersPdf'])->name('users.pdf');

        Route::get('/audit-trail', [ReportController::class, 'auditTrail'])->name('audit-trail');
        Route::get('/audit-trail/pdf', [ReportController::class, 'auditTrailPdf'])->name('audit-trail.pdf');
    });
});
