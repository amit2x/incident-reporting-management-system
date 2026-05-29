{{-- resources/views/reports/partials/department-detail.blade.php --}}


@php

$dept = $department ?? null;
$stats = $departmentStats ?? [];
$incidents = $deptIncidents ?? collect();
$users = $deptUsers ?? collect();
$categories = $deptCategories ?? collect();
$trendData = $deptTrendData ?? [];
$kpiData = $deptKpiData ?? [];
@endphp

@if(!$dept)
<div class="text-center py-5">
    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
    <h5>Department not found</h5>
    <a href="{{ route('reports.department') }}" class="btn btn-primary mt-2">Back to Departments</a>
</div>
@else


{{-- Header --}}
<div class="mb-3">
    <a href="{{ route('reports.department') }}" class="text-muted text-decoration-none small">
        <i class="fas fa-arrow-left me-1"></i> Back to All Department
    </a>
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 gap-2">
        <h4 class="fw-bold mb-0">
            <span
                style="display:inline-block;width:12px;height:12px;border-radius:3px;background:{{ $dept->color }};margin-right:8px;"></span>
            {{ $dept->name }} <small class="text-muted">({{ $dept->code }})</small>
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.department.export', array_merge(['format' => 'excel', 'department_id' => $dept->id], request()->all())) }}"
                class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a href="{{ route('reports.department.export', array_merge(['format' => 'pdf', 'department_id' => $dept->id], request()->all())) }}"
                class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.department') }}" class="row g-2 align-items-end">
            <input type="hidden" name="department_id" value="{{ $dept->id }}">

            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Period</label>
                <select class="form-select form-select-sm" name="period" onchange="this.form.submit()">
                    <option value="today" {{ $period=='today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $period=='yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last7days" {{ $period=='last7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="last30days" {{ $period=='last30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="thisMonth" {{ $period=='thisMonth' ? 'selected' : '' }}>This Month</option>
                    <option value="lastMonth" {{ $period=='lastMonth' ? 'selected' : '' }}>Last Month</option>
                    <option value="custom" {{ $period=='custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-4 custom-date" style="display:{{ $period == 'custom' ? 'block' : 'none' }}">
                <label class="form-label small mb-1 fw-semibold">Start Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2 col-sm-4 custom-date" style="display:{{ $period == 'custom' ? 'block' : 'none' }}">
                <label class="form-label small mb-1 fw-semibold">End Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Status</label>
                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open</option>
                    <option value="acknowledged" {{ request('status')=='acknowledged' ? 'selected' : '' }}>Acknowledged
                    </option>
                    <option value="in_progress" {{ request('status')=='in_progress' ? 'selected' : '' }}>In Progress
                    </option>
                    <option value="escalated" {{ request('status')=='escalated' ? 'selected' : '' }}>Escalated</option>
                    <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>Closed</option>
                    <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Category</label>
                <select class="form-select form-select-sm" name="category_id" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($allCategories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id')==$cat->id ? 'selected' : '' }}>{{
                        $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Severity</label>
                <select class="form-select form-select-sm" name="severity" onchange="this.form.submit()">
                    <option value="">All Severity</option>
                    <option value="critical" {{ request('severity')=='critical' ? 'selected' : '' }}>Critical</option>
                    <option value="high" {{ request('severity')=='high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('severity')=='medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('severity')=='low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..."
                    value="{{ request('search') }}">
            </div>

            <div class="col-md-2 col-sm-4 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="fas fa-filter me-1"></i> Apply
                </button>
                <a href="{{ route('reports.department', ['department_id' => $dept->id]) }}"
                    class="btn btn-light btn-sm">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3 col-lg-2">
        <div class="detail-stat-card">
            <div class="stat-number text-primary">{{ $kpiData['total_incidents'] ?? 0 }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="detail-stat-card">
            <div class="stat-number text-warning">{{ $kpiData['open_incidents'] ?? 0 }}</div>
            <div class="stat-label">Open</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="detail-stat-card">
            <div class="stat-number text-success">{{ $kpiData['resolved_incidents'] ?? 0 }}</div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="detail-stat-card">
            <div class="stat-number text-danger">{{ $kpiData['escalated_incidents'] ?? 0 }}</div>
            <div class="stat-label">Escalated</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="detail-stat-card">
            <div class="stat-number text-info">{{ round($kpiData['avg_response_time'] ?? 0, 1) }}m</div>
            <div class="stat-label">Avg Response</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="detail-stat-card">
            <div class="stat-number {{ ($kpiData['sla_compliance'] ?? 100) >= 80 ? 'text-success' : 'text-warning' }}">
                {{ $kpiData['sla_compliance'] ?? 100 }}%
            </div>
            <div class="stat-label">SLA</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        {{-- Users --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-2"><strong><i class="fas fa-users me-2"></i>Team ({{ $users->count()
                    }})</strong></div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto;">
                @forelse($users as $user)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <img src="{{ $user->avatar_url }}" class="rounded-circle" width="32" height="32"
                        style="object-fit:cover;">
                    <div>
                        <div class="fw-medium small">{{ $user->name }}</div><small class="text-muted">{{
                            $user->getFirstRoleName() }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3 small">No users</p>
                @endforelse
            </div>
        </div>
        {{-- Categories --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white py-2"><strong><i class="fas fa-tags me-2"></i>Categories</strong></div>
            <div class="card-body p-0">
                @forelse($categories as $cat)
                <div class="px-3 py-2 border-bottom d-flex justify-content-between">
                    <span class="small"><i class="{{ $cat->icon ?? 'fas fa-tag' }}" style="color:{{ $cat->color }}"></i>
                        {{ $cat->name }}</span>
                    <span class="badge bg-light text-dark small">{{ $cat->incidents_count ?? 0 }}</span>
                </div>
                @empty
                <p class="text-muted text-center py-3 small">No categories</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        {{-- Trend --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-2"><strong><i class="fas fa-chart-line me-2"></i>Trend</strong></div>
            <div class="card-body p-2">
                <div style="height:200px;"><canvas id="deptTrendChart"></canvas></div>
            </div>
        </div>
        {{-- Recent Incidents --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between">
                <strong><i class="fas fa-list me-2"></i>Incidents ({{ $incidents->total() }})</strong>
                <a href="{{ route('incidents.index', array_merge(['department_id' => $dept->id], request()->only(['status','severity','category_id','search']))) }}"
                    class="small">View All →</a>
            </div>
            <div class="card-body p-0">
                @forelse($incidents as $incident)
                <div class="px-3 py-2 border-bottom small d-flex justify-content-between" style="cursor:pointer"
                    onclick="window.location='{{ route('incidents.show', $incident) }}'">
                    <span><span class="badge bg-light text-dark me-1">#{{ $incident->incident_id }}</span>{{
                        Str::limit($incident->title, 40) }}</span>
                    <span class="badge status-{{ str_replace('_','-',$incident->status) }} small">{{ str_replace('_','
                        ',ucfirst($incident->status)) }}</span>
                </div>
                @empty
                <p class="text-muted text-center py-3 small">No incidents found</p>
                @endforelse
            </div>
            @if($incidents->hasPages())
            <div class="card-footer bg-white py-1">
                {{ $incidents->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</div>

@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Show/hide custom date fields
    const periodSelect = document.querySelector('[name="period"]');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            document.querySelectorAll('.custom-date').forEach(el => {
                el.style.display = this.value === 'custom' ? 'block' : 'none';
            });
        });
    }

    const ctx = document.getElementById('deptTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_keys($trendData)) !!},
                datasets: [{
                    label: 'Incidents', data: {!! json_encode(array_values($trendData)) !!},
                    borderColor: '{{ $dept->color ?? "#3b82f6" }}',
                    backgroundColor: '{{ $dept->color ?? "#3b82f6" }}15',
                    tension: 0.3, fill: true, pointRadius: 2, borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
});
</script>
@endpush