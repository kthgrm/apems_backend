<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ImpactAssessmentController;
use App\Http\Controllers\IntlPartnerController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\ResolutionController;
use App\Http\Controllers\TechTransferController;
use App\Http\Controllers\CampusCollegeController;
use App\Http\Controllers\AuditLogController;
use App\Models\Campus;
use App\Models\User;
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

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');
    });

    // User management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function (Request $request) {
            return User::all();
        })->name('index');
    });

    // Core resource routes
    Route::apiResource('campuses', CampusController::class);
    Route::apiResource('colleges', CollegeController::class);

    Route::apiResource('awards', AwardController::class);
    Route::apiResource('impact-assessments', ImpactAssessmentController::class);

    Route::apiResource('international-partners', IntlPartnerController::class);
    Route::patch('international-partners/{internationalPartner}/archive', [IntlPartnerController::class, 'archive'])
        ->name('international-partners.archive');

    Route::apiResource('modalities', ModalityController::class);

    Route::apiResource('resolutions', ResolutionController::class);
    Route::patch('resolutions/{resolution}/archive', [ResolutionController::class, 'archive'])
        ->name('resolutions.archive');

    Route::apiResource('tech-transfers', TechTransferController::class);
    Route::patch('tech-transfers/{techTransfer}/archive', [TechTransferController::class, 'archive'])
        ->name('tech-transfers.archive');

    // Audit logs (read-only for most users)
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show');
        Route::get('/model/{modelType}/{modelId}', [AuditLogController::class, 'getModelAuditLogs'])->name('model');
        Route::get('/statistics', [AuditLogController::class, 'getStatistics'])->name('statistics');
    });

    // Relationship management routes
    Route::prefix('relationships')->name('relationships.')->group(function () {
        Route::get('/campuses/{campus}/colleges', [CampusController::class, 'getCollegesFromCampus'])->name('campus.colleges');
        // Route::get('/colleges/{college}/campuses', [CampusCollegeController::class, 'getCampusesForCollege'])->name('college.campuses');
    });
});
