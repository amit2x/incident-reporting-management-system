{{-- resources/views/reports/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Reports - IRMS')

@push('styles')
<style>
    .report-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 28px 20px;
        text-align: center;
        transition: all 0.2s ease;
        height: 100%;
        text-decoration: none;
        display: block;
        position: relative;
        overflow: hidden;
    }

    .report-card:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        transform: translateY(-4px);
        border-color: #3b82f6;
    }

    .report-card .report-icon {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 16px;
        transition: all 0.3s;
    }

    .report-card:hover .report-icon {
        transform: scale(1.1);
    }

    .report-card h5 {
        font-weight: 700;
        margin-bottom: 6px;
        color: #1f2937;
    }

    .report-card p {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-bottom: 0;
        line-height: 1.5;
    }

    .report-card .badge-count {
        position: absolute;
        top: 16px;
        right: 16px;
        font-size: 0.65rem;
        padding: 4px 10px;
    }

    .quick-stat-mini {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px;
        text-align: center;
    }

    .quick-stat-mini .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .quick-stat-mini .stat-label {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold mb-1"><i class="fas fa-chart-bar text-primary me-2"></i>Reports & Analytics</h4>
        <p class="text-muted small mb-0">Comprehensive reporting and analytics for incident management</p>
    </div>

    {{-- Quick Stats Row --}}
    @php
    $totalIncidents = \App\Models\Incident::count();
    $openIncidents = \App\Models\Incident::whereIn('status', ['open','acknowledged','in_progress'])->count();
    $slaBreaches = \App\Models\Incident::where('sla_breach_count', '>', 0)->count();
    $departmentsCount = \App\Models\Department::active()->count();
    $totalUsers = \App\Models\User::active()->count();
    $categoriesCount = \App\Models\IncidentCategory::active()->count();
    @endphp
    <div class="row g-2 mb-4">
        <div class="col-4 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-primary">{{ $totalIncidents }}</div>
                <div class="stat-label">Total Incidents</div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-warning">{{ $openIncidents }}</div>
                <div class="stat-label">Open</div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-danger">{{ $slaBreaches }}</div>
                <div class="stat-label">SLA Breaches</div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-info">{{ $departmentsCount }}</div>
                <div class="stat-label">Departments</div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-success">{{ $totalUsers }}</div>
                <div class="stat-label">Users</div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-purple" style="color:#8b5cf6;">{{ $categoriesCount }}</div>
                <div class="stat-label">Categories</div>
            </div>
        </div>
    </div>

    {{-- Main Report Cards --}}
    <h5 class="fw-bold mb-3">📊 Standard Reports</h5>
    <div class="row g-3 mb-4">
        {{-- KPI Dashboard --}}
        <div class="col-lg-4 col-md-6">
            <a href="{{ route('reports.kpi') }}" class="report-card">
                <span class="badge bg-primary badge-count">Live</span>
                <div class="report-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h5>KPI Dashboard</h5>
                <p>Key performance indicators, trends, severity distribution, hourly heatmap and more</p>
            </a>
        </div>

        {{-- Department Report --}}
        <div class="col-lg-4 col-md-6">
            <a href="{{ route('reports.department') }}" class="report-card">
                <span class="badge bg-success badge-count">{{ $departmentsCount }} Depts</span>
                <div class="report-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-building"></i>
                </div>
                <h5>Department Report</h5>
                <p>Department-wise performance, team members, incident breakdown, and resolution rates</p>
            </a>
        </div>

        {{-- SLA Report --}}
        <div class="col-lg-4 col-md-6">
            <a href="{{ route('reports.sla') }}" class="report-card">
                <span class="badge bg-danger badge-count">{{ $slaBreaches }} Breaches</span>
                <div class="report-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-stopwatch"></i>
                </div>
                <h5>SLA Compliance Report</h5>
                <p>Service level agreement tracking, breach analysis, and compliance by department/category</p>
            </a>
        </div>
    </div>

    {{-- Additional Reports --}}
    <h5 class="fw-bold mb-3">📋 Detailed Analysis</h5>
    <div class="row g-3 mb-4">
        {{-- Category-wise Report --}}
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('reports.category') }}" class="report-card">
                <div class="report-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-tags"></i>
                </div>
                <h5>Category Analysis</h5>
                <p>Incident distribution by category, SLA compliance per category, resolution trends</p>
            </a>
        </div>

        {{-- User Performance --}}
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('reports.user-performance') }}" class="report-card">
                <div class="report-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-users"></i>
                </div>
                <h5>User Performance</h5>
                <p>Individual performance metrics, resolution rates, response times by user</p>
            </a>
        </div>

        {{-- Escalation Report --}}
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('reports.escalation') }}" class="report-card">
                <div class="report-icon bg-purple bg-opacity-10" style="color:#7c3aed; background:#7c3aed15;">
                    <i class="fas fa-arrow-up-right-dots"></i>
                </div>
                <h5>Escalation Analysis</h5>
                <p>Escalation patterns, auto-escalation triggers, escalation matrix effectiveness</p>
            </a>
        </div>

        {{-- Custom Date Range --}}
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('reports.custom') }}" class="report-card">
                <div class="report-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="fas fa-calendar-range"></i>
                </div>
                <h5>Custom Date Report</h5>
                <p>Generate reports for any custom date range with all available filters</p>
            </a>
        </div>
    </div>

    {{-- Quick Export Section --}}
    <h5 class="fw-bold mb-3">📥 Quick Export</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2">
                        <i class="fas fa-file-excel text-success fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Export All Data</h6>
                        <p class="text-muted small mb-2">Complete incident data export</p>
                        <a href="{{ route('reports.export', 'excel') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-download me-1"></i> Download Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2">
                        <i class="fas fa-file-pdf text-danger fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">KPI Summary PDF</h6>
                        <p class="text-muted small mb-2">One-page KPI summary</p>
                        <a href="{{ route('reports.kpi.export', 'pdf') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-download me-1"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2">
                        <i class="fas fa-file-csv text-warning fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">SLA Report CSV</h6>
                        <p class="text-muted small mb-2">SLA compliance data export</p>
                        <a href="{{ route('reports.sla.export', 'excel') }}" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-download me-1"></i> Download CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection