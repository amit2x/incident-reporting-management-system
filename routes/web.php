<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EscalationMatrixController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| IMPORTANT: All web route names use standard naming.
| API routes are in routes/api.php with 'api.' prefix or different names.
|
*/

// Welcome/Landing page
Route::get('/home', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/', function () {
    return redirect()->route('login');
})->name('welcome');

//public feed route:-

Route::get('/feed', [FeedController::class, 'index'])->name('guest.home');
Route::get('/search', [FeedController::class, 'search'])->name('search');
Route::get('/incident/{incident}', [FeedController::class, 'showIncident'])->name('incident.public');
Route::middleware('auth')->group(function () {
    Route::post('/incidents/{incident}/like', [FeedController::class, 'toggleLike'])->name('incidents.like');
});


Route::get('/help', [HelpController::class, 'index'])->name('guest.help');
Route::get('/features', [HelpController::class, 'features'])->name('guest.features');

// Authentication Routes
Auth::routes(['verify' => true]);
Route::get('/captcha/refresh', [App\Http\Controllers\Auth\LoginController::class, 'refreshCaptcha'])->name('captcha.refresh');
// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==========================================
    // INCIDENT ROUTES
    // ==========================================

    // List incidents
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');

    // Create incident form
    Route::get('/incidents/create', [IncidentController::class, 'create'])->name('incidents.create');

    // Store incident
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');

    // Show incident
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');

    // Edit incident form
    Route::get('/incidents/{incident}/edit', [IncidentController::class, 'edit'])->name('incidents.edit');

    // Update incident
    Route::put('/incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
    Route::patch('/incidents/{incident}', [IncidentController::class, 'update']);

    // Delete incident
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

    // Incident Actions
    Route::post('/incidents/{incident}/assign', [IncidentController::class, 'assign'])->name('incidents.assign');
    Route::post('/incidents/{incident}/escalate', [IncidentController::class, 'escalate'])->name('incidents.escalate');
    Route::post('/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    Route::post('/incidents/{incident}/close', [IncidentController::class, 'close'])->name('incidents.close');
    Route::post('/incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');

     // Newly added Incident Actions
    Route::post('/incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
    Route::post('/incidents/{incident}/reject', [IncidentController::class, 'reject'])->name('incidents.reject');
    Route::post('/incidents/{incident}/reassign', [IncidentController::class, 'reassign'])->name('incidents.reassign');


    // Comments
    Route::post('/incidents/{incident}/comments', [IncidentController::class, 'addComment'])->name('incidents.comments.store');
     // Edit comment
    Route::put('/incidents/{incident}/comments/{comment}', [IncidentController::class, 'editComment'])->name('incidents.comments.update');

    // Delete comment
    Route::delete('/incidents/{incident}/comments/{comment}', [IncidentController::class, 'deleteComment'])->name('incidents.comments.destroy');


    // Comment with media
    Route::post('/incidents/{incident}/comments', [IncidentController::class, 'addComment'])->name('incidents.comments.store');


    // Media
    Route::post('/incidents/{incident}/media', [IncidentController::class, 'uploadMedia'])->name('incidents.media.store');
    Route::delete('/incidents/{incident}/media/{media}', [IncidentController::class, 'deleteMedia'])->name('incidents.media.destroy');


    // Incident share data
    Route::get('/incidents/{incident}/share', [IncidentController::class, 'shareData'])->name('incidents.share');

    // ==========================================
    // REPORT ROUTES
    // ==========================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/kpi', [ReportController::class, 'kpiReport'])->name('kpi');
        Route::get('/department', [ReportController::class, 'departmentReport'])->name('department');
        Route::get('/sla', [ReportController::class, 'slaReport'])->name('sla');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });

    // ==========================================
    // NOTIFICATION ROUTES
    // ==========================================
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{id}', [NotificationController::class, 'delete'])->name('delete');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/latest', [NotificationController::class, 'latest'])->name('latest');
        Route::get('/{id}/click', [NotificationController::class, 'handleClick'])->name('handle-click');
    });

    // ==========================================
    // PROFILE ROUTES
    // ==========================================
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar');
    });

    // ==========================================
    // SETTINGS ROUTES
    // ==========================================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });

    // ==========================================
    // ADMIN ROUTES (Role Protected)
    // ==========================================
    Route::middleware('role:admin|super-admin')->prefix('admin')->name('admin.')->group(function () {

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::put('/{user}/status', [UserController::class, 'updateStatus'])->name('status');
            Route::put('/{user}/roles', [UserController::class, 'updateRoles'])->name('roles');
            Route::get('/{user}/activity', [UserController::class, 'activity'])->name('activity');
        });

        // Department Management
        Route::prefix('departments')->name('departments.')->group(function () {
            Route::get('/', [DepartmentController::class, 'index'])->name('index');
            Route::get('/create', [DepartmentController::class, 'create'])->name('create');
            Route::post('/', [DepartmentController::class, 'store'])->name('store');
            Route::get('/{department}', [DepartmentController::class, 'show'])->name('show');
            Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->name('edit');
            Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
            Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
        });

        // Category Management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // Escalation Matrix
        Route::prefix('escalation-matrix')->name('escalation-matrix.')->group(function () {
            Route::get('users-by-department', [EscalationMatrixController::class, 'getUsersByDepartment'])->name('users-by-department');

            Route::get('/', [EscalationMatrixController::class, 'index'])->name('index');
            Route::get('/create', [EscalationMatrixController::class, 'create'])->name('create');
            Route::post('/', [EscalationMatrixController::class, 'store'])->name('store');
            Route::get('/{escalationMatrix}', [EscalationMatrixController::class, 'show'])->name('show');
            Route::get('/{escalationMatrix}/edit', [EscalationMatrixController::class, 'edit'])->name('edit');
            Route::put('/{escalationMatrix}', [EscalationMatrixController::class, 'update'])->name('update');
            Route::delete('/{escalationMatrix}', [EscalationMatrixController::class, 'destroy'])->name('destroy');
            // AJAX route for getting users by department
        });




        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');

        // Notification Settings
        Route::get('/notification-settings', [SettingsController::class, 'notificationSettings'])
            ->name('notification-settings');
        Route::put('/notification-settings', [SettingsController::class, 'updateNotificationSettings'])
            ->name('notification-settings.update');

        // System Settings
        Route::get('/system-settings', [SettingsController::class, 'systemSettings'])
            ->name('system-settings');
        Route::put('/system-settings', [SettingsController::class, 'updateSystemSettings'])
            ->name('system-settings.update');
    });


    // API-like routes for AJAX calls (using web session auth)
    Route::prefix('api/v1')->name('api.')->group(function () {

        // Get users for @mention suggestions
        Route::get('/ajax/users/mention-suggestions', function () {
            $users = \App\Models\User::active()
                ->select('id', 'name', 'username', 'avatar')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'avatar_url' => $user->avatar_url,
                    ];
                });

            return response()->json(['success' => true, 'data' => $users]);
        })->name('users.mention-suggestions');

        // Get users by department (for escalation modal)
        Route::get('/departments/{department}/users', function (\App\Models\Department $department) {
            $users = $department->users()->active()
                ->get(['id', 'name', 'username', 'designation'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'role_name' => $user->getFirstRoleName(),
                    ];
                });

            return response()->json(['success' => true, 'data' => $users]);
        })->name('department.users');

        // Get notification unread count
        Route::get('/notifications/unread-count', function () {
            $count = Auth::user()->unreadNotifications()->count();
            return response()->json(['count' => $count]);
        })->name('notifications.unread-count');

        // Get latest notifications for dropdown
        Route::get('/notifications/latest', function () {
            $notifications = Auth::user()->notifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($n) {
                    $type = $n->data['type'] ?? 'general';
                    return [
                        'id' => $n->id,
                        'title' => $n->data['title'] ?? 'Notification',
                        'message' => $n->data['message'] ?? '',
                        'type' => $type,
                        'icon' => match($type) {
                            'new_incident' => 'fa-exclamation-triangle',
                            'incident_assigned' => 'fa-user-plus',
                            'incident_escalated' => 'fa-arrow-up',
                            'incident_resolved' => 'fa-check-circle',
                            'incident_closed' => 'fa-lock',
                            'new_comment' => 'fa-comment',
                            'mentioned' => 'fa-at',
                            default => 'fa-bell',
                        },
                        'color' => match($type) {
                            'new_incident', 'incident_escalated' => '#EF4444',
                            'incident_assigned' => '#3B82F6',
                            'incident_resolved' => '#10B981',
                            'incident_closed' => '#6B7280',
                            'new_comment' => '#8B5CF6',
                            'mentioned' => '#EC4899',
                            default => '#6B7280',
                        },
                        'time' => $n->created_at->diffForHumans(),
                        'read' => !is_null($n->read_at),
                        'url' => $n->data['url'] ?? route('notifications.index'),
                    ];
                });

            return response()->json($notifications);
        })->name('notifications.latest');

    });

});




