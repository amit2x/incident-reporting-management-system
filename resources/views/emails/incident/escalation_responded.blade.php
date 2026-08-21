@extends('emails.layouts.samanvay')

@section('slot')
<div class="status-banner" style="background-color: {{ $color ?? ($responseType === 'accept' ? '#10B981' : ($responseType === 'reject' ? '#DC2626' : '#F59E0B')) }};">
    <span class="icon">
        @if($responseType === 'accept') ✅
        @elseif($responseType === 'reject') ❌
        @elseif($responseType === 'return') ↩️
        @else 📌
        @endif
    </span>
    <span>{{ $label }} – {{ $incident->incident_id }}</span>
</div>

<p class="greeting">{{ $greeting ?? 'Hello,' }}</p>

<p style="margin: 8px 0 16px; white-space: pre-line;">
    {{ $body ?? 'The escalation has been responded to.' }}
</p>

{{-- Incident Details Card --}}
<div class="card" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div class="card-title" style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #f1f5f9;">
        📋 Incident Details
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout: fixed; border-collapse: collapse;">
        <tr>
            <td width="35%" valign="top" style="padding: 10px 0; font-size: 13px; font-weight: 600; color: #64748b;">
                Incident ID
            </td>
            <td width="65%" valign="top" style="padding: 10px 0; font-size: 13px; color: #0f172a; font-weight: 700;">
                {{ $incident->incident_id }}
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Title
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->title ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Priority
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                @php
                $pLower = strtolower($incident->priority ?? '');
                $pBg = '#f1f5f9'; $pColor = '#334155';
                if (in_array($pLower, ['high', 'critical', 'emergency'])) { $pBg = '#fef2f2'; $pColor = '#ef4444'; }
                elseif (in_array($pLower, ['medium', 'warning'])) { $pBg = '#fffbeb'; $pColor = '#d97706'; }
                elseif (in_array($pLower, ['low', 'info'])) { $pBg = '#f0fdf4'; $pColor = '#16a34a'; }
                @endphp
                <span style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; background-color: {{ $pBg }}; color: {{ $pColor }}; line-height: 1;">
                    {{ ucfirst($incident->priority) }}
                </span>
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Severity
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ ucfirst($incident->severity) }}
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Category
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->category?->name ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Department
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->department?->name ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Location
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->location ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Status
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                @php
                $sLower = strtolower($incident->status ?? '');
                $sBg = '#eff6ff'; $sColor = '#2563eb';
                if (in_array($sLower, ['closed', 'resolved', 'fixed'])) { $sBg = '#f0fdf4'; $sColor = '#16a34a'; }
                elseif (in_array($sLower, ['pending', 'hold'])) { $sBg = '#fffbeb'; $sColor = '#d97706'; }
                @endphp
                <span style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; background-color: {{ $sBg }}; color: {{ $sColor }}; line-height: 1;">
                    {{ ucfirst($incident->status) }}
                </span>
            </td>
        </tr>
        @if(isset($extraFields))
            @foreach($extraFields as $label => $value)
                @if($value)
                <tr>
                    <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                        {{ $label }}
                    </td>
                    <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                        {{ $value }}
                    </td>
                </tr>
                @endif
            @endforeach
        @endif
    </table>
</div>

<div style="text-align: center; margin: 24px 0 16px;">
    <a href="{{ $actionUrl }}" class="btn" style="display: inline-block; padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; background-color: #0d47a1; color: #ffffff;">
        View Incident
    </a>
</div>

<div style="text-align: center; margin-top: 8px;">
    <a href="{{ route('dashboard') }}" style="color: #64748b; font-size: 13px; text-decoration: underline;">
        Open Dashboard
    </a>
</div>
@endsection