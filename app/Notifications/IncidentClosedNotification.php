<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentClosedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class IncidentClosedNotification extends Notification implements ShouldQueue
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
            $actor = $this->getActorFromLogs('closed');
        }
        
        if (!$actor && $notifiable instanceof User) {
            $actor = $notifiable;
        }
        
        if (!$actor) {
            $actor = User::role('admin')->first() ?? User::first();
        }
        
        if (!$actor) {
            Log::warning('No actor found for closed notification', [
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

        $recipientName = $notifiable instanceof User ? $notifiable->name : 'User';

        $mail = (new IncidentClosedMail($this->incident, $actor))
            ->setRecipientName($recipientName)
            ->to($notifiable->email);
        
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->getActor();
        
        return [
            'type' => 'incident_closed',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'closed_by' => $actor?->name ?? 'System',
            'closed_by_id' => $actor?->id,
            'message' => "Incident #{$this->incident->incident_id} has been closed",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        $statusAction = $this->incident->statusActions()
            ->where('action', 'closed')
            ->latest('acted_at')
            ->first();
            
        if ($statusAction && $statusAction->user_id) {
            return User::find($statusAction->user_id);
        }

        if ($this->incident->assigned_to) {
            return User::find($this->incident->assigned_to);
        }

        if ($this->incident->reported_by) {
            return User::find($this->incident->reported_by);
        }

        return null;
    }

    protected function getActorFromLogs(string $action): ?User
    {
        $log = $this->incident->logs()
            ->where('action', $action)
            ->orWhere('action', 'status_changed')
            ->where('new_values', 'like', '%"' . $action . '"%')
            ->whereNotNull('user_id')
            ->latest('created_at')
            ->first();

        if ($log && $log->user_id) {
            return User::find($log->user_id);
        }

        return null;
    }
}