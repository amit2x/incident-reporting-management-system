@extends('emails.layouts.samanvay')

@section('slot')
<div class="status-banner" style="background-color: {{ $color ?? '#3B82F6' }};">
    <span class="icon">🔄</span>
    <span>{{ $label ?? 'Incident Reopened' }} – {{ $incident->incident_id ?? '' }}</span>
</div>

<p class="greeting">{{ $greeting ?? 'Hello,' }}</p>

<p style="margin: 8px 0 16px;">{{ $body ?? 'The incident has been reopened. Please review and take appropriate action.' }}</p>

@include('emails.partials.incident-card', [
    'incident' => $incident,
    'extraFields' => $extraFields ?? [],
])

@include('emails.partials.button', [
    'url' => $actionUrl ?? '#',
    'text' => 'View Incident',
    'type' => 'primary',
])

<div style="text-align: center; margin-top: 8px;">
    <a href="{{ route('dashboard') }}" style="color: #64748b; font-size: 13px; text-decoration: underline;">
        Open Dashboard
    </a>
</div>

@include('emails.partials.contact-support')
@endsection