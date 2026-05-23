@extends('layouts.app')

@section('title', 'Dashboard - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="page-enter">

    {{-- Welcome Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Welcome back, {{ Auth::user()->name }} 👋</h4>
            <p class="text-muted mb-0" style="font-size: 0.8125rem;">
                Here's what's happening with your incidents today.
            </p>
        </div>
        <a href="{{ route('incidents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Report Incident
        </a>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10">
                    <i class="fas fa-clipboard-list text-primary"></i>
                </div>
                <div class="stat-value">{{ $stats['total_incidents'] ?? 0 }}</div>
                <div class="stat-label">Total Incidents</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 12% from last month
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10">
                    <i class="fas fa-clock text-warning"></i>
                </div>
                <div class="stat-value">{{ $stats['open_incidents'] ?? 0 }}</div>
                <div class="stat-label">Open Incidents</div>
                <div class="stat-change negative">
                    <i class="fas fa-exclamation-circle"></i> Needs attention
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <div class="stat-value">{{ $stats['resolved_today'] ?? 0 }}</div>
                <div class="stat-label">Resolved Today</div>
                <div class="stat-change positive">
                    <i class="fas fa-check"></i> On track
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon bg-danger bg-opacity-10">
                    <i class="fas fa-arrow-up text-danger"></i>
                </div>
                <div class="stat-value">{{ $stats['escalated_incidents'] ?? 0 }}</div>
                <div class="stat-label">Escalated</div>
                <div class="stat-change">
                    <i class="fas fa-flag"></i> High priority
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Incident Trends (Last 30 Days)</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light active" onclick="updateTrendChart('weekly')">Week</button>
                        <button class="btn btn-light" onclick="updateTrendChart('monthly')">Month</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Severity Distribution</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="severityChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Critical Incidents & Recent Feed --}}
    <div class="row g-3">
        {{-- Recent Incidents Feed --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Incidents</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light active feed-filter" data-filter="all">All</button>
                        <button class="btn btn-light feed-filter" data-filter="open">Open</button>
                        <button class="btn btn-light feed-filter" data-filter="resolved">Resolved</button>
                    </div>
                </div>
                <div class="card-body p-0" id="incidentFeed">
                    @if(isset($recentIncidents) && count($recentIncidents) > 0)
                        @foreach($recentIncidents as $incident)
                            <div class="p-3 border-bottom incident-feed-item">
                                <div class="d-flex gap-3">
                                    <img src="{{ $incident->reporter?->avatar_url ?? asset('images/default-avatar.png') }}"
                                         alt="Reporter"
                                         class="rounded-circle"
                                         width="40" height="40"
                                         style="object-fit: cover;">
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <span class="fw-semibold">{{ $incident->reporter?->name ?? 'Anonymous' }}</span>
                                                <span class="badge ms-2" style="background: {{ $incident->department?->color ?? '#6B7280' }}20; color: {{ $incident->department?->color ?? '#6B7280' }}; font-size: 0.625rem;">
                                                    {{ $incident->department?->code ?? 'N/A' }}
                                                </span>
                                            </div>
                                            <small class="text-muted">{{ $incident->created_at->diffForHumans() }}</small>
                                        </div>
                                        <a href="{{ route('incidents.show', $incident) }}" class="text-decoration-none">
                                            <div class="fw-medium text-dark mb-1">{{ $incident->title }}</div>
                                        </a>
                                        <p class="text-muted small mb-2">{{ Str::limit($incident->description, 120) }}</p>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge status-{{ str_replace('_', '-', $incident->status) }}">
                                                {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                                            </span>
                                            <span class="badge priority-{{ $incident->priority }}">
                                                {{ ucfirst($incident->priority) }}
                                            </span>
                                            @if($incident->is_overdue)
                                                <span class="badge bg-danger">Overdue</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="empty-title">No incidents yet</div>
                            <div class="empty-description">Incidents will appear here once reported</div>
                            <a href="{{ route('incidents.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Report First Incident
                            </a>
                        </div>
                    @endif
                </div>
                @if(isset($recentIncidents) && $recentIncidents->hasMorePages())
                    <div class="card-footer text-center">
                        <a href="{{ route('incidents.index') }}" class="btn btn-light btn-sm">
                            View All Incidents <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar Stats --}}
        <div class="col-xl-4">
            {{-- Department Performance --}}
            @if(isset($departmentPerformance) && count($departmentPerformance) > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Department Performance</h6>
                </div>
                <div class="card-body p-0">
                    @foreach($departmentPerformance as $dept)
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-dot" style="background: {{ $dept['color'] ?? '#6B7280' }};"></span>
                                    <small class="fw-medium">{{ $dept['name'] ?? 'Unknown' }}</small>
                                </div>
                                <small class="text-muted">
                                    {{ $dept['active_incidents'] ?? $dept['open_incidents_count'] ?? 0 }} open
                                </small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                @php
                                    $total = ($dept['total_incidents'] ?? $dept['total_incidents_count'] ?? 0);
                                    $resolved = ($dept['resolved_incidents'] ?? $dept['resolved_incidents_count'] ?? 0);
                                    $percentage = $total > 0 ? ($resolved / $total) * 100 : 0;
                                @endphp
                                <div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                            </div>
                            <small class="text-muted" style="font-size: 0.6875rem;">{{ round($percentage, 1) }}% resolved</small>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('incidents.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Report New Incident
                        </a>
                        <a href="{{ route('reports.kpi') }}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-2"></i>View KPI Report
                        </a>
                        <a href="{{ route('incidents.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>View All Incidents
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Trend Chart
    const trendCtx = document.getElementById('trendChart')?.getContext('2d');
    if (trendCtx) {
        const trendData = @json($dailyTrends ?? []);
        const labels = Object.keys(trendData);
        const values = Object.values(trendData);

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels.length > 0 ? labels : ['No Data'],
                datasets: [{
                    label: 'Incidents',
                    data: values.length > 0 ? values : [0],
                    borderColor: '#1a56db',
                    backgroundColor: 'rgba(26, 86, 219, 0.08)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#1a56db',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 11 } }
                    },
                    x: {
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Severity Chart
    const severityCtx = document.getElementById('severityChart')?.getContext('2d');
    if (severityCtx) {
        const severityData = @json($severityDistribution ?? []);
        const sevLabels = Object.keys(severityData).map(k => k.charAt(0).toUpperCase() + k.slice(1));
        const sevValues = Object.values(severityData);

        new Chart(severityCtx, {
            type: 'doughnut',
            data: {
                labels: sevLabels.length > 0 ? sevLabels : ['No Data'],
                datasets: [{
                    data: sevValues.length > 0 ? sevValues : [1],
                    backgroundColor: ['#dc2626', '#ea580c', '#d97706', '#059669'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: { size: 11 },
                            usePointStyle: true,
                        }
                    }
                }
            }
        });
    }

    // Feed Filter Buttons
    document.querySelectorAll('.feed-filter').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.feed-filter').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            // You can implement AJAX filtering here
        });
    });
});
</script>
@endpush
