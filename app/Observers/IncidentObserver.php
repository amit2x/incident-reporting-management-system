<?php

namespace App\Observers;

use App\Models\Incident;
use App\Services\NotificationService;
use App\Events\IncidentCreated;
use App\Events\IncidentUpdated;
use App\Jobs\SendIncidentNotifications;
use Illuminate\Support\Facades\Log;

class IncidentObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function created(Incident $incident): void
    {
        // Dispatch events
        event(new IncidentCreated($incident));
        
        // Queue notifications
        SendIncidentNotifications::dispatch($incident);
        
        // Log
        Log::info('Incident created', [
            'incident_id' => $incident->incident_id,
            'reported_by' => $incident->reported_by,
            'department_id' => $incident->department_id,
        ]);
    }

    public function updated(Incident $incident): void
    {
        $changes = $incident->getChanges();
        
        // Remove timestamp changes
        unset($changes['updated_at'], $changes['created_at']);
        
        // Only dispatch if there are actual changes
        if (!empty($changes)) {
            event(new IncidentUpdated($incident, $changes));
            
            // Log status changes
            if ($incident->wasChanged('status')) {
                Log::info('Incident status changed', [
                    'incident_id' => $incident->incident_id,
                    'old_status' => $incident->getOriginal('status'),
                    'new_status' => $incident->status,
                    'changed_by' => auth()->id(),
                ]);
            }
            
            // Check SLA breach
            if ($incident->wasChanged('status') && 
                in_array($incident->status, ['open', 'acknowledged', 'in_progress']) &&
                $incident->sla_due_at && $incident->sla_due_at->isPast()) {
                
                $incident->increment('sla_breach_count');
            }
        }
    }

    public function deleted(Incident $incident): void
    {
        Log::warning('Incident deleted', [
            'incident_id' => $incident->incident_id,
            'deleted_by' => auth()->id(),
        ]);
    }

    public function restored(Incident $incident): void
    {
        Log::info('Incident restored', [
            'incident_id' => $incident->incident_id,
            'restored_by' => auth()->id(),
        ]);
    }

    public function forceDeleted(Incident $incident): void
    {
        // Delete associated media files
        foreach ($incident->media as $media) {
            \Storage::delete($media->file_path);
            if ($media->thumbnail_path) {
                \Storage::delete($media->thumbnail_path);
            }
            $media->delete();
        }
        
        Log::warning('Incident permanently deleted', [
            'incident_id' => $incident->incident_id,
            'deleted_by' => auth()->id(),
        ]);
    }
}