{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard - IRMS')

@php
$user = Auth::user();
$isAdmin = $user->isAdmin();
@endphp

@push('styles')
<style>
    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.1);
        transform: translateY(-2px);
    }

    .stat-card .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 8px;
    }

    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.2;
    }

    .stat-card .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .stat-card .stat-change {
        font-size: 0.6875rem;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-card .stat-change.positive {
        color: #059669;
    }

    .stat-card .stat-change.negative {
        color: #dc2626;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .section-header h5 {
        font-weight: 700;
        margin: 0;
    }

    .section-header .view-all {
        font-size: 0.8125rem;
    }

    .my-incident-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.15s;
        background: white;
    }

    .my-incident-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .my-incident-card.escalated {
        border-left: 3px solid #f59e0b;
    }

    .my-incident-card.assigned {
        border-left: 3px solid #3b82f6;
    }

    .my-incident-card.reported {
        border-left: 3px solid #10b981;
    }

    /* Feed filter buttons - mobile friendly */
    .feed-filter-group {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
    }

    .feed-filter-group .btn {
        white-space: nowrap;
        font-size: 0.6875rem;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
    }

    .feed-filter-group .btn.active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #3b82f6;
        font-weight: 600;
    }

    .feed-filter-group .btn sup {
        top: auto;
        vertical-align: middle;
        line-height: 1;
    }

    @media (max-width: 575.98px) {
        .feed-filter-group {
            gap: 2px;
        }

        .feed-filter-group .btn {
            font-size: 0.625rem;
            padding: 5px 8px;
            border-radius: 16px;
        }

        .feed-filter-group .btn sup {
            font-size: 0.5rem !important;
        }
    }

    .feed-item {
        padding: 14px 16px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background 0.15s;
    }

    .feed-item:hover {
        background: #f8fafc;
    }

    .feed-item:last-child {
        border-bottom: none;
    }

    @media (max-width: 575.98px) {
        .stat-card {
            padding: 12px;
        }

        .stat-card .stat-icon {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
            position: absolute;
            top: 12px;
            right: 12px;
            margin-bottom: 0;
        }

        .stat-card .stat-value {
            font-size: 1.25rem;
            margin-top: 0;
        }

        .stat-card .stat-label {
            font-size: 0.7rem;
            max-width: 70%;
        }
    }
</style>
@endpush

