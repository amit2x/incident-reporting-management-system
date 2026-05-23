{{-- resources/views/guest/features.blade.php --}}
@extends('layouts.app')

@section('title', 'Features - IRMSystem')

@push('styles')
<style>
    .features-container {
        max-width: 960px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    .feature-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 28px;
        transition: all 0.2s ease;
        height: 100%;
    }
    .feature-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }
    .feature-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 16px;
    }
    .hero-section {
        text-align: center;
        padding: 40px 20px;
        margin-bottom: 20px;
    }
    .hero-section h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 12px;
    }
    .hero-section p {
        color: #6b7280;
        font-size: 1.0625rem;
        max-width: 600px;
        margin: 0 auto;
    }
</style>
@endpush

@section('content')
<div class="features-container">

    {{-- Hero --}}
    <div class="hero-section">
        <h2>Powerful Incident Management</h2>
        <p>IRMSystem provides everything you need to report, track, and resolve incidents efficiently across your organization.</p>
    </div>

    {{-- Features Grid --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-bolt"></i>
                </div>
                <h5 class="fw-bold mb-2">Quick Incident Reporting</h5>
                <p class="text-muted small mb-0">Report incidents in seconds with our intuitive form. Add photos, videos, and documents as evidence. Select category, severity, and priority with ease.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <h5 class="fw-bold mb-2">Auto Assignment</h5>
                <p class="text-muted small mb-0">Incidents are automatically assigned to the right person based on department workload. No manual routing needed - the system handles it intelligently.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <h5 class="fw-bold mb-2">SLA Tracking</h5>
                <p class="text-muted small mb-0">Every incident has a Service Level Agreement (SLA) timer. Get alerts before deadlines and track compliance across departments.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-arrow-up-right-dots"></i>
                </div>
                <h5 class="fw-bold mb-2">Escalation Matrix</h5>
                <p class="text-muted small mb-0">Define multi-level escalation rules. When SLAs are breached, incidents automatically escalate to supervisors, HODs, or admins.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-comments"></i>
                </div>
                <h5 class="fw-bold mb-2">Real-time Collaboration</h5>
                <p class="text-muted small mb-0">Discuss incidents with threaded comments. Mention team members, share updates, and keep everyone in the loop.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-purple bg-opacity-10 text-purple" style="background: #7c3aed20; color: #7c3aed;">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h5 class="fw-bold mb-2">KPI Dashboard</h5>
                <p class="text-muted small mb-0">Visualize incident data with interactive charts. Track response times, resolution rates, SLA compliance, and department performance.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-bell"></i>
                </div>
                <h5 class="fw-bold mb-2">Smart Notifications</h5>
                <p class="text-muted small mb-0">Get email and push notifications for assignments, escalations, comments, and status changes. Never miss an important update.</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="feature-card">
                <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-file-export"></i>
                </div>
                <h5 class="fw-bold mb-2">Reports & Exports</h5>
                <p class="text-muted small mb-0">Generate detailed reports in Excel, PDF, or CSV formats. Export incident data for audits, meetings, or compliance reviews.</p>
            </div>
        </div>
    </div>
</div>
@endsection
