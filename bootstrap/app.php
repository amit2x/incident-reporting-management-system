<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
     ->withMiddleware(function (Middleware $middleware) {

        // 1. Application's Global HTTP Middleware Stack
        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // 2. Web Middleware Group Updates
        $middleware->web(append: [
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\LogUserActivity::class,
        ]);

        // 3. API Middleware Group Updates
        $middleware->api(append: [
            \App\Http\Middleware\RateLimitMiddleware::class,
        ]);

        // 4. Middleware Aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'user.status' => \App\Http\Middleware\CheckUserStatus::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'captcha' => \App\Http\Middleware\CaptchaMiddleware::class,

        ]);

        // 5. rate limiting for login
        $middleware->throttleWithRedis();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // ==========================================
        // QUEUE PROCESSING (Windows/Local Development)
        // ==========================================

        // Process queue jobs every minute (for Windows without Supervisor)
        $schedule->command('queue:process-scheduled --max-jobs=50')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/queue-scheduler.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Queue scheduler failed');
            });

        // Alternative: Process specific queues at different intervals
        $schedule->command('queue:work database --queue=notifications --max-jobs=20 --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/queue-notifications.log'));

        $schedule->command('queue:work database --queue=emails --max-jobs=20 --stop-when-empty')
            ->everyTwoMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/queue-emails.log'));

        $schedule->command('queue:work database --queue=reports --max-jobs=10 --stop-when-empty')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/queue-reports.log'));

        // ==========================================
        // KPI REPORT GENERATION
        // ==========================================

        // Generate daily KPI reports at midnight
        $schedule->job(new \App\Jobs\GenerateKpiReport('daily'))
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Daily KPI report generation failed');
            });

        // Generate weekly KPI reports every Sunday at 00:10
        $schedule->job(new \App\Jobs\GenerateKpiReport('weekly'))
            ->weeklyOn(0, '00:10')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Weekly KPI report generation failed');
            });

        // Generate monthly KPI reports on 1st of every month at 00:15
        $schedule->job(new \App\Jobs\GenerateKpiReport('monthly'))
            ->monthlyOn(1, '00:15')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Monthly KPI report generation failed');
            });

        // ==========================================
        // SLA MONITORING
        // ==========================================

        // Check for SLA breaches every 30 minutes
        $schedule->command('incidents:check-sla')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/sla-check.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('SLA check failed');
            });

        // Send SLA breach notifications every 15 minutes for critical incidents
        $schedule->command('incidents:check-sla --critical-only')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/sla-critical-check.log'));

        // ==========================================
        // DATA CLEANUP TASKS
        // ==========================================

        // Clean up old notifications (older than 30 days) - Daily at 2:00 AM
        $schedule->command('notifications:clean --days=30')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup-notifications.log'));

        // Clean up old activity logs (older than 90 days) - Daily at 3:00 AM
        $schedule->command('activity:clean --days=90')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup-activity.log'));

        // Clean up temporary files - Daily at 4:00 AM
        $schedule->command('files:clean-temp')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup-files.log'));

        // Clean up failed jobs older than 7 days - Daily at 2:30 AM
        $schedule->command('queue:flush --hours=168')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup-failed-jobs.log'));

        // ==========================================
        // SYSTEM HEALTH CHECKS
        // ==========================================

        // Check system health every hour
        $schedule->command('system:health-check')
            ->hourly()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/health-check.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('System health check failed');
            });

        // Backup database daily at 1:00 AM (only in production)
        if (app()->environment('production')) {
            $schedule->command('backup:run --only-db')
                ->dailyAt('01:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/backup.log'))
                ->onFailure(function () {
                    \Illuminate\Support\Facades\Log::error('Database backup failed');
                });
        }

        // ==========================================
        // CACHE OPTIMIZATION (Production Only)
        // ==========================================

        if (app()->environment('production')) {
            // Clear and rebuild cache weekly
            $schedule->command('optimize:clear')
                ->weeklyOn(1, '03:00')
                ->withoutOverlapping()
                ->thenPing('https://healthchecks.io/ping/your-uuid'); // Optional health check ping
        }
    })
    ->create();
