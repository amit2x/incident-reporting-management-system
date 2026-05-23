<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incident;
    public $changes;

    public function __construct(Incident $incident, array $changes = [])
    {
        $this->incident = $incident;
        $this->changes = $changes;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('incident.' . $this->incident->id),
            new PrivateChannel('department.' . $this->incident->department_id),
        ];

        if ($this->incident->assigned_to) {
            $channels[] = new PrivateChannel('user.' . $this->incident->assigned_to);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'status' => $this->incident->status,
            'changes' => $this->changes,
            'updated_at' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.updated';
    }
}