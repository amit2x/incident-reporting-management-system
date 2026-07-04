@extends('layouts.app')

@section('title', 'KPI Dashboard - IRMS')

@push('styles')
<style>
    .kpi-card {
        transition: all 0.2s ease;
        cursor: pointer;
        border-radius: 12px;
    }

    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .kpi-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 10px;
    }

    .kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .kpi-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        font-weight: 600;
    }

    .kpi-subtitle {
        font-size: 0.65rem;
        color: #9ca3af;
        margin-top: 2px;
    }

    .heatmap-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 4px;
    }

    .heatmap-cell {
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        cursor: pointer;
        transition: transform 0.2s;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .heatmap-cell:hover {
        transform: scale(1.15);
        z-index: 1;
    }

    .heatmap-cell span {
        font-size: 0.6rem;
        opacity: 0.8;
    }

    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }

    .chart-container canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .filter-bar {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .section-header h6 {
        font-weight: 700;
        margin: 0;
    }

    @media (max-width: 767.98px) {
        .kpi-value {
            font-size: 1.25rem;
        }

        .kpi-icon {
            width: 32px;
            height: 32px;
        }

        .chart-container {
            height: 220px;
        }
    }
</style>
@endpush

@section('content')
<div class="py-3">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-chart-line text-primary me-2"></i>KPI Dashboard</h4>
            <p class="text-muted small mb-0">Key performance indicators and incident analytics</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm" onclick="exportReport('excel')" title="Export Excel">
                <i class="fas fa-file-excel me-1"></i> Excel
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="exportReport('pdf')" title="Export PDF">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form id="kpiFilterForm" class="row g-2 align-items-end">
            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Period</label>
                <select class="form-select form-select-sm" name="period" id="periodSelect"
                    onchange="this.form.submit()">
                    <option value="today" {{ $period=='today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $period=='yesterday' ? 'selected' : '' }}>Yesterday</option>
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
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $departmentId==$dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="form-label small mb-1 fw-semibold">Category</label>
                <select class="form-select form-select-sm" name="category_id" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId==$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-4">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
            <div class="col-md-4 col-sm-8 ms-auto text-end">
                <small class="text-muted">
                    Showing data from <strong>{{ \Carbon\Carbon::parse($trendData['labels'][0] ?? now())->format('d M
                        Y') }}</strong>
                    to <strong>{{ \Carbon\Carbon::parse(end($trendData['labels']) ?? now())->format('d M Y') }}</strong>
                </small>
            </div>
        </form>
    </div>

    {{-- ========================================== --}}
    {{-- KPI CARDS --}}
    {{-- ========================================== --}}
    <div class="row g-2 mb-3">
        @php
        $kpiCards = [
        ['key' => 'total_incidents', 'label' => 'Total Incidents', 'icon' => 'fa-clipboard-list', 'color' => '#3B82F6',
        'col' => 'primary'],
        ['key' => 'open_incidents', 'label' => 'Open', 'icon' => 'fa-folder-open', 'color' => '#EF4444', 'col' =>
        'danger'],
        ['key' => 'resolved_incidents', 'label' => 'Resolved', 'icon' => 'fa-check-circle', 'color' => '#10B981', 'col'
        => 'success'],
        ['key' => 'avg_response_time', 'label' => 'Avg Response', 'icon' => 'fa-stopwatch', 'color' => '#F59E0B', 'col'
        => 'warning', 'suffix' => 'm'],
        ['key' => 'sla_compliance', 'label' => 'SLA Compliance', 'icon' => 'fa-percentage', 'color' => '#06B6D4', 'col'
        => 'info', 'suffix' => '%'],
        ['key' => 'escalated_incidents', 'label' => 'Escalated', 'icon' => 'fa-level-up-alt', 'color' => '#6B7280',
        'col' => 'secondary'],
        ];
        @endphp
        @foreach($kpiCards as $card)
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-{{ $card['col'] }} h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">{{ $card['label'] }}</div>
                            <div class="kpi-value mt-1" style="color:{{ $card['color'] }}">
                                {{ $kpiData[$card['key']] ?? 0 }}{{ $card['suffix'] ?? '' }}
                            </div>
                            @if(isset($card['subkey']))
                            <div class="kpi-subtitle">{{ $kpiData[$card['subkey']] ?? '' }}</div>
                            @endif
                        </div>
                        <div class="kpi-icon bg-{{ $card['col'] }} bg-opacity-10">
                            <i class="fas {{ $card['icon'] }}" style="color:{{ $card['color'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ========================================== --}}
    {{-- CHARTS ROW 1: Trends + Severity --}}
    {{-- ========================================== --}}
    <div class="row g-2 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chart-line text-primary me-1"></i> Incident Trends</h6>
                    <small class="text-muted">Created vs Resolved</small>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container"><canvas id="trendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle text-warning me-1"></i> Severity Distribution
                    </h6>
                </div>
                <div class="card-body p-2 d-flex align-items-center">
                    <div class="chart-container" style="height:250px;"><canvas id="severityChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- CHARTS ROW 2: Status + Department + Category --}}
    {{-- ========================================== --}}
    <div class="row g-2 mb-3">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-chart-bar text-info me-1"></i> Status Distribution</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-building text-success me-1"></i> Department Performance</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container"><canvas id="departmentChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-tags text-purple me-1"></i> Category Breakdown</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container"><canvas id="categoryChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- CHARTS ROW 3: Heatmap + SLA --}}
    {{-- ========================================== --}}
    <div class="row g-2 mb-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt text-primary me-1"></i> Hourly Distribution (Heatmap)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="heatmap-grid" id="heatmapContainer">
                        @foreach($hourlyDistribution as $hour => $count)
                        @php
                        $maxCount = max($hourlyDistribution) ?: 1;
                        $intensity = $maxCount > 0 ? min($count / $maxCount, 1) : 0;
                        @endphp
                        <div class="heatmap-cell"
                            style="background: rgba(26, 86, 219, {{ 0.15 + ($intensity * 0.85) }});"
                            title="{{ sprintf('%02d:00 - %02d:00 | %d incidents', $hour, ($hour+1)%24, $count) }}"
                            data-bs-toggle="tooltip">
                            <small>{{ $count }}</small>
                            <span>{{ sprintf('%02d', $hour) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-shield-alt text-success me-1"></i> SLA Compliance by Department
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container"><canvas id="slaChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // ALL CHARTS
    // ==========================================

    // Trend Chart
    const trendCtx = document.getElementById('trendChart')?.getContext('2d');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($trendData['labels'] ?? []) !!},
                datasets: [{
                    label: 'Created', data: {!! json_encode($trendData['created'] ?? []) !!},
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.05)',
                    tension: 0.3, fill: true, pointRadius: 1, borderWidth: 2
                }, {
                    label: 'Resolved', data: {!! json_encode($trendData['resolved'] ?? []) !!},
                    borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.05)',
                    tension: 0.3, fill: true, pointRadius: 1, borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 12, font: { size: 10 } } } },
                scales: { y: { beginAtZero: true }, x: { ticks: { maxTicksLimit: 15, font: { size: 9 } } } }
            }
        });
    }

    // Severity Chart
    const severityCtx = document.getElementById('severityChart')?.getContext('2d');
    if (severityCtx) {
        const sevData = {!! json_encode($severityDistribution ?? []) !!};
        new Chart(severityCtx, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{ data: [sevData.critical||0, sevData.high||0, sevData.medium||0, sevData.low||0], backgroundColor: ['#DC2626','#EF4444','#F59E0B','#10B981'], borderWidth: 0 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 }, usePointStyle: true } } }
            }
        });
    }

    // Status Chart
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        const statusData = {!! json_encode($statusDistribution ?? []) !!};
        const statusLabels = ['open','acknowledged','in_progress','escalated','resolved','closed','rejected'];
        const statusColors = ['#3B82F6','#F59E0B','#8B5CF6','#EF4444','#10B981','#6B7280','#DC2626'];
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Open','Acknowledged','In Progress','Escalated','Resolved','Closed','Rejected'],
                datasets: [{ data: statusLabels.map(l => statusData[l]||0), backgroundColor: statusColors, borderRadius: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true }, x: { ticks: { font: { size: 8 } } } }
            }
        });
    }

    // Department Chart
    const deptCtx = document.getElementById('departmentChart')?.getContext('2d');
    if (deptCtx) {
        const deptData = {!! json_encode($departmentPerformance ?? []) !!};
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptData.map(d => d.name),
                datasets: [
                    { label: 'Active', data: deptData.map(d => d.active), backgroundColor: '#3B82F6', borderRadius: 3 },
                    { label: 'Resolved', data: deptData.map(d => d.resolved), backgroundColor: '#10B981', borderRadius: 3 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 8, font: { size: 9 } } } },
                scales: { y: { beginAtZero: true }, x: { ticks: { font: { size: 8 } } } }
            }
        });
    }

    // Category Chart
    const catCtx = document.getElementById('categoryChart')?.getContext('2d');
    if (catCtx) {
        const catData = {!! json_encode($categoryBreakdown ?? []) !!};
        const colors = ['#3B82F6','#EF4444','#F59E0B','#10B981','#8B5CF6','#EC4899','#06B6D4','#F97316','#6366F1','#14B8A6'];
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catData.map(c => c.name),
                datasets: [{ data: catData.map(c => c.total), backgroundColor: colors.slice(0, catData.length), borderWidth: 0 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 8, font: { size: 9 }, usePointStyle: true } } }
            }
        });
    }

    // SLA Chart
    const slaCtx = document.getElementById('slaChart')?.getContext('2d');
    if (slaCtx) {
        const slaData = {!! json_encode($slaCompliance ?? []) !!};
        new Chart(slaCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(slaData),
                datasets: [{
                    data: Object.values(slaData),
                    backgroundColor: Object.values(slaData).map(v => v >= 80 ? '#10B981' : (v >= 50 ? '#F59E0B' : '#EF4444')),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }, x: { ticks: { font: { size: 8 } } } }
            }
        });
    }

    // ==========================================
    // EXPORT FUNCTION
    // ==========================================
    window.exportReport = function(format) {
        const params = new URLSearchParams(new FormData(document.getElementById('kpiFilterForm'))).toString();
        window.open('{{ route("reports.kpi.export", ":format") }}'.replace(':format', format) + '?' + params, '_blank');
    };

    // ==========================================
    // TOOLTIPS
    // ==========================================
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

});
</script>
@endpush