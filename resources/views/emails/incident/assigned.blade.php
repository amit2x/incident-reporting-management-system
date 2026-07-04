@extends('emails.layouts.samanvay')

@section('slot')
<div class="status-banner" style="background-color: {{ $color }};">
    <span class="icon">📋</span>
    <span>{{ $label }} – {{ $incident->incident_id }}</span>
</div>

<p class="greeting">{{ $greeting }}</p>
<p style="margin: 8px 0 16px;">{{ $body }}</p>

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

        <!-- Priority -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Priority
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                <!-- Universal Inline Badge Structure -->
                <span
                    style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; background-color: #f1f5f9; color: #334155;">
                    {{ ucfirst($incident->priority) }}
                </span>
            </td>
        </tr>

        <!-- Severity -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Severity
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ ucfirst($incident->severity) }}
            </td>
        </tr>

        <!-- Category -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Category
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->category?->name ?? 'N/A' }}
            </td>
        </tr>

        <!-- Department -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Department
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->department?->name ?? 'N/A' }}
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

        <!-- Assigned To -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Assigned To
            </td>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a; font-weight: 600;">
                {{ $assignee?->name ?? 'N/A' }}
            </td>
        </tr>

        <!-- Reported By -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Reported By
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                {{ $incident->reporter?->name ?? 'N/A' }}
            </td>
        </tr>

        <!-- Created Time -->
        <tr>
            <td valign="top"
                style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #64748b;">
                Created
            </td>
            <td valign="top" style="padding: 10px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #475569;">
                {{ $incident->created_at->format('d M Y, H:i') }}
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

<div style="text-align: center; margin-top: 8px;">
    <a href="{{ route('dashboard') }}" style="color: #64748b; font-size: 13px; text-decoration: underline;">
        Open Dashboard
    </a>
</div>
@endsection
