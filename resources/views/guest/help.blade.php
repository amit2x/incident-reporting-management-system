{{-- resources/views/guest/help.blade.php --}}
@extends('layouts.app')

@section('title', 'Help & User Manual - IRMSystem')

@push('styles')
<style>
    .help-container {
        max-width: 860px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    .help-header {
        text-align: center;
        padding: 32px 20px;
        margin-bottom: 32px;
        background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
        border-radius: 20px;
        color: white;
    }
    .help-header h2 {
        color: white;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .help-header p {
        color: rgba(255,255,255,0.85);
        margin: 0;
    }
    .help-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .help-section h5 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
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
    .status-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-table td {
        font-size: 0.8125rem;
    }
</style>
@endpush

@section('content')
<div class="help-container">

    {{-- Header --}}
    <div class="help-header">
        <h2><i class="fas fa-book me-2"></i>User Manual</h2>
        <p>Learn how to use IRMSystem effectively for incident reporting and management</p>
    </div>

    {{-- Getting Started --}}
    <div class="help-section">
        <h5>
            <span class="step-num">1</span>
            Getting Started
        </h5>
        <ul>
            <li><strong>Login:</strong> Use your registered email and password to sign in. Contact your administrator if you don't have credentials.</li>
            <li><strong>Dashboard:</strong> After login, you'll see your dashboard with incident statistics, recent incidents, and quick actions.</li>
            <li><strong>Navigation:</strong> Use the sidebar (desktop) or bottom navigation (mobile) to move between sections.</li>
        </ul>
        <div class="help-note">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Your role (Staff, Supervisor, HOD, Admin) determines what you can see and do in the system.
        </div>
    </div>

    {{-- Reporting an Incident --}}
    <div class="help-section">
        <h5>
            <span class="step-num">2</span>
            Reporting an Incident
        </h5>
        <ul>
            <li>Click the <strong>"Report Incident"</strong> button from the dashboard or sidebar.</li>
            <li>Fill in the <strong>Title</strong> - be specific and descriptive (e.g., "Water leakage in Terminal 1 lobby").</li>
            <li>Select the appropriate <strong>Category</strong> (Safety, Maintenance, IT, Security, etc.).</li>
            <li>Choose your <strong>Department</strong> - this determines who handles the incident.</li>
            <li>Set the <strong>Severity</strong> (Low, Medium, High, Critical) based on impact.</li>
            <li>Set the <strong>Priority</strong> to indicate urgency.</li>
            <li>Provide a detailed <strong>Description</strong> - include what happened, when, where, and who is affected.</li>
            <li>Add <strong>Tags</strong> (comma-separated) to help categorize the incident.</li>
            <li>Upload <strong>Photos, Videos, or Documents</strong> as evidence (max 20MB each).</li>
            <li>Check <strong>"Report Anonymously"</strong> if you want to hide your identity.</li>
        </ul>
        <div class="help-tip">
            <i class="fas fa-lightbulb"></i>
            <strong>Tip:</strong> Good photos and clear descriptions help resolve incidents faster. Include exact location details.
        </div>
    </div>

    {{-- Understanding Incident Status --}}
    <div class="help-section">
        <h5>
            <span class="step-num">3</span>
            Understanding Incident Status
        </h5>
        <div class="table-responsive">
            <table class="table table-sm status-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Status</th>
                        <th>Meaning</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><span class="badge status-open">Open</span></td><td>Incident has been reported and is waiting to be acknowledged</td></tr>
                    <tr><td><span class="badge status-acknowledged">Acknowledged</span></td><td>A supervisor or HOD has seen the incident and it's being reviewed</td></tr>
                    <tr><td><span class="badge status-in_progress">In Progress</span></td><td>Someone is actively working on resolving the incident</td></tr>
                    <tr><td><span class="badge status-escalated">Escalated</span></td><td>The incident has been escalated to a higher authority for resolution</td></tr>
                    <tr><td><span class="badge status-resolved">Resolved</span></td><td>The issue has been fixed and resolution notes have been added</td></tr>
                    <tr><td><span class="badge status-closed">Closed</span></td><td>The incident has been verified and permanently closed</td></tr>
                    <tr><td><span class="badge status-rejected">Rejected</span></td><td>The incident was reviewed and determined to be invalid or duplicate</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tracking Your Incidents --}}
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
        </ul>
        <div class="help-tip">
            <i class="fas fa-lightbulb"></i>
            <strong>Tip:</strong> Incidents marked as <span class="badge bg-danger">Overdue</span> have crossed their SLA deadline and need immediate attention.
        </div>
    </div>

    {{-- Comments & Collaboration --}}
    <div class="help-section">
        <h5>
            <span class="step-num">5</span>
            Comments & Collaboration
        </h5>
        <ul>
            <li>Open any incident and scroll to the <strong>Comments</strong> section.</li>
            <li>Type your comment and click <strong>"Post Comment"</strong> or press <strong>Ctrl+Enter</strong>.</li>
            <li>All users involved in the incident can see and reply to comments.</li>
            <li>Use comments to provide updates, ask questions, or share additional information.</li>
        </ul>
    </div>

    {{-- For Supervisors & HODs --}}
    <div class="help-section">
        <h5>
            <span class="step-num">6</span>
            For Supervisors & Department Heads
        </h5>
        <ul>
            <li><strong>Assigning:</strong> Click "Assign" on an incident to delegate it to a team member.</li>
            <li><strong>Escalating:</strong> If an incident needs higher authority, click "Escalate" to send it up the chain.</li>
            <li><strong>Resolving:</strong> After fixing the issue, click "Resolve" and add detailed resolution notes.</li>
            <li><strong>Closing:</strong> Verify the resolution and click "Close" to finalize the incident.</li>
            <li><strong>Reports:</strong> Access the KPI Dashboard to monitor department performance.</li>
        </ul>
    </div>

    {{-- Notifications --}}
    <div class="help-section">
        <h5>
            <span class="step-num">7</span>
            Notifications
        </h5>
        <ul>
            <li>You'll receive <strong>email notifications</strong> when incidents are assigned to you, escalated, or resolved.</li>
            <li>The <strong>bell icon</strong> in the top bar shows unread notifications count.</li>
            <li>Click the bell to see recent notifications, or go to the Notifications page to view all.</li>
            <li>You can manage notification preferences in <strong>Settings</strong>.</li>
        </ul>
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
        </ul>
    </div>

</div>
@endsection
