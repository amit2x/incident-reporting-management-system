<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidentController as ApiIncidentController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\ReportController as ApiReportController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\DepartmentController as ApiDepartmentController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\EscalationMatrixController as ApiEscalationMatrixController;
use App\Http\Controllers\Api\AuditLogController as ApiAuditLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| IMPORTANT: All API route names use 'api.' prefix to avoid conflicts
| with web routes during route caching.
|
*/

Route::prefix('v1')->name('api.')->group(function () {
    
    // ==========================================
    // PUBLIC ROUTES (No Authentication)
    // ==========================================
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
    
    // ==========================================
    // PROTECTED ROUTES (Sanctum Authentication)
    // ==========================================
    // Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('auth')->group(function () {
        
        // Auth
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', function (Request $request) {
            return $request->user()->load('department', 'roles', 'permissions');
        })->name('user');
        Route::put('/user/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::put('/user/password', [AuthController::class, 'updatePassword'])->name('password.update');
        Route::post('/user/avatar', [AuthController::class, 'uploadAvatar'])->name('avatar.upload');
        Route::put('/user/fcm-token', [AuthController::class, 'updateFCMToken'])->name('fcm-token.update');
        
        // ==========================================
        // INCIDENT API ROUTES
        // ==========================================
        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [ApiIncidentController::class, 'index'])->name('index');
            Route::post('/', [ApiIncidentController::class, 'store'])->name('store');
            Route::get('/{incident}', [ApiIncidentController::class, 'show'])->name('show');
            Route::put('/{incident}', [ApiIncidentController::class, 'update'])->name('update');
            Route::delete('/{incident}', [ApiIncidentController::class, 'destroy'])->name('destroy');
            
            // Incident Actions
            Route::post('/{incident}/assign', [ApiIncidentController::class, 'assign'])->name('assign');
            Route::post('/{incident}/escalate', [ApiIncidentController::class, 'escalate'])->name('escalate');
            Route::post('/{incident}/resolve', [ApiIncidentController::class, 'resolve'])->name('resolve');
            Route::post('/{incident}/close', [ApiIncidentController::class, 'close'])->name('close');
            Route::post('/{incident}/reopen', [ApiIncidentController::class, 'reopen'])->name('reopen');
            
            // Comments
            Route::post('/{incident}/comments', [ApiIncidentController::class, 'addComment'])->name('comments.store');
            
            // Media
            Route::post('/{incident}/media', [ApiIncidentController::class, 'uploadMedia'])->name('media.store');
            Route::delete('/{incident}/media/{media}', [ApiIncidentController::class, 'deleteMedia'])->name('media.destroy');
            
            // Timeline & Logs
            Route::get('/{incident}/timeline', [ApiIncidentController::class, 'timeline'])->name('timeline');
            Route::get('/{incident}/logs', [ApiIncidentController::class, 'logs'])->name('logs');
        });
        
        // ==========================================
        // DASHBOARD API ROUTES
        // ==========================================
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/stats', [ApiDashboardController::class, 'stats'])->name('stats');
            Route::get('/feed', [ApiDashboardController::class, 'feed'])->name('feed');
            Route::get('/charts', [ApiDashboardController::class, 'charts'])->name('charts');
            Route::get('/critical-incidents', [ApiDashboardController::class, 'criticalIncidents'])->name('critical-incidents');
        });
        
        // ==========================================
        // REPORT API ROUTES
        // ==========================================
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/kpi', [ApiReportController::class, 'kpiReport'])->name('kpi');
            Route::get('/department', [ApiReportController::class, 'departmentReport'])->name('department');
            Route::get('/sla-compliance', [ApiReportController::class, 'slaReport'])->name('sla');
            Route::get('/export/{format}', [ApiReportController::class, 'exportReport'])->name('export');
        });
        
        // ==========================================
        // NOTIFICATION API ROUTES
        // ==========================================
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [ApiNotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [ApiNotificationController::class, 'unreadCount'])->name('unread-count');
            Route::put('/{notification}/read', [ApiNotificationController::class, 'markAsRead'])->name('read');
            Route::put('/read-all', [ApiNotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::post('/subscribe', [ApiNotificationController::class, 'subscribe'])->name('subscribe');
        });
        
        // ==========================================
        // SEARCH
        // ==========================================
        Route::get('/search', [ApiIncidentController::class, 'search'])->name('search');
        
        // ==========================================
        // ADMIN ONLY API ROUTES
        // ==========================================
        Route::middleware('role:admin|super-admin')->name('admin.')->group(function () {
            
            // Users
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [ApiUserController::class, 'index'])->name('index');
                Route::post('/', [ApiUserController::class, 'store'])->name('store');
                Route::get('/{user}', [ApiUserController::class, 'show'])->name('show');
                Route::put('/{user}', [ApiUserController::class, 'update'])->name('update');
                Route::delete('/{user}', [ApiUserController::class, 'destroy'])->name('destroy');
                Route::put('/{user}/status', [ApiUserController::class, 'updateStatus'])->name('status');
                Route::put('/{user}/roles', [ApiUserController::class, 'updateRoles'])->name('roles');
                Route::get('/{user}/activity', [ApiUserController::class, 'activity'])->name('activity');
            });
            
            // Departments
            Route::prefix('departments')->name('departments.')->group(function () {
                Route::get('/', [ApiDepartmentController::class, 'index'])->name('index');
                Route::post('/', [ApiDepartmentController::class, 'store'])->name('store');
                Route::get('/{department}', [ApiDepartmentController::class, 'show'])->name('show');
                Route::put('/{department}', [ApiDepartmentController::class, 'update'])->name('update');
                Route::delete('/{department}', [ApiDepartmentController::class, 'destroy'])->name('destroy');
            });
            
            // Categories
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [ApiCategoryController::class, 'index'])->name('index');
                Route::post('/', [ApiCategoryController::class, 'store'])->name('store');
                Route::get('/{category}', [ApiCategoryController::class, 'show'])->name('show');
                Route::put('/{category}', [ApiCategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [ApiCategoryController::class, 'destroy'])->name('destroy');
            });
            
            // Escalation Matrix
            Route::prefix('escalation-matrix')->name('escalation-matrix.')->group(function () {
                Route::get('/', [ApiEscalationMatrixController::class, 'index'])->name('index');
                Route::post('/', [ApiEscalationMatrixController::class, 'store'])->name('store');
                Route::get('/{escalationMatrix}', [ApiEscalationMatrixController::class, 'show'])->name('show');
                Route::put('/{escalationMatrix}', [ApiEscalationMatrixController::class, 'update'])->name('update');
                Route::delete('/{escalationMatrix}', [ApiEscalationMatrixController::class, 'destroy'])->name('destroy');
            });
            
            // Audit Logs
            Route::get('/audit-logs', [ApiAuditLogController::class, 'index'])->name('audit-logs');
        });
    });
});