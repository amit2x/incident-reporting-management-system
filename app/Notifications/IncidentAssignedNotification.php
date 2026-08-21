<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentAssignedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class IncidentAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();
        
        if (!$actor) {
            $actor = $this->getActorFromAssignment();
        }
        
        if (!$actor && $notifiable instanceof User) {
            $actor = $notifiable;
        }
        
        if (!$actor) {
            $actor = User::role('admin')->first() ?? User::first();
        }
        
        if (!$actor) {
            Log::warning('No actor found for assigned notification', [
                'incident_id' => $this->incident->id,
            ]);
            
            $actor = User::firstOrCreate(
                ['email' => 'system@' . parse_url(config('app.url'), PHP_URL_HOST)],
                [
                    'name' => 'System',
                    'username' => 'system',
                    'password' => bcrypt(str_random(32)),
                ]
            );
        }

        // Get recipient name
        $recipientName = $notifiable instanceof User ? $notifiable->name : 'User';

        // Create mail with personalized greeting
        $mail = (new IncidentAssignedMail($this->incident, $actor, $notifiable))
            ->setRecipientName($recipientName)
            ->to($notifiable->email);
        
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->getActor();
        
        return [
            'type' => 'incident_assigned',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'assigned_by' => $actor?->name ?? 'System',
            'assigned_by_id' => $actor?->id,
            'assigned_to' => $notifiable instanceof User ? $notifiable->name : null,
            'message' => "Incident #{$this->incident->incident_id} has been assigned to you",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        $statusAction = $this->incident->statusActions()
            ->where('action', 'assigned')
            ->latest('acted_at')
            ->first();
            
        if ($statusAction && $statusAction->user_id) {
            return User::find($statusAction->user_id);
        }

        $statusAction = $this->incident->statusActions()
            ->where('action', 'reassigned')
            ->latest('acted_at')
            ->first();
            
        if ($statusAction && $statusAction->user_id) {
            return User::find($statusAction->user_id);
        }

        return null;
    }

    protected function getActorFromAssignment(): ?User
    {
        $lastAssignment = $this->incident->assignments()->latest()->first();
        if ($lastAssignment && $lastAssignment->assigned_by) {
            return User::find($lastAssignment->assigned_by);
        }

        $assignment = $this->incident->assignments()
            ->whereNotNull('assigned_by')
            ->latest()
            ->first();
            
        if ($assignment && $assignment->assigned_by) {
            return User::find($assignment->assigned_by);
        }

        return null;
    }
}