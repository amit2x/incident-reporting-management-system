<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentCreatedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewIncidentNotification extends Notification implements ShouldQueue
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

 // public function toMail(object $notifiable): MailMessage
    // {
    //     $url = route('incidents.show', $this->incident->id);

    //     return (new MailMessage)
    //         ->subject("🚨 New Incident Reported: #{$this->incident->incident_id}")
    //         ->greeting("Hello {$notifiable->name},")
    //         ->line("A new incident has been reported that requires your attention.")
    //         ->line("**Incident ID:** {$this->incident->incident_id}")
    //         ->line("**Title:** {$this->incident->title}")
    //         ->line("**Severity:** " . ucfirst($this->incident->severity))
    //         ->line("**Priority:** " . ucfirst($this->incident->priority))
    //         ->line("**Department:** {$this->incident->department->name}")
    //         ->line("**Category:** {$this->incident->category->name}")
    //         ->line("**Reported By:** " . ($this->incident->is_anonymous ? 'Anonymous' : $this->incident->reporter->name))
    //         ->line("**Location:** " . ($this->incident->location ?? 'Not specified'))
    //         ->when($this->incident->sla_due_at, function ($mail) {
    //             return $mail->line("**SLA Due:** {$this->incident->sla_due_at->format('d M Y, H:i')}");
    //         })
    //         ->action('View Incident', $url)
    //         ->line('Please take appropriate action at your earliest convenience.')
    //         // ->salutation('Regards,<br>IRMS Notification System');
    //                     ->salutation("Regards,\nSAMANVAY");

    // }
    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();
        
        // If actor is null, use the notifiable user
        if (!$actor && $notifiable instanceof User) {
            $actor = $notifiable;
        }
        
        // Ultimate fallback
        if (!$actor) {
            $actor = User::role('admin')->first() ?? User::first();
        }
        
        // If still null, create a temporary system user
        if (!$actor) {
            Log::warning('No actor found for new incident notification', [
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
        $mail = (new IncidentCreatedMail($this->incident, $actor))
            ->setRecipientName($recipientName)
            ->to($notifiable->email);
        
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->getActor();
        
        return [
            'type' => 'new_incident',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'severity' => $this->incident->severity,
            'priority' => $this->incident->priority,
            'status' => $this->incident->status,
            'department' => $this->incident->department?->name ?? 'N/A',
            'category' => $this->incident->category?->name ?? 'N/A',
            'reported_by' => $this->incident->reporter?->name ?? 'Anonymous',
            'is_anonymous' => $this->incident->is_anonymous,
            'message' => "New incident #{$this->incident->incident_id} reported: {$this->incident->title}",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        // Strategy 1: Check authenticated user
        if (auth()->check()) {
            return auth()->user();
        }

        // Strategy 2: Check reporter
        if ($this->incident->reported_by) {
            return $this->incident->reporter;
        }

        // Strategy 3: Check status_actions
        $statusAction = $this->incident->statusActions()
            ->where('action', 'created')
            ->latest('acted_at')
            ->first();
            
        if ($statusAction && $statusAction->user_id) {
            return User::find($statusAction->user_id);
        }

        // Strategy 4: Check logs
        $log = $this->incident->logs()
            ->where('action', 'created')
            ->whereNotNull('user_id')
            ->latest('created_at')
            ->first();
            
        if ($log && $log->user_id) {
            return User::find($log->user_id);
        }

        return null;
    }
}
