@extends('layouts.app')

@section('title', 'KPI Dashboard - IRMS')

@push('styles')
<style>
    .kpi-card {
        transition: transform 0.2s;
        cursor: default;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
    }
    .kpi-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
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
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.2s;
        color: white;
    }
    .heatmap-cell:hover {
        transform: scale(1.1);
        z-index: 1;
    }
    .heatmap-cell small {
        font-weight: 600;
        font-size: 0.75rem;
    }
    .heatmap-cell span {
        font-size: 0.625rem;
        opacity: 0.8;
    }
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    .chart-container canvas {
        width: 100% !important;
        height: 100% !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">
    
    {{-- Date Range Filter --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form id="kpiFilterForm" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">Date Range</label>
                    <select class="form-select form-select-sm" name="period" id="periodSelect">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7days" selected>Last 7 Days</option>
                        <option value="last30days">Last 30 Days</option>
                        <option value="thisMonth">This Month</option>
                        <option value="lastMonth">Last Month</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">Department</label>
                    <select class="form-select form-select-sm" name="department_id">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                </div>
                <div class="col-md-2 col-sm-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="exportExcelBtn">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </button>
                </div>
                <div class="col-md-2 col-sm-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="exportPdfBtn">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- KPI Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-primary h-100">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">Total</small>
                            <h4 class="mb-0 fw-bold" id="kpiTotal">{{ $kpiData['total_incidents'] ?? 0 }}</h4>
                        </div>
                        <div class="kpi-icon bg-primary bg-opacity-10 rounded-circle">
                            <i class="fas fa-clipboard-list text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-danger h-100">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">Open</small>
                            <h4 class="mb-0 fw-bold" id="kpiOpen">{{ $kpiData['open_incidents'] ?? 0 }}</h4>
                        </div>
                        <div class="kpi-icon bg-danger bg-opacity-10 rounded-circle">
                            <i class="fas fa-folder-open text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-success h-100">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">Resolved</small>
                            <h4 class="mb-0 fw-bold" id="kpiResolved">{{ $kpiData['resolved_incidents'] ?? 0 }}</h4>
                        </div>
                        <div class="kpi-icon bg-success bg-opacity-10 rounded-circle">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-warning h-100">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">Avg Response</small>
                            <h4 class="mb-0 fw-bold" id="kpiAvgResponse">{{ round($kpiData['avg_response_time'] ?? 0, 1) }}m</h4>
                        </div>
                        <div class="kpi-icon bg-warning bg-opacity-10 rounded-circle">
                            <i class="fas fa-stopwatch text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-info h-100">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">SLA</small>
                            <h4 class="mb-0 fw-bold" id="kpiSlaCompliance">{{ round($kpiData['sla_compliance'] ?? 0, 1) }}%</h4>
                        </div>
                        <div class="kpi-icon bg-info bg-opacity-10 rounded-circle">
                            <i class="fas fa-percentage text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-start border-4 border-secondary h-100">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px;">Escalated</small>
                            <h4 class="mb-0 fw-bold" id="kpiEscalated">{{ $kpiData['escalated_incidents'] ?? 0 }}</h4>
                        </div>
                        <div class="kpi-icon bg-secondary bg-opacity-10 rounded-circle">
                            <i class="fas fa-level-up-alt text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Charts Row 1 --}}
    <div class="row g-2 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">Incident Trends</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">Severity Distribution</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="severityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Charts Row 2 --}}
    <div class="row g-2 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">Status Distribution</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">Department Performance</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">Category Distribution</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Heat Map --}}
    <div class="row g-2 mb-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">Hourly Incident Distribution</h6>
                </div>
                <div class="card-body p-3">
                    <div class="heatmap-grid" id="heatmapContainer">
                        @for($hour = 0; $hour < 24; $hour++)
                            @php $count = $hourlyDistribution[$hour] ?? 0; @endphp
                            @php 
                                $maxCount = !empty($hourlyDistribution) ? max($hourlyDistribution) : 1;
                                $intensity = $maxCount > 0 ? min($count / $maxCount, 1) : 0;
                            @endphp
                            <div class="heatmap-cell" 
                                 style="background: rgba(26, 86, 219, {{ 0.2 + ($intensity * 0.8) }});"
                                 title="{{ sprintf('%02d:00 - %02d:00 | %d incidents', $hour, ($hour+1)%24, $count) }}">
                                <small>{{ $count }}</small>
                                <span>{{ sprintf('%02d:00', $hour) }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0">SLA Compliance by Department</h6>
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="slaChart"></canvas>
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
    
    // ==========================================
    // INITIALIZE ALL CHARTS
    // ==========================================
    
    // Trend Chart
    var trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        var trendLabels = {!! json_encode(array_keys($trendData['total'] ?? [])) !!};
        var trendTotal = {!! json_encode(array_values($trendData['total'] ?? [])) !!};
        var trendResolved = {!! json_encode(array_values($trendData['resolved'] ?? [])) !!};
        
        window.trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels.length > 0 ? trendLabels : ['No Data'],
                datasets: [{
                    label: 'Total',
                    data: trendTotal.length > 0 ? trendTotal : [0],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.05)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    borderWidth: 2
                }, {
                    label: 'Resolved',
                    data: trendResolved.length > 0 ? trendResolved : [0],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, padding: 16, font: { size: 11 } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 9 }, maxTicksLimit: 10 } }
                }
            }
        });
    }

    // Severity Chart
    var severityCtx = document.getElementById('severityChart');
    if (severityCtx) {
        var sevData = {!! json_encode(array_values($severityData ?? [])) !!};
        new Chart(severityCtx, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: sevData.length > 0 ? sevData : [1, 1, 1, 1],
                    backgroundColor: ['#DC2626', '#EF4444', '#F59E0B', '#10B981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, font: { size: 10 }, usePointStyle: true } }
                }
            }
        });
    }

    // Status Chart
    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        var statusData = {!! json_encode(array_values($statusData ?? [])) !!};
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Open', 'In Progress', 'Escalated', 'Resolved', 'Closed'],
                datasets: [{
                    data: statusData.length > 0 ? statusData : [0, 0, 0, 0, 0],
                    backgroundColor: ['#3B82F6', '#8B5CF6', '#EF4444', '#10B981', '#6B7280'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 9 } } }
                }
            }
        });
    }

    // Department Chart
    var deptCtx = document.getElementById('departmentChart');
    if (deptCtx) {
        var deptLabels = {!! json_encode(array_keys($departmentData ?? [])) !!};
        var deptActive = {!! json_encode(array_column($departmentData ?? [], 'active')) !!};
        var deptResolved = {!! json_encode(array_column($departmentData ?? [], 'resolved')) !!};
        
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptLabels.length > 0 ? deptLabels : ['No Data'],
                datasets: [{
                    label: 'Active',
                    data: deptActive.length > 0 ? deptActive : [0],
                    backgroundColor: '#3B82F6',
                    borderRadius: 3
                }, {
                    label: 'Resolved',
                    data: deptResolved.length > 0 ? deptResolved : [0],
                    backgroundColor: '#10B981',
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 10, font: { size: 10 } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 9 } } }
                }
            }
        });
    }

    // Category Chart
    var catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        var catLabels = {!! json_encode(array_keys($categoryData ?? [])) !!};
        var catValues = {!! json_encode(array_values($categoryData ?? [])) !!};
        
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels.length > 0 ? catLabels : ['No Data'],
                datasets: [{
                    data: catValues.length > 0 ? catValues : [1],
                    backgroundColor: ['#3B82F6', '#EF4444', '#F59E0B', '#10B981', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, font: { size: 9 }, usePointStyle: true } }
                }
            }
        });
    }

    // SLA Chart
    var slaCtx = document.getElementById('slaChart');
    if (slaCtx) {
        var slaLabels = {!! json_encode(array_keys($slaData ?? [])) !!};
        var slaValues = {!! json_encode(array_values($slaData ?? [])) !!};
        
        new Chart(slaCtx, {
            type: 'bar',
            data: {
                labels: slaLabels.length > 0 ? slaLabels : ['No Data'],
                datasets: [{
                    label: 'SLA %',
                    data: slaValues.length > 0 ? slaValues : [0],
                    backgroundColor: slaValues.map(function(v) {
                        return v >= 80 ? '#10B981' : (v >= 50 ? '#F59E0B' : '#EF4444');
                    }),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { font: { size: 10 }, callback: function(v) { return v + '%'; } } },
                    x: { ticks: { font: { size: 9 } } }
                }
            }
        });
    }

    // ==========================================
    // FILTER FORM
    // ==========================================
    var filterForm = document.getElementById('kpiFilterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var params = new URLSearchParams(formData).toString();
            window.location.href = '{{ route("reports.kpi") }}?' + params;
        });
    }

    // ==========================================
    // EXPORT BUTTONS
    // ==========================================
    var exportExcel = document.getElementById('exportExcelBtn');
    var exportPdf = document.getElementById('exportPdfBtn');
    
    function getFilterParams() {
        var form = document.getElementById('kpiFilterForm');
        var formData = new FormData(form);
        return new URLSearchParams(formData).toString();
    }

    if (exportExcel) {
        exportExcel.addEventListener('click', function() {
            window.location.href = '{{ route("reports.export", "excel") }}?' + getFilterParams();
        });
    }
    
    if (exportPdf) {
        exportPdf.addEventListener('click', function() {
            window.location.href = '{{ route("reports.export", "pdf") }}?' + getFilterParams();
        });
    }

    // ==========================================
    // TOOLTIPS FOR HEATMAP
    // ==========================================
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.heatmap-cell').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }

});
</script>
@endpush