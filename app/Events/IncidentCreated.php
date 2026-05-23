<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incident;

    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('incidents'),
            new PrivateChannel('department.' . $this->incident->department_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'incident_id' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'severity' => $this->incident->severity,
            'priority' => $this->incident->priority,
            'department' => $this->incident->department->name,
            'category' => $this->incident->category->name,
            'reporter' => $this->incident->is_anonymous ? 'Anonymous' : $this->incident->reporter->name,
            'created_at' => $this->incident->created_at->toISOString(),
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.created';
    }
}