<x-email.status-banner 
    color="{{ $statusColor }}" 
    label="{{ $statusLabel }}" 
    incidentId="{{ $incident->incident_id }}"
    type="{{ $notificationType }}"
/>

<p class="greeting">{{ $greeting }}</p>

<p style="margin: 8px 0 16px;">{{ $body }}</p>

<div class="card" style="background: #ecfeff; border-left: 4px solid #0891B2;">
    <div style="font-size: 13px; color: #0e7490; font-weight: 500;">💬 New Comment</div>
    <div style="font-size: 13px; color: #1e293b; margin-top: 4px;">
        {{ $extraFields['Comment'] ?? 'N/A' }}
    </div>
    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
        – {{ $extraFields['Comment By'] ?? 'N/A' }}
    </div>
</div>

<x-email.incident-card :incident="$incident" :extraFields="[]" />

<x-email.button url="{{ $actionUrl }}" text="View Incident" type="primary" />