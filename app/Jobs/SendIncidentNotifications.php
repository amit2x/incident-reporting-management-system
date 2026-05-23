<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Notifications\NewIncidentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendIncidentNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $incident;
    public $tries = 3;
    public $timeout = 120;

    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
        $this->onQueue('notifications');
    }

    public function handle()
    {
        $incident = $this->incident->load('department', 'category', 'reporter');
        
        // Get HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod) {
            Notification::send($hod, new NewIncidentNotification($incident));
        }
        
        // Get supervisors
        $supervisors = $incident->department->getSupervisors();
        foreach ($supervisors as $supervisor) {
            if ($supervisor->id !== $hod?->id) {
                Notification::send($supervisor, new NewIncidentNotification($incident));
            }
        }
        
        // Notify admins for critical incidents
        if (in_array($incident->severity, ['critical', 'high'])) {
            $admins = \App\Models\User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                Notification::send($admin, new NewIncidentNotification($incident));
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('Failed to send incident notifications', [
            'incident_id' => $this->incident->id,
            'error' => $exception->getMessage(),
        ]);
    }
}