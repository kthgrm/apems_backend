<?php

use Illuminate\Support\Facades\Route;
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Main application route - redirect to frontend or show API info
Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to APEMS (Academic Program Evaluation Management System)',
        'version' => '1.0.0',
        'api_base' => url('/api'),
        'documentation' => url('/docs'),
        'status' => 'active',
        'timestamp' => now()->toISOString()
    ]);
})->name('home');

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'connected',
            'cache' => 'active',
            'session' => 'active'
        ],
        'system' => [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]
    ]);
})->name('health.check');

// System status endpoint
Route::get('/status', function () {
    return response()->json([
        'system_status' => 'operational',
        'uptime' => 'Available',
        'last_updated' => now()->toISOString(),
        'api_endpoints' => [
            'authentication' => '/api/auth/*',
            'campuses' => '/api/campuses',
            'colleges' => '/api/colleges',
            'awards' => '/api/awards',
            'impact_assessments' => '/api/impact-assessments',
            'international_partners' => '/api/international-partners',
            'modalities' => '/api/modalities',
            'resolutions' => '/api/resolutions',
            'tech_transfers' => '/api/tech-transfers',
            'campus_colleges' => '/api/campus-colleges',
            'audit_logs' => '/api/audit-logs'
        ]
    ]);
})->name('status');

// Documentation routes
Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'title' => 'APEMS API Documentation',
            'description' => 'Academic Program Evaluation Management System API',
            'version' => '1.0.0',
            'base_url' => url('/api'),
            'authentication' => [
                'type' => 'Bearer Token',
                'provider' => 'Laravel Sanctum',
                'login_endpoint' => '/api/auth/login'
            ],
            'content_type' => 'application/json',
            'endpoints' => 'https://localhost:8000/docs/endpoints'
        ]);
    })->name('index');

    Route::get('/endpoints', function () {
        return response()->json([
            'authentication' => [
                'POST /api/auth/login' => 'User login',
                'POST /api/auth/logout' => 'User logout (requires auth)',
                'GET /api/user' => 'Get authenticated user info'
            ],
            'campuses' => [
                'GET /api/campuses' => 'List all campuses',
                'POST /api/campuses' => 'Create new campus',
                'GET /api/campuses/{id}' => 'Get specific campus',
                'PUT /api/campuses/{id}' => 'Update campus',
                'DELETE /api/campuses/{id}' => 'Delete campus'
            ],
            'colleges' => [
                'GET /api/colleges' => 'List all colleges',
                'POST /api/colleges' => 'Create new college',
                'GET /api/colleges/{id}' => 'Get specific college',
                'PUT /api/colleges/{id}' => 'Update college',
                'DELETE /api/colleges/{id}' => 'Delete college'
            ],
            'tech_transfers' => [
                'GET /api/tech-transfers' => 'List all tech transfers',
                'POST /api/tech-transfers' => 'Create new tech transfer',
                'GET /api/tech-transfers/{id}' => 'Get specific tech transfer',
                'PUT /api/tech-transfers/{id}' => 'Update tech transfer',
                'DELETE /api/tech-transfers/{id}' => 'Delete tech transfer',
                'GET /api/tech-transfers/statistics' => 'Get tech transfer statistics',
                'GET /api/tech-transfers/commercialization-metrics' => 'Get commercialization metrics'
            ],
            'campus_colleges' => [
                'GET /api/campus-colleges' => 'List campus-college relationships',
                'POST /api/campus-colleges' => 'Create new relationship',
                'GET /api/campus-colleges/{id}' => 'Get specific relationship',
                'PUT /api/campus-colleges/{id}' => 'Update relationship',
                'DELETE /api/campus-colleges/{id}' => 'Delete relationship'
            ],
            'audit_logs' => [
                'GET /api/audit-logs' => 'List audit logs',
                'GET /api/audit-logs/{id}' => 'Get specific audit log',
                'GET /api/audit-logs/statistics' => 'Get audit statistics'
            ]
        ]);
    })->name('endpoints');
});

// Fallback route for undefined web routes
Route::fallback(function () {
    return response()->json([
        'error' => 'Route not found',
        'message' => 'The requested web route does not exist',
        'suggestions' => [
            'api_documentation' => url('/docs'),
            'api_base' => url('/api'),
            'health_check' => url('/health'),
            'system_status' => url('/status')
        ]
    ], 404);
});