//for FCM token service

// routes/api.php or routes/web.php

Route::middleware('auth')->group(function () {
    // Update FCM token
    Route::put('/api/v1/user/fcm-token', function (Request $request) {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->fcm_token,
            'device_type' => $request->device_type ?? 'web',
        ]);

        // Log
        Log::info('FCM token updated', [
            'user_id' => $user->id,
            'device_type' => $request->device_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully',
        ]);
    })->name('fcm.token.update');
});




// Test FCM Configuration
Route::get('/test-fcm-config', function () {
    $credentials = config('firebase.projects.app.credentials');

    return response()->json([
        'credentials_path' => $credentials,
        'credentials_exists' => file_exists($credentials),
        'credentials_readable' => is_readable($credentials),
        'sender_id' => env('FCM_SENDER_ID'),
    ]);
});

// Test FCM Send
Route::get('/test-fcm-send', function (FCMService $fcmService) {
    // Get a user with FCM token
    $user = \App\Models\User::whereNotNull('fcm_token')->first();

    if (!$user || !$user->fcm_token) {
        return response()->json([
            'error' => 'No user with FCM token found.',
            'help' => 'Visit /test-fcm-token?token=YOUR_DEVICE_TOKEN to add one',
        ]);
    }

    $result = $fcmService->sendToDevice(
        $user->fcm_token,
        '🧪 Test Notification',
        'This is a test from IRMSystem at ' . now()->format('H:i:s'),
        [
            'type' => 'test',
            'incident_id' => '1',
            'click_action' => url('/'),
        ]
    );

    return response()->json([
        'success' => $result,
        'user' => $user->name,
        'token_preview' => substr($user->fcm_token, 0, 30) . '...',
    ]);
});

// Add test FCM token
Route::get('/test-fcm-token', function (Request $request) {
    $token = $request->get('token');

    if (!$token) {
        return response()->json([
            'error' => 'Please provide a token parameter',
            'example' => '/test-fcm-token?token=YOUR_FCM_TOKEN',
        ]);
    }

    $user = \App\Models\User::first();

    if ($user) {
        $user->update(['fcm_token' => $token]);
        return response()->json([
            'success' => true,
            'message' => 'FCM token added to ' . $user->name,
            'token' => substr($token, 0, 30) . '...',
        ]);
    }

    return response()->json(['error' => 'No user found']);
});

// View users with FCM tokens
Route::get('/test-fcm-users', function () {
    $users = \App\Models\User::whereNotNull('fcm_token')
        ->get(['id', 'name', 'email', 'fcm_token'])
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_token' => !empty($user->fcm_token),
                'token_preview' => $user->fcm_token ? substr($user->fcm_token, 0, 30) . '...' : null,
            ];
        });

    return response()->json($users);
});
