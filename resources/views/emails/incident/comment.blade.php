@extends('emails.layouts.samanvay')

@section('slot')
<div class="status-banner" style="background-color: {{ $color }};">
    <span class="icon">💬</span>
    <span>{{ $label }} – {{ $incident->incident_id }}</span>
</div>

<p class="greeting">{{ $greeting }}</p>
<p style="margin: 8px 0 16px;">{{ $body }}</p>

<div style="background: #ecfeff; border-left: 4px solid #0891B2; border-radius: 8px; padding: 16px; margin: 16px 0;">
    <div style="font-size: 13px; color: #0e7490; font-weight: 500;">💬 New Comment</div>
    <div style="font-size: 13px; color: #1e293b; margin-top: 4px;">
        {{ $extraFields['Comment'] ?? 'N/A' }}
    </div>
    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
        – {{ $extraFields['Comment By'] ?? 'N/A' }}
    </div>
</div>

<!-- Main Card Container -->
<div class="card"
    style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    <!-- Card Title -->
    <div class="card-title"
        style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #f1f5f9;">
        📋 Incident Details
    </div>

    <!-- Data Table Structure (Fixes rejection and breaking) -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0"
        style="table-layout: fixed; border-collapse: collapse;">

        <!-- Incident ID -->
        <tr>
            <td width="35%" valign="top" style="padding: 10px 0; font-size: 13px; font-weight: 600; color: #64748b;">
                Incident ID
            </td>
            <td width="65%" valign="top" style="padding: 10px 0; font-size: 13px; color: #0f172a; font-weight: 700;">
                {{ $incident->incident_id }}
            </td>
        </tr>

        <!-- Title -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Title
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->title ?? 'N/A' }}
            </td>
        </tr>

        <!-- Location -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Location
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->location ?? 'N/A' }}
            </td>
        </tr>

        <!-- Dynamic Priority Badge Row -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Priority
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                @php
                $pLower = strtolower($incident->priority ?? '');
                $pBg = '#f1f5f9'; $pColor = '#334155'; // Default fallback
                if (in_array($pLower, ['high', 'critical', 'emergency'])) { $pBg = '#fef2f2'; $pColor = '#ef4444'; }
                elseif (in_array($pLower, ['medium', 'warning'])) { $pBg = '#fffbeb'; $pColor = '#d97706'; }
                elseif (in_array($pLower, ['low', 'info'])) { $pBg = '#f0fdf4'; $pColor = '#16a34a'; }
                @endphp
                <span
                    style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; background-color: {{ $pBg }}; color: {{ $pColor }}; line-height: 1;">
                    {{ ucfirst($incident->priority) }}
                </span>
            </td>
        </tr>

        <!-- Dynamic Status Badge Row -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Status
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                @php
                $sLower = strtolower($incident->status ?? '');
                $sBg = '#f1f5f9'; $sColor = '#334155'; // Default fallback
                if (in_array($sLower, ['open', 'new', 'active', 'escalated'])) { $sBg = '#eff6ff'; $sColor = '#2563eb';
                }
                elseif (in_array($sLower, ['pending', 'in progress', 'progress'])) { $sBg = '#fffbeb'; $sColor =
                '#d97706'; }
                elseif (in_array($sLower, ['closed', 'resolved', 'fixed'])) { $sBg = '#f0fdf4'; $sColor = '#16a34a'; }
                @endphp
                <span
                    style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; background-color: {{ $sBg }}; color: {{ $sColor }}; line-height: 1;">
                    {{ ucfirst($incident->status) }}
                </span>
            </td>
        </tr>

    </table>
</div>


<div style="text-align: center; margin: 24px 0 16px;">
    <a href="{{ $actionUrl }}" class="btn"
        style="background:#0d47a1;color:#fff;text-decoration:none;padding:12px 24px;display:inline-block;border-radius:6px;">
        View Incident
    </a>
</div>
@endsection