@section('content')
<div class="py-3">

    {{-- Welcome Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1">Welcome back, {{ $user->name }} 👋</h4>
            <p class="text-muted small mb-0">Here's your incident overview</p>
        </div>
        <a href="{{ route('incidents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Report Incident
        </a>
    </div>

    {{-- ========================================== --}}
    {{-- MY QUICK STATS --}}
    {{-- ========================================== --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('incidents.index', ['tab' => 'assigned']) }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10"><i class="fas fa-user-check text-primary"></i></div>
                    <div class="stat-value">{{ $myStats['assigned_count'] ?? 0 }}</div>
                    <div class="stat-label">Assigned to Me</div>
                    <div class="stat-change"><span class="text-muted">Pending action</span></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('incidents.index', ['tab' => 'escalated']) }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10"><i class="fas fa-arrow-up text-warning"></i></div>
                    <div class="stat-value">{{ $myStats['escalated_count'] ?? 0 }}</div>
                    <div class="stat-label">Escalated to Me</div>
                    <div class="stat-change negative"><i class="fas fa-exclamation-triangle"></i> Needs attention</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" onclick="window.location='{{ route('incidents.index') }}?reported_by=me'">
                <div class="stat-icon bg-success bg-opacity-10"><i class="fas fa-pen text-success"></i></div>
                <div class="stat-value">{{ $myStats['reported_count'] ?? 0 }}</div>
                <div class="stat-label">Reported by Me</div>
                <div class="stat-change positive"><i class="fas fa-check-circle"></i> Total reported</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info bg-opacity-10"><i class="fas fa-check-double text-info"></i></div>
                <div class="stat-value">{{ $myStats['resolved_count'] ?? 0 }}</div>
                <div class="stat-label">Resolved by Me</div>
                <div class="stat-change positive"><i class="fas fa-trophy"></i> Good job!</div>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- OVERALL STATS (Admin/Department View) --}}
    {{-- ========================================== --}}
    <div class="row g-2 mb-3">
        @php
        $statusList = [
        'total_incidents' => ['label' => 'Total', 'icon' => 'fa-clipboard-list', 'color' => '#3B82F6'],
        'open_incidents' => ['label' => 'Open', 'icon' => 'fa-folder-open', 'color' => '#F59E0B'],
        'resolved_today' => ['label' => 'Resolved Today', 'icon' => 'fa-check-circle', 'color' => '#10B981'],
        'escalated_incidents' => ['label' => 'Escalated', 'icon' => 'fa-arrow-up', 'color' => '#EF4444'],
        ];
        @endphp
        @foreach($statusList as $key => $item)
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:{{ $item['color'] }}15;color:{{ $item['color'] }}">
                    <i class="fas {{ $item['icon'] }}"></i>
                </div>
                <div class="stat-value">{{ $stats[$key] ?? 0 }}</div>
                <div class="stat-label">{{ $item['label'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ========================================== --}}
    {{-- CRITICAL & OVERDUE ALERTS --}}
    {{-- ========================================== --}}
    @if(isset($criticalIncidents) && $criticalIncidents->count() > 0)
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert"
        style="font-size:0.8125rem;">
        <i class="fas fa-exclamation-triangle fs-5"></i>
        <div class="flex-grow-1">
            <strong>{{ $criticalCount }} critical incident(s)</strong> require immediate attention.
        </div>
        <a href="{{ route('incidents.index', ['severity' => 'critical']) }}" class="btn btn-danger btn-sm">View All</a>
    </div>
    @endif

    @if(isset($overdueIncidents) && $overdueIncidents->count() > 0)
    <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert"
        style="font-size:0.8125rem;">
        <i class="fas fa-clock fs-5"></i>
        <div class="flex-grow-1">
            <strong>{{ $overdueCount }} incident(s)</strong> are overdue.
        </div>
        <a href="{{ route('incidents.index', ['status' => 'overdue']) }}" class="btn btn-warning btn-sm">View All</a>
    </div>
    @endif

    <div class="row g-3">
        {{-- ========================================== --}}
        {{-- LEFT COLUMN: FEED + MY INCIDENTS --}}
        {{-- ========================================== --}}
        <div class="col-lg-8">

            {{-- Escalated to Me --}}
            @if($escalatedToMe->count() > 0)
            <div class="section-header">
                <h5><i class="fas fa-arrow-up text-warning me-2"></i>Escalated to Me</h5>
                <a href="{{ route('incidents.index', ['tab' => 'escalated']) }}" class="view-all text-warning">View All
                    →</a>
            </div>
            <div class="mb-3">
                @foreach($escalatedToMe as $incident)
                <div class="my-incident-card escalated"
                    onclick="window.location='{{ route('incidents.show', $incident) }}'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-light text-dark small me-1">#{{ $incident->incident_id }}</span>
                            <span class="fw-medium small">{{ Str::limit($incident->title, 50) }}</span>
                        </div>
                        <small class="text-muted flex-shrink-0">{{ $incident->escalated_at?->diffForHumans() }}</small>
                    </div>
                    <div class="d-flex gap-1 mt-1">
                        <span class="badge bg-light text-dark small">{{ $incident->department?->name }}</span>
                        <span class="badge priority-{{ $incident->priority }} small">{{ ucfirst($incident->priority)
                            }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Assigned to Me --}}
            @if($assignedToMe->count() > 0)
            <div class="section-header">
                <h5><i class="fas fa-user-check text-primary me-2"></i>Assigned to Me</h5>
                <a href="{{ route('incidents.index', ['tab' => 'assigned']) }}" class="view-all text-primary">View All
                    →</a>
            </div>
            <div class="mb-3">
                @foreach($assignedToMe as $incident)
                <div class="my-incident-card assigned"
                    onclick="window.location='{{ route('incidents.show', $incident) }}'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-light text-dark small me-1">#{{ $incident->incident_id }}</span>
                            <span class="fw-medium small">{{ Str::limit($incident->title, 50) }}</span>
                        </div>
                        <small class="text-muted flex-shrink-0">{{ $incident->updated_at->diffForHumans() }}</small>
                    </div>
                    <div class="d-flex align-items-center gap-1 mt-1">
                        <span class="badge status-{{ str_replace('_','-',$incident->status) }} small">{{
                            str_replace('_',' ',ucfirst($incident->status)) }}</span>
                        <span class="badge priority-{{ $incident->priority }} small">{{ ucfirst($incident->priority)
                            }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Recent Incidents Feed with Working Filters --}}
            <div class="section-header">
                <h5><i class="fas fa-newspaper text-info me-2"></i>Recent Incidents</h5>
                <div class="btn-group btn-group-sm feed-filter-group">
                    <button class="btn btn-light active feed-filter" data-filter="all">
                        All
                        @php $allCount = isset($stats['total_incidents']) ? $stats['total_incidents'] : 0; @endphp
                        @if($allCount > 0)<sup class="ms-1 fw-bold" style="font-size:0.6rem;color:#3b82f6;">{{ $allCount
                            }}</sup>@endif
                    </button>
                    <button class="btn btn-light feed-filter" data-filter="escalated">
                        Escalated
                        @if($myStats['escalated_count'] > 0)<sup class="ms-1 fw-bold"
                            style="font-size:0.6rem;color:#f59e0b;">{{ $myStats['escalated_count'] }}</sup>@endif
                    </button>
                    <button class="btn btn-light feed-filter" data-filter="assigned">
                        Assigned
                        @if($myStats['assigned_count'] > 0)<sup class="ms-1 fw-bold"
                            style="font-size:0.6rem;color:#3b82f6;">{{ $myStats['assigned_count'] }}</sup>@endif
                    </button>
                    <button class="btn btn-light feed-filter" data-filter="critical">
                        Critical
                        @if($criticalCount > 0)<sup class="ms-1 fw-bold" style="font-size:0.6rem;color:#dc2626;">{{
                            $criticalCount }}</sup>@endif
                    </button>
                </div>
            </div>
            <div class="card shadow-sm mb-3">
                <div class="card-body p-0" id="incidentFeed">
                    @if(isset($recentIncidents) && count($recentIncidents) > 0)
                    @foreach($recentIncidents as $incident)
                    <div class="feed-item" onclick="window.location='{{ route('incidents.show', $incident) }}'">
                        <div class="d-flex gap-2">
                            <img src="{{ $incident->reporter?->avatar_url ?? asset('images/default-avatar.png') }}"
                                class="rounded-circle flex-shrink-0" width="36" height="36" style="object-fit:cover;">
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="fw-semibold small">{{ $incident->reporter?->name ?? 'Anonymous'
                                            }}</span>
                                        <span class="badge ms-1 small"
                                            style="background:{{ $incident->department?->color ?? '#6B7280' }}15;color:{{ $incident->department?->color ?? '#6B7280' }}">
                                            {{ $incident->department?->code ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <small class="text-muted flex-shrink-0">{{ $incident->created_at->diffForHumans()
                                        }}</small>
                                </div>
                                <div class="fw-medium small mt-1">{{ Str::limit($incident->title, 60) }}</div>
                                <div class="d-flex gap-1 mt-1">
                                    <span class="badge status-{{ str_replace('_','-',$incident->status) }} small">{{
                                        str_replace('_',' ',ucfirst($incident->status)) }}</span>
                                    <span class="badge priority-{{ $incident->priority }} small">{{
                                        ucfirst($incident->priority) }}</span>
                                    @if($incident->is_overdue)<span class="badge bg-danger small">Overdue</span>@endif
                                    @if($incident->escalated_to === Auth::id() && $incident->status === 'escalated')
                                    <span class="badge bg-warning text-dark small">⚠ Action Needed</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
                        <small>No incidents found</small>
                    </div>
                    @endif
                </div>
                {{-- Loading indicator (hidden by default) --}}
                <div id="feedLoading" class="text-center py-4 d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- RIGHT COLUMN: CHARTS + QUICK ACTIONS --}}
        {{-- ========================================== --}}
        <div class="col-lg-4">
            {{-- Severity Chart --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white py-2"><strong>Severity Distribution</strong></div>
                <div class="card-body" style="height:250px;">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>

            {{-- Trend Chart --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white py-2"><strong>Trend (Last 30 Days)</strong></div>
                <div class="card-body" style="height:200px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white py-2"><strong>Quick Actions</strong></div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('incidents.create') }}" class="btn btn-primary btn-sm"><i
                                class="fas fa-plus-circle me-1"></i> Report Incident</a>
                        <a href="{{ route('incidents.index', ['tab' => 'escalated']) }}"
                            class="btn btn-outline-warning btn-sm"><i class="fas fa-arrow-up me-1"></i> Escalated to Me
                            @if($myStats['escalated_count'] > 0)<span class="badge bg-warning text-dark ms-1">{{
                                $myStats['escalated_count'] }}</span>@endif</a>
                        <a href="{{ route('incidents.index', ['tab' => 'assigned']) }}"
                            class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check me-1"></i> My Assignments
                            @if($myStats['assigned_count'] > 0)<span class="badge bg-primary ms-1">{{
                                $myStats['assigned_count'] }}</span>@endif</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // CHARTS
    // ==========================================
    const severityCtx = document.getElementById('severityChart')?.getContext('2d');
    if (severityCtx) {
        const severityData = @json($severityDistribution ?? []);
        new Chart(severityCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(severityData).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
                datasets: [{
                    data: Object.values(severityData).length > 0 ? Object.values(severityData) : [1],
                    backgroundColor: ['#dc2626', '#ea580c', '#d97706', '#059669'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 10 }, usePointStyle: true } } }
            }
        });
    }

    const trendCtx = document.getElementById('trendChart')?.getContext('2d');
    if (trendCtx) {
        const trendData = @json($dailyTrends ?? []);
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: Object.keys(trendData),
                datasets: [{
                    data: Object.values(trendData),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.05)',
                    tension: 0.3, fill: true, pointRadius: 1, borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 9 } } },
                    x: { ticks: { font: { size: 8 }, maxTicksLimit: 10 } }
                }
            }
        });
    }

    // ==========================================
    // FEED FILTERS
    // ==========================================

    document.querySelectorAll('.feed-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('.feed-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.dataset.filter;
            const feedContainer = document.getElementById('incidentFeed');
            const loadingEl = document.getElementById('feedLoading');

            // Show loading
            if (loadingEl) loadingEl.classList.remove('d-none');

            // Build URL with filter parameter
            let url = '/dashboard?feed_filter=' + filter;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success && data.data && data.data.recent_incidents) {
                    const incidents = data.data.recent_incidents;

                    if (incidents.length > 0) {
                        feedContainer.innerHTML = incidents.map(incident => `
                            <div class="feed-item" onclick="window.location='/incidents/${incident.id}'">
                                <div class="d-flex gap-2">
                                    <img src="${incident.reporter?.avatar_url || '/images/default-avatar.png'}"
                                        class="rounded-circle flex-shrink-0" width="36" height="36" style="object-fit:cover;">
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span class="fw-semibold small">${escapeHtml(incident.reporter?.name || 'Anonymous')}</span>
                                                <span class="badge ms-1 small" style="background:${(incident.department?.color || '#6B7280')}15;color:${incident.department?.color || '#6B7280'}">
                                                    ${incident.department?.code || 'N/A'}
                                                </span>
                                            </div>
                                            <small class="text-muted flex-shrink-0">${incident.created_at_diff || ''}</small>
                                        </div>
                                        <div class="fw-medium small mt-1">${escapeHtml((incident.title || '').substring(0, 60))}</div>
                                        <div class="d-flex gap-1 mt-1">
                                            <span class="badge status-${(incident.status || '').replace(/_/g, '-')} small">${(incident.status || '').replace(/_/g, ' ')}</span>
                                            <span class="badge priority-${incident.priority || 'medium'} small">${incident.priority || 'Medium'}</span>
                                            ${incident.is_overdue ? '<span class="badge bg-danger small">Overdue</span>' : ''}
                                            ${(incident.escalated_to == {{ Auth::id() }} && incident.status === 'escalated') ? '<span class="badge bg-warning text-dark small">⚠ Action Needed</span>' : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        feedContainer.innerHTML = `
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
                                <small>No ${filter === 'all' ? '' : filter} incidents found</small>
                            </div>`;
                    }
                }
            })
            .catch(error => {
                console.error('Feed filter error:', error);
                feedContainer.innerHTML = `
                    <div class="text-center py-4 text-danger">
                        <small>Failed to load incidents. Please try again.</small>
                    </div>`;
            })
            .finally(() => {
                // Hide loading
                if (loadingEl) loadingEl.classList.add('d-none');
            });
        });
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

});
</script>
@endpush