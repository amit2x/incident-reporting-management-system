{{-- resources/views/reports/sla.blade.php --}}
@extends('layouts.app')

@section('title', 'SLA Compliance Report - IRMS')

@push('styles')
<style>
    .sla-gauge {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 2rem;
        font-weight: 700;
        border: 8px solid #e5e7eb;
    }

    .sla-gauge.good {
        border-color: #10b981;
        color: #10b981;
    }

    .sla-gauge.average {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .sla-gauge.poor {
        border-color: #ef4444;
        color: #ef4444;
    }

    .breach-row {
        cursor: pointer;
        transition: background 0.15s;
    }

    .breach-row:hover {
        background: #fef2f2;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0">SLA Compliance Report</h4>
            <p class="text-muted small mb-0">Service Level Agreement tracking and breach analysis</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.sla.export', array_merge(['format' => 'excel'], request()->all())) }}"
                class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a href="{{ route('reports.sla.export', array_merge(['format' => 'pdf'], request()->all())) }}"
                class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1 fw-semibold">Period</label>
                    <select class="form-select form-select-sm" name="period" onchange="this.form.submit()">
                        <option value="last7days" {{ $period=='last7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last30days" {{ $period=='last30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="thisMonth" {{ $period=='thisMonth' ? 'selected' : '' }}>This Month</option>
                        <option value="lastMonth" {{ $period=='lastMonth' ? 'selected' : '' }}>Last Month</option>
                        <option value="thisYear" {{ $period=='thisYear' ? 'selected' : '' }}>This Year</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1 fw-semibold">Department</label>
                    <select class="form-select form-select-sm" name="department_id" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($allDepartments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId==$dept->id ? 'selected' : '' }}>{{ $dept->name
                            }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1 fw-semibold">Category</label>
                    <select class="form-select form-select-sm" name="category_id" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($allCategories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId==$cat->id ? 'selected' : '' }}>{{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <a href="{{ route('reports.sla') }}" class="btn btn-light btn-sm w-100"><i
                            class="fas fa-redo me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- SLA Overview Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4 text-center">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="text-muted small mb-2">Overall SLA Compliance</p>
                    <div class="sla-gauge {{ $overallSla >= 80 ? 'good' : ($overallSla >= 50 ? 'average' : 'poor') }}">
                        {{ $overallSla }}%
                    </div>
                    <small class="text-muted mt-2 d-block">{{ $totalIncidents }} total incidents | {{ $breachedIncidents
                        }} breached</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="display-6 fw-bold text-info">{{ round($avgResponseTime, 1) }}m</div>
                    <p class="text-muted small mb-0">Average Response Time</p>
                </div>
            </div>
            <div class="card shadow-sm mt-2">
                <div class="card-body p-3 text-center">
                    <div class="display-6 fw-bold text-success">{{ round($avgResolutionTime, 1) }}m</div>
                    <p class="text-muted small mb-0">Average Resolution Time</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2"><strong>SLA Trend</strong></div>
                <div class="card-body p-2">
                    <div style="height:180px;"><canvas id="slaTrendChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Department-wise SLA + Category SLA --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2"><strong>Department-wise SLA Compliance</strong></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>SLA %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($slaData as $dept => $compliance)
                            <tr>
                                <td class="small">{{ $dept }}</td>
                                <td><strong>{{ $compliance }}%</strong></td>
                                <td><span
                                        class="badge {{ $compliance >= 80 ? 'bg-success' : ($compliance >= 50 ? 'bg-warning' : 'bg-danger') }}">{{
                                        $compliance >= 80 ? 'Good' : ($compliance >= 50 ? 'Average' : 'Poor') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">No data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2"><strong>Category-wise SLA</strong></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Total</th>
                                <th>Breached</th>
                                <th>SLA %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($slaByCategory as $cat => $data)
                            <tr>
                                <td class="small"><i class="{{ $data['icon'] ?? 'fas fa-tag' }}"
                                        style="color:{{ $data['color'] ?? '#6B7280' }}"></i> {{ $cat }}</td>
                                <td>{{ $data['total'] }}</td>
                                <td class="text-danger">{{ $data['breached'] }}</td>
                                <td><span
                                        class="badge {{ $data['compliance'] >= 80 ? 'bg-success' : ($data['compliance'] >= 50 ? 'bg-warning' : 'bg-danger') }}">{{
                                        $data['compliance'] }}%</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Breaches --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-exclamation-triangle text-danger me-2"></i>SLA Breaches ({{ count($slaBreaches)
                }})</strong>
        </div>
        <div class="card-body p-0">
            @if(count($slaBreaches) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Incident ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Breaches</th>
                            <th>SLA Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slaBreaches as $breach)
                        <tr class="breach-row" onclick="window.location='{{ route('incidents.show', $breach['id']) }}'">
                            <td><span class="badge bg-light text-dark">{{ $breach['incident_id'] }}</span></td>
                            <td class="small">{{ Str::limit($breach['title'], 50) }}</td>
                            <td><span class="badge"
                                    style="background:{{ ($breach['department_color'] ?? '#6B7280') }}15;color:{{ $breach['department_color'] ?? '#6B7280' }}">{{
                                    $breach['department'] }}</span></td>
                            <td><span class="badge"
                                    style="background:{{ ($breach['category_color'] ?? '#6B7280') }}15;color:{{ $breach['category_color'] ?? '#6B7280' }}">{{
                                    $breach['category'] }}</span></td>
                            <td><span class="badge bg-danger">{{ $breach['breach_count'] }}</span></td>
                            <td><small class="text-muted">{{ $breach['sla_due_at'] }}</small></td>
                            <td><span class="badge status-{{ str_replace('_', '-', $breach['status']) }} small">{{
                                    str_replace('_', ' ', ucfirst($breach['status'])) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                <small>No SLA breaches found - Great job!</small>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('slaTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_keys($slaTrend ?? [])) !!},
                datasets: [{
                    data: {!! json_encode(array_values($slaTrend ?? [])) !!},
                    borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.05)',
                    tension: 0.3, fill: true, pointRadius: 1, borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } }
            }
        });
    }
});
</script>
@endpush