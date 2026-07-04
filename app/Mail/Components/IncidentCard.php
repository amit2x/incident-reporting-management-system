<?php

namespace App\View\Components\Email;

use App\Models\Incident;
use Illuminate\View\Component;

class IncidentCard extends Component
{
    public Incident $incident;
    public array $extraFields;
    public bool $showDescription;
    public bool $showAllFields;

    public function __construct(
        Incident $incident, 
        array $extraFields = [], 
        bool $showDescription = true,
        bool $showAllFields = false
    ) {
        $this->incident = $incident;
        $this->extraFields = $extraFields;
        $this->showDescription = $showDescription;
        $this->showAllFields = $showAllFields;
    }

    public function render()
    {
        return view('emails.partials.incident-card');
    }

    /**
     * Get priority badge class.
     */
    public function getPriorityBadge(): string
    {
        return match ($this->incident->priority) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'medium' => 'badge-medium',
            'low' => 'badge-low',
            default => '',
        };
    }

    /**
     * Get priority color.
     */
    public function getPriorityColor(): ?string
    {
        return match ($this->incident->priority) {
            'critical' => '#DC2626',
            'high' => '#EA580C',
            'medium' => '#D97706',
            'low' => '#059669',
            default => null,
        };
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadge(): string
    {
        return match ($this->incident->status) {
            'open' => 'badge-open',
            'acknowledged' => 'badge-acknowledged',
            'in_progress' => 'badge-in_progress',
            'escalated' => 'badge-escalated',
            'resolved' => 'badge-resolved',
            'closed' => 'badge-closed',
            default => '',
        };
    }

    /**
     * Check if field should be displayed.
     */
    public function shouldShowField(string $field): bool
    {
        if ($this->showAllFields) {
            return true;
        }

        $alwaysShow = ['incident_id', 'priority', 'severity', 'status', 'category', 'department'];
        return in_array($field, $alwaysShow);
    }

    /**
     * Get formatted value for a field.
     */
    public function getFieldValue(string $field): string
    {
        return match ($field) {
            'incident_id' => $this->incident->incident_id,
            'priority' => ucfirst($this->incident->priority),
            'severity' => ucfirst($this->incident->severity),
            'status' => ucfirst(str_replace('_', ' ', $this->incident->status)),
            'category' => $this->incident->category?->name ?? 'N/A',
            'department' => $this->incident->department?->name ?? 'N/A',
            'location' => $this->incident->location ?? 'N/A',
            'reported_by' => $this->incident->reporter?->name ?? 'N/A',
            'assigned_to' => $this->incident->assignedTo?->name ?? 'Unassigned',
            'escalated_to' => $this->incident->escalatedTo?->name ?? 'N/A',
            'created_at' => $this->incident->created_at->format('d M Y, H:i'),
            'updated_at' => $this->incident->updated_at->format('d M Y, H:i'),
            'resolved_at' => $this->incident->resolved_at?->format('d M Y, H:i') ?? 'N/A',
            'closed_at' => $this->incident->closed_at?->format('d M Y, H:i') ?? 'N/A',
            'sla_due_at' => $this->incident->sla_due_at?->format('d M Y, H:i') ?? 'N/A',
            default => 'N/A',
        };
    }

    /**
     * Get fields to display.
     */
    public function getFields(): array
    {
        $fields = [
            'incident_id' => 'Incident ID',
            'priority' => 'Priority',
            'severity' => 'Severity',
            'status' => 'Status',
            'category' => 'Category',
            'department' => 'Department',
        ];

        if ($this->showAllFields) {
            $fields = array_merge($fields, [
                'location' => 'Location',
                'reported_by' => 'Reported By',
                'assigned_to' => 'Assigned To',
                'escalated_to' => 'Escalated To',
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date',
                'resolved_at' => 'Resolved Date',
                'closed_at' => 'Closed Date',
                'sla_due_at' => 'SLA Due Date',
            ]);
        }

        return $fields;
    }
}