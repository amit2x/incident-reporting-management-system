@extends('layouts.app')

@section('title', 'KPI Dashboard - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">KPI Dashboard</li>
@endsection

@section('content')
<div class="container-fluid p-3">
    
    {{-- Date Range Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form id="kpiFilterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" name="period" id="periodSelect">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7days" selected>Last 7 Days</option>
                        <option value="last30days">Last 30 Days</option>
                        <option value="thisMonth">This Month</option>
                        <option value="lastMonth">Last Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 custom-date-range" style="display: none;">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="date_from">
                </div>
                <div class="col-md-3 custom-date-range" style="display: none;">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="date_to">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary w-100" id="exportBtn">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-primary border-start border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.6875rem;">TOTAL INCIDENTS</p>
                            <h3 class="mb-0" id="kpiTotal">{{ $kpiData['total_incidents'] ?? 0 }}</h3>
                            <small class="text-success" id="kpiTotalTrend">
                                <i class="fas fa-arrow-up"></i> 12%
                            </small>
                        </div>
                        <div class="kpi-icon bg-primary bg-opacity-10 rounded-circle p-2">
                            <i class="fas fa-clipboard-list text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-danger border-start border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.6875rem;">OPEN</p>
                            <h3 class="mb-0" id="kpiOpen">{{ $kpiData['open_incidents'] ?? 0 }}</h3>
                            <small class="text-danger" id="kpiOpenTrend">
                                <i class="fas fa-exclamation-circle"></i> Needs attention
                            </small>
                        </div>
                        <div class="kpi-icon bg-danger bg-opacity-10 rounded-circle p-2">
                            <i class="fas fa-folder-open text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-success border-start border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.6875rem;">RESOLVED</p>
                            <h3 class="mb-0" id="kpiResolved">{{ $kpiData['resolved_incidents'] ?? 0 }}</h3>
                            <small class="text-success" id="kpiResolvedTrend">
                                <i class="fas fa-arrow-up"></i> 8%
                            </small>
                        </div>
                        <div class="kpi-icon bg-success bg-opacity-10 rounded-circle p-2">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-warning border-start border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.6875rem;">AVG RESPONSE</p>
                            <h3 class="mb-0" id="kpiAvgResponse">{{ round($kpiData['avg_response_time'] ?? 0, 1) }}min</h3>
                            <small class="text-warning" id="kpiResponseTrend">
                                <i class="fas fa-clock"></i> Target: 30min
                            </small>
                        </div>
                        <div class="kpi-icon bg-warning bg-opacity-10 rounded-circle p-2">
                            <i class="fas fa-stopwatch text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-info border-start border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.6875rem;">SLA COMPLIANCE</p>
                            <h3 class="mb-0" id="kpiSlaCompliance">{{ round($kpiData['sla_compliance'] ?? 0, 1) }}%</h3>
                            <small class="text-info" id="kpiSlaTrend">
                                <i class="fas fa-chart-line"></i> Good
                            </small>
                        </div>
                        <div class="kpi-icon bg-info bg-opacity-10 rounded-circle p-2">
                            <i class="fas fa-percentage text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card border-secondary border-start border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.6875rem;">ESCALATED</p>
                            <h3 class="mb-0" id="kpiEscalated">{{ $kpiData['escalated_incidents'] ?? 0 }}</h3>
                            <small class="text-secondary" id="kpiEscalatedTrend">
                                <i class="fas fa-arrow-up"></i> 2%
                            </small>
                        </div>
                        <div class="kpi-icon bg-secondary bg-opacity-10 rounded-circle p-2">
                            <i class="fas fa-level-up-alt text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Charts Row 1 --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Incident Trends</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light active chart-period" data-period="daily">Daily</button>
                        <button class="btn btn-light chart-period" data-period="weekly">Weekly</button>
                        <button class="btn btn-light chart-period" data-period="monthly">Monthly</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Severity Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Charts Row 2 --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Status Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Department Performance</h6>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Category Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Heat Map & Hourly Distribution --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Incident Heat Map (Hourly Distribution)</h6>
                </div>
                <div class="card-body">
                    <div id="heatmapContainer" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">SLA Compliance by Department</h6>
                </div>
                <div class="card-body">
                    <canvas id="slaChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Detailed Tables --}}
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Top Incident Categories</h6>
                    <button class="btn btn-light btn-sm" onclick="exportTable('categoryTable')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="categoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Total</th>
                                    <th>Open</th>
                                    <th>Resolved</th>
                                    <th>Avg Response</th>
                                    <th>SLA %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryStats as $stat)
                                <tr>
                                    <td>
                                        <span class="badge" style="background: {{ $stat['color'] }}20; color: {{ $stat['color'] }}">
                                            <i class="{{ $stat['icon'] }} me-1"></i>
                                            {{ $stat['name'] }}
                                        </span>
                                    </td>
                                    <td>{{ $stat['total'] }}</td>
                                    <td class="text-danger">{{ $stat['open'] }}</td>
                                    <td class="text-success">{{ $stat['resolved'] }}</td>
                                    <td>{{ round($stat['avg_response_time'], 1) }} min</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar {{ $stat['sla_compliance'] >= 80 ? 'bg-success' : ($stat['sla_compliance'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                                     style="width: {{ $stat['sla_compliance'] }}%"></div>
                                            </div>
                                            <small>{{ round($stat['sla_compliance'], 1) }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Department Overview</h6>
                    <button class="btn btn-light btn-sm" onclick="exportTable('departmentTable')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="departmentTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Department</th>
                                    <th>Total</th>
                                    <th>Active</th>
                                    <th>Resolved</th>
                                    <th>Escalated</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($departmentStats as $stat)
                                <tr>
                                    <td>
                                        <span class="badge" style="background: {{ $stat['color'] }}20; color: {{ $stat['color'] }}">
                                            {{ $stat['name'] }}
                                        </span>
                                    </td>
                                    <td>{{ $stat['total'] }}</td>
                                    <td>{{ $stat['active'] }}</td>
                                    <td>{{ $stat['resolved'] }}</td>
                                    <td>{{ $stat['escalated'] }}</td>
                                    <td>
                                        @php
                                            $performance = $stat['total'] > 0 ? ($stat['resolved'] / $stat['total']) * 100 : 0;
                                        @endphp
                                        <span class="badge {{ $performance >= 80 ? 'bg-success' : ($performance >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ round($performance, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
$(document).ready(function() {
    // Initialize all charts
    initializeTrendChart();
    initializeSeverityChart();
    initializeStatusChart();
    initializeDepartmentChart();
    initializeCategoryChart();
    initializeSlaChart();
    initializeHeatmap();
    
    // Date range toggle
    $('#periodSelect').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.custom-date-range').show();
        } else {
            $('.custom-date-range').hide();
        }
    });
    
    // Filter form submission
    $('#kpiFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadKpiData($(this).serialize());
    });
    
    // Chart period buttons
    $('.chart-period').on('click', function() {
        $('.chart-period').removeClass('active');
        $(this).addClass('active');
        updateTrendChart($(this).data('period'));
    });
});

function initializeTrendChart() {
    const ctx = document.getElementById('trendChart').getContext('2d');
    window.trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trendData['labels'] ?? []) !!},
            datasets: [{
                label: 'Total Incidents',
                data: {!! json_encode($trendData['total'] ?? []) !!},
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Resolved',
                data: {!! json_encode($trendData['resolved'] ?? []) !!},
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function initializeSeverityChart() {
    const ctx = document.getElementById('severityChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                data: {!! json_encode(array_values($severityData ?? [])) !!},
                backgroundColor: ['#DC2626', '#EF4444', '#F59E0B', '#10B981']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function initializeStatusChart() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Open', 'In Progress', 'Escalated', 'Resolved', 'Closed'],
            datasets: [{
                label: 'Incidents',
                data: {!! json_encode(array_values($statusData ?? [])) !!},
                backgroundColor: ['#3B82F6', '#8B5CF6', '#EF4444', '#10B981', '#6B7280']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function initializeDepartmentChart() {
    const ctx = document.getElementById('departmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($departmentData ?? [])) !!},
            datasets: [{
                label: 'Active',
                data: {!! json_encode(array_column($departmentData ?? [], 'active')) !!},
                backgroundColor: '#3B82F6'
            }, {
                label: 'Resolved',
                data: {!! json_encode(array_column($departmentData ?? [], 'resolved')) !!},
                backgroundColor: '#10B981'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}

function initializeCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'polarArea',
        data: {
            labels: {!! json_encode(array_keys($categoryData ?? [])) !!},
            datasets: [{
                data: {!! json_encode(array_values($categoryData ?? [])) !!},
                backgroundColor: [
                    'rgba(37, 99, 235, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(99, 102, 241, 0.7)',
                    'rgba(236, 72, 153, 0.7)',
                    'rgba(20, 184, 166, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function initializeSlaChart() {
    const ctx = document.getElementById('slaChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($slaData ?? [])) !!},
            datasets: [{
                label: 'SLA Compliance %',
                data: {!! json_encode(array_values($slaData ?? [])) !!},
                backgroundColor: function(context) {
                    const value = context.dataset.data[context.dataIndex];
                    return value >= 80 ? '#10B981' : (value >= 50 ? '#F59E0B' : '#EF4444');
                }
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                annotation: {
                    annotations: {
                        line1: {
                            type: 'line',
                            yMin: 80,
                            yMax: 80,
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 2,
                            borderDash: [5, 5]
                        }
                    }
                }
            }
        }
    });
}

function initializeHeatmap() {
    const hourlyData = {!! json_encode($hourlyDistribution ?? []) !!};
    const container = document.getElementById('heatmapContainer');
    
    let html = '<div class="heatmap-grid">';
    for (let hour = 0; hour < 24; hour++) {
        const count = hourlyData[hour] || 0;
        const intensity = Math.min(count / 10, 1); // Normalize to 0-1
        const color = `rgba(37, 99, 235, ${intensity * 0.8 + 0.2})`;
        
        html += `
            <div class="heatmap-cell" style="background: ${color};" 
                 data-bs-toggle="tooltip" 
                 title="${hour}:00 - ${hour+1}:00 | ${count} incidents">
                <small>${count}</small>
                <span>${hour}:00</span>
            </div>
        `;
    }
    html += '</div>';
    
    container.innerHTML = html;
    
    // Reinitialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
}

function updateTrendChart(period) {
    $.get('{{ route("reports.kpi") }}', { period: period }, function(response) {
        window.trendChart.data.labels = response.labels;
        window.trendChart.data.datasets[0].data = response.total;
        window.trendChart.data.datasets[1].data = response.resolved;
        window.trendChart.update();
    });
}

function loadKpiData(filters) {
    $.get('{{ route("reports.kpi") }}', filters, function(response) {
        // Update KPI cards
        $('#kpiTotal').text(response.total_incidents);
        $('#kpiOpen').text(response.open_incidents);
        $('#kpiResolved').text(response.resolved_incidents);
        $('#kpiAvgResponse').text(Math.round(response.avg_response_time) + 'min');
        $('#kpiSlaCompliance').text(Math.round(response.sla_compliance) + '%');
        $('#kpiEscalated').text(response.escalated_incidents);
        
        // Update charts
        updateAllCharts(response);
    });
}

function exportTable(tableId) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, `${tableId}_${new Date().toISOString().split('T')[0]}.xlsx`);
}

// Export report
$('#exportBtn').on('click', function() {
    const filters = $('#kpiFilterForm').serialize();
    
    Swal.fire({
        title: 'Export Report',
        text: 'Choose export format',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Excel',
        cancelButtonText: 'PDF',
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `{{ route("reports.export", "excel") }}?${filters}`;
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            window.location.href = `{{ route("reports.export", "pdf") }}?${filters}`;
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    .kpi-card {
        transition: transform 0.2s;
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
        opacity: 0.7;
    }
</style>
@endpush