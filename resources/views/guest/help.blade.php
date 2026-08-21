{{-- resources/views/guest/help.blade.php --}}
@extends('layouts.app')

@section('title', 'Help & User Manual - SAMANVAY')

@php
    $user = Auth::user();
    $isAdmin = $user && ($user->isAdmin() || $user->isSuperAdmin());
    $isHOD = $user && $user->isHOD();
    $isSupervisor = $user && $user->isSupervisor();
    $isStaff = $user && $user->hasRole('staff');
    $isViewer = $user && $user->hasRole('viewer');
@endphp

@push('styles')
<style>
    .help-container {
        margin: 0 auto;
        padding: 40px 20px;
    }
    .help-header {
        text-align: center;
        padding: 40px 20px;
        margin-bottom: 32px;
        background: linear-gradient(135deg, #0d47a1 0%, #1a56db 100%);
        border-radius: 20px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    .help-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .help-header h2 {
        color: white;
        font-weight: 800;
        margin-bottom: 8px;
        font-size: 2rem;
    }
    .help-header p {
        color: rgba(255,255,255,0.85);
        margin: 0;
        font-size: 1.1rem;
    }
    .help-header .version-badge {
        display: inline-block;
        background: rgba(255,255,255,0.15);
        padding: 4px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        margin-top: 12px;
        color: rgba(255,255,255,0.7);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .help-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.2s ease;
    }
    .help-section:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .help-section h5 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #1f2937;
    }
    .help-section h5 .step-num {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #1a56db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .help-section h5 .step-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f3f4f6;
        color: #1a56db;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        flex-shrink: 0;
    }
    .help-section ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    .help-section ul li {
        margin-bottom: 8px;
        font-size: 0.875rem;
        color: #4b5563;
        line-height: 1.6;
    }
    .help-section ul li:last-child {
        margin-bottom: 0;
    }
    .help-section ul li strong {
        color: #1f2937;
    }
    .help-tip {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        border-radius: 0 8px 8px 0;
        padding: 12px 16px;
        font-size: 0.8125rem;
        color: #92400e;
        margin-top: 12px;
    }
    .help-tip i {
        margin-right: 6px;
        color: #d97706;
    }
    .help-note {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
        border-radius: 0 8px 8px 0;
        padding: 12px 16px;
        font-size: 0.8125rem;
        color: #1e40af;
        margin-top: 12px;
    }
    .help-note i {
        margin-right: 6px;
    }
    .help-warning {
        background: #fef2f2;
        border-left: 4px solid #dc2626;
        border-radius: 0 8px 8px 0;
        padding: 12px 16px;
        font-size: 0.8125rem;
        color: #991b1b;
        margin-top: 12px;
    }
    .help-warning i {
        margin-right: 6px;
        color: #dc2626;
    }
    .status-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8fafc;
    }
    .status-table td {
        font-size: 0.8125rem;
        vertical-align: middle;
    }
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .role-badge.admin { background: #dbeafe; color: #1e40af; }
    .role-badge.hod { background: #d1fae5; color: #065f46; }
    .role-badge.supervisor { background: #fef3c7; color: #92400e; }
    .role-badge.staff { background: #e0e7ff; color: #3730a3; }
    .role-badge.viewer { background: #f3f4f6; color: #374151; }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    .feature-item {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.8125rem;
        color: #374151;
        border: 1px solid #e5e7eb;
    }
    .feature-item .check { color: #10b981; }
    .feature-item .cross { color: #dc2626; }
    .feature-item .lock { color: #6b7280; }
    @media (max-width: 767.98px) {
        .help-container { padding: 20px 12px; }
        .help-header { padding: 24px 16px; }
        .help-header h2 { font-size: 1.5rem; }
        .help-section { padding: 16px; }
        .feature-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="help-container">

    {{-- Header --}}
    <div class="help-header">
        <h2><i class="fas fa-book-open me-2"></i>User Manual</h2>
        <p>Complete guide to using SAMANVAY - Integrated Incident Reporting & Management System</p>
        <div class="version-badge">
            <i class="fas fa-code-branch me-1"></i> Version 2.0
        </div>
    </div>

    {{-- Quick Role Guide --}}
    <div class="help-section">
        <h5>
            <span class="step-icon"><i class="fas fa-user-tag"></i></span>
            Your Role & Permissions
        </h5>
        <p class="text-muted small mb-3">Your role determines what you can see and do in SAMANVAY.</p>
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Role</th>
                        <th>Badge</th>
                        <th>Key Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Viewer</strong></td>
                        <td><span class="role-badge viewer"><i class="fas fa-eye"></i> Viewer</span></td>
                        <td>View incidents in department, read comments</td>
                    </tr>
                    <tr>
                        <td><strong>Staff</strong></td>
                        <td><span class="role-badge staff"><i class="fas fa-user"></i> Staff</span></td>
                        <td>Report, comment, work on assigned incidents</td>
                    </tr>
                    <tr>
                        <td><strong>Supervisor</strong></td>
                        <td><span class="role-badge supervisor"><i class="fas fa-user-check"></i> Supervisor</span></td>
                        <td>Assign, escalate, manage team workload</td>
                    </tr>
                    <tr>
                        <td><strong>HOD</strong></td>
                        <td><span class="role-badge hod"><i class="fas fa-user-tie"></i> HOD</span></td>
                        <td>Full department control, approve, close incidents</td>
                    </tr>
                    <tr>
                        <td><strong>Admin</strong></td>
                        <td><span class="role-badge admin"><i class="fas fa-user-cog"></i> Admin</span></td>
                        <td>Complete system access, user management, settings</td>
                    </tr>
                    <tr>
                        <td><strong>Super Admin</strong></td>
                        <td><span class="role-badge admin"><i class="fas fa-crown"></i> Super Admin</span></td>
                        <td>Full system control, all permissions</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="help-note mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Your current role:</strong> 
            @if($isAdmin) 
                <span class="role-badge admin"><i class="fas fa-crown"></i> Administrator</span>
            @elseif($isHOD)
                <span class="role-badge hod"><i class="fas fa-user-tie"></i> HOD</span>
            @elseif($isSupervisor)
                <span class="role-badge supervisor"><i class="fas fa-user-check"></i> Supervisor</span>
            @elseif($isStaff)
                <span class="role-badge staff"><i class="fas fa-user"></i> Staff</span>
            @elseif($isViewer)
                <span class="role-badge viewer"><i class="fas fa-eye"></i> Viewer</span>
            @else
                <span class="role-badge viewer">Guest</span>
            @endif
        </div>
    </div>

    {{-- Role-Based Feature Matrix --}}
    <div class="help-section">
        <h5>
            <span class="step-icon"><i class="fas fa-th-list"></i></span>
            Feature Availability by Role
        </h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Feature</th>
                        <th class="text-center">Viewer</th>
                        <th class="text-center">Staff</th>
                        <th class="text-center">Supervisor</th>
                        <th class="text-center">HOD</th>
                        <th class="text-center">Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>View Incidents</td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Report Incidents</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Comment & Collaborate</td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Assign Incidents</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Escalate Incidents</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Resolve Incidents</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Close Incidents</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>View Reports & Analytics</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Export Reports</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>User Management</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>System Configuration</td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-danger"><i class="fas fa-times"></i></td>
                        <td class="text-center text-success"><i class="fas fa-check"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Getting Started --}}
    <div class="help-section">
        <h5>
            <span class="step-num">1</span>
            Getting Started with SAMANVAY
        </h5>
        <ul>
            <li><strong>Login:</strong> Use your registered email and password to sign in. Contact your administrator if you don't have credentials.</li>
            <li><strong>Dashboard:</strong> After login, you'll see your dashboard with incident statistics, recent incidents, and quick actions.</li>
            <li><strong>Navigation:</strong> Use the sidebar (desktop) or bottom navigation (mobile) to move between sections.</li>
        </ul>
        <div class="help-note">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Your role determines what you see. Viewers see read-only data, while Staff and above can take actions.
        </div>
    </div>

    {{-- Reporting an Incident --}}
    @if(!$isViewer)
    <div class="help-section">
        <h5>
            <span class="step-num">2</span>
            Reporting an Incident
        </h5>
        <ul>
            <li>Click the <strong>"Report Incident"</strong> button from the dashboard or sidebar.</li>
            <li>Fill in the <strong>Title</strong> - be specific and descriptive.</li>
            <li>Select the appropriate <strong>Category</strong> (IT/Network, Safety, Security, Infrastructure, etc.).</li>
            <li>Choose your <strong>Department</strong> - this determines who handles the incident.</li>
            <li>Set the <strong>Severity</strong> (Low, Medium, High, Critical) based on impact.</li>
            <li>Set the <strong>Priority</strong> to indicate urgency.</li>
            <li>Provide a detailed <strong>Description</strong> - include what happened, when, where, and who is affected.</li>
            <li>Add <strong>Tags</strong> to help categorize the incident.</li>
            <li>Upload <strong>Photos, Videos, or Documents</strong> as evidence (max 20MB each).</li>
            <li>Check <strong>"Report Anonymously"</strong> if you want to hide your identity.</li>
        </ul>
        <div class="help-tip">
            <i class="fas fa-lightbulb"></i>
            <strong>Tip:</strong> Good photos and clear descriptions help resolve incidents faster. Include exact location and GPS coordinates if possible.
        </div>
    </div>
    @else
    <div class="help-section" style="opacity:0.6;">
        <h5>
            <span class="step-num">2</span>
            Reporting an Incident <span class="badge bg-secondary ms-2">Viewer - Read Only</span>
        </h5>
        <p class="text-muted small">As a Viewer, you cannot report incidents. Please contact your supervisor or HOD to report an incident.</p>
        <div class="help-warning">
            <i class="fas fa-lock"></i>
            <strong>Access Restricted:</strong> Reporting incidents requires <strong>Staff</strong> role or higher.
        </div>
    </div>
    @endif

    {{-- Understanding Incident Status --}}
    <div class="help-section">
        <h5>
            <span class="step-num">3</span>
            Understanding Incident Status
        </h5>
        <div class="table-responsive">
            <table class="table table-sm status-table mb-0">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Meaning</th>
                        <th>Who Can Change</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge status-open">Open</span></td>
                        <td>Incident reported, waiting for acknowledgment</td>
                        <td>Staff, Supervisor, HOD, Admin</td>
                    </tr>
                    <tr>
                        <td><span class="badge status-acknowledged">Acknowledged</span></td>
                        <td>Seen and being reviewed</td>
                        <td>Supervisor, HOD, Admin</td>
                    </tr>
                    <tr>
                        <td><span class="badge status-in_progress">In Progress</span></td>
                        <td>Actively being worked on</td>
                        <td>Assigned User, Supervisor, HOD, Admin</td>
                    </tr>
                    <tr>
                        <td><span class="badge status-escalated">Escalated</span></td>
                        <td>Sent to higher authority</td>
                        <td>Supervisor, HOD, Admin</td>
                    </tr>
                    <tr>
                        <td><span class="badge status-resolved">Resolved</span></td>
                        <td>Issue fixed, awaiting verification</td>
                        <td>Assigned User, HOD, Supervisor and Admin</td>
                    </tr>
                    <tr>
                        <td><span class="badge status-closed">Closed</span></td>
                        <td>Verified and permanently closed</td>
                        <td>HOD, Admin and reporter</td>
                    </tr>
                    <tr>
                        <td><span class="badge status-rejected">Rejected</span></td>
                        <td>Invalid or duplicate incident</td>
                        <td>HOD, Admin</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="help-tip mt-3">
            <i class="fas fa-lightbulb"></i>
            <strong>Tip:</strong> Incidents marked as <span class="badge bg-danger">Overdue</span> have crossed their SLA deadline and need immediate attention.
        </div>
    </div>

    {{-- Tracking Incidents --}}
    <div class="help-section">
        <h5>
            <span class="step-num">4</span>
            Tracking Your Incidents
        </h5>
        <ul>
            <li>Go to <strong>Incidents</strong> from the sidebar to see all incidents.</li>
            <li>Use <strong>filters</strong> to find incidents by status, severity, department, or date.</li>
            <li>Click on any incident to view its <strong>full details</strong>, timeline, and comments.</li>
            <li>The <strong>Timeline</strong> shows every action taken on the incident.</li>
            <li>Check <strong>SLA Due</strong> time to know when the incident must be resolved.</li>
            @if($isViewer)
            <li>As a Viewer, you can see all incidents in your department but cannot take actions.</li>
            @endif
        </ul>
        @if($isViewer)
        <div class="help-note">
            <i class="fas fa-eye"></i>
            <strong>Viewer Mode:</strong> You have read-only access to all incidents in your department.
        </div>
        @endif
    </div>

    {{-- Comments & Collaboration --}}
    <div class="help-section">
        <h5>
            <span class="step-num">5</span>
            Comments & Collaboration
        </h5>
        <ul>
            <li>Open any incident and scroll to the <strong>Comments</strong> section.</li>
            @if(!$isViewer)
            <li>Type your comment and click <strong>"Post Comment"</strong> or press <strong>Ctrl+Enter</strong>.</li>
            <li>Use <strong>@username</strong> to mention other users and <strong>#tag</strong> for labels.</li>
            <li>All users involved in the incident can see and reply to comments.</li>
            @else
            <li>As a Viewer, you can read comments but cannot post new comments.</li>
            @endif
        </ul>
        @if($isViewer)
        <div class="help-warning">
            <i class="fas fa-lock"></i>
            <strong>Read Only:</strong> Viewers can read comments but cannot add or reply.
        </div>
        @endif
    </div>

    {{-- For Supervisors & HODs --}}
    @if($isSupervisor || $isHOD || $isAdmin)
    <div class="help-section" style="border-left: 4px solid {{ $isAdmin ? '#1a56db' : ($isHOD ? '#10b981' : '#f59e0b') }};">
        <h5>
            <span class="step-num">6</span>
            {{ $isAdmin ? 'Admin Controls' : ($isHOD ? 'HOD Controls' : 'Supervisor Controls') }}
        </h5>
        <ul>
            @if($isSupervisor || $isHOD || $isAdmin)
            <li><strong>Assigning:</strong> Click "Assign" on an incident to delegate it to a team member.</li>
            <li><strong>Escalating:</strong> If an incident needs higher authority, click "Escalate" to send it up the chain.</li>
            @endif
            @if($isHOD || $isAdmin)
            <li><strong>Resolving:</strong> After fixing the issue, click "Resolve" and add detailed resolution notes.</li>
            <li><strong>Closing:</strong> Verify the resolution and click "Close" to finalize the incident.</li>
            <li><strong>Rejecting:</strong> If an incident is invalid, click "Reject" with a reason.</li>
            @endif
            @if($isAdmin)
            <li><strong>User Management:</strong> Add, edit, or remove users from the system.</li>
            <li><strong>Department Management:</strong> Create and manage departments.</li>
            <li><strong>Category Management:</strong> Define incident categories and SLA settings.</li>
            <li><strong>Reports:</strong> Access the KPI Dashboard to monitor organization performance.</li>
            <li><strong>Export:</strong> Export reports in Excel, PDF, and CSV formats.</li>
            @endif
        </ul>
        <div class="help-tip">
            <i class="fas fa-lightbulb"></i>
            <strong>Tip:</strong> 
            @if($isAdmin)
            As an Admin, you have full control over the system. Use this power responsibly.
            @elseif($isHOD)
            As HOD, you have full control over your department's incidents.
            @else
            As Supervisor, you can assign and escalate incidents within your department.
            @endif
        </div>
    </div>
    @endif

    {{-- SLA & Escalation --}}
    <div class="help-section">
        <h5>
            <span class="step-num">7</span>
            SLA & Escalation
        </h5>
        <ul>
            <li><strong>SLA (Service Level Agreement):</strong> Each incident category has a defined SLA time.</li>
            <li><strong>Breach:</strong> If the SLA time is exceeded, the incident is marked as "Overdue".</li>
            <li><strong>Auto-Escalation:</strong> If an incident breaches SLA multiple times, it's automatically escalated.</li>
            <li><strong>Escalation Matrix:</strong> Defines who to escalate to based on incident category and severity.</li>
            <li>HODs and Admins can configure escalation matrices in the admin panel.</li>
        </ul>
        <div class="help-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Important:</strong> SLA breaches are tracked and count towards department performance metrics.
        </div>
    </div>

    {{-- Notifications --}}
    <div class="help-section">
        <h5>
            <span class="step-num">8</span>
            Notifications
        </h5>
        <ul>
            <li>You'll receive <strong>email notifications</strong> when incidents are assigned to you, escalated, or resolved.</li>
            <li>The <strong>bell icon</strong> in the top bar shows unread notifications count.</li>
            <li>Click the bell to see recent notifications, or go to the Notifications page to view all.</li>
            <li><strong>Push notifications:</strong> Enable browser notifications for real-time updates.</li>
        </ul>
    </div>

    {{-- Mobile Access --}}
    <div class="help-section">
        <h5>
            <span class="step-num">9</span>
            Mobile Access
        </h5>
        <ul>
            <li>SAMANVAY is fully responsive and works on all mobile devices.</li>
            <li>Use the <strong>bottom navigation bar</strong> to access key features on mobile.</li>
            <li><strong>GPS Attendance:</strong> Mark attendance using GPS verification (mobile required).</li>
            <li>Upload photos directly from your camera on mobile.</li>
        </ul>
        <div class="help-tip">
            <i class="fas fa-mobile-alt"></i>
            <strong>Tip:</strong> For the best mobile experience, use the latest version of Chrome, Safari, or Edge.
        </div>
    </div>

    {{-- Need Help --}}
    <div class="help-section">
        <h5>
            <i class="fas fa-headset text-primary me-2"></i>
            Need Further Help?
        </h5>
        <ul>
            <li>Contact your <strong>department head</strong> or <strong>system administrator</strong> for account-related issues.</li>
            <li>For technical issues, reach out to the <strong>IT support team</strong>.</li>
            <li>Email support: <a href="mailto:team@ddg.nscbiairport.com">team@ddg.nscbiairport.com</a></li>
            <li>Phone: <a href="tel:+917633947548">+91-7633947548</a></li>
        </ul>
        <div class="help-note">
            <i class="fas fa-info-circle"></i>
            <strong>Feedback:</strong> We continuously improve SAMANVAY. Share your feedback with your administrator.
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
@endpush


