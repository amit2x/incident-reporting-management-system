<x-email.status-banner 
    color="{{ $statusColor }}" 
    label="{{ $statusLabel }}" 
    incidentId="{{ $incident->incident_id }}"
    type="{{ $notificationType }}"
/>

<p class="greeting">{{ $greeting }}</p>

<p style="margin: 8px 0 16px;">{{ $body }}</p>

<x-email.incident-card :incident="$incident" :extraFields="$extraFields" />

<x-email.button url="{{ $actionUrl }}" text="View Incident" type="primary" />

<div style="text-align: center; margin-top: 8px;">
    <a href="{{ route('dashboard') }}" style="color: #64748b; font-size: 13px; text-decoration: underline;">
        Open Dashboard
    </a>
</div>