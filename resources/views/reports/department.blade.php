{{-- resources/views/reports/department.blade.php --}}
@extends('layouts.app')

@section('title', 'Department Report - IRMS')

@push('styles')
<style>
    .dept-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 20px;
        transition: all 0.2s;
        cursor: pointer;
        height: 100%;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .dept-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .dept-card .dept-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .stat-box {
        background: #f9fafb;
        border-radius: 8px;
        padding: 10px 8px;
        text-align: center;
    }

    .stat-box .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .stat-box .stat-label {
        font-size: 0.625rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .detail-stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }

    .detail-stat-card .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .detail-stat-card .stat-label {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    @if(isset($department) && $department)
    {{-- ========================================== --}}
    {{-- DEPARTMENT DETAIL VIEW --}}
    {{-- ========================================== --}}

    @include('reports.partials.department-detail')

    @else
    {{-- ========================================== --}}
    {{-- ALL DEPARTMENTS OVERVIEW --}}
    {{-- ========================================== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0">Department Performance</h4>
            <p class="text-muted small mb-0">Click a department to view details</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.department.export', ['format' => 'excel']) }}"
                class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel me-1"></i>Excel</a>
            <a href="{{ route('reports.department.export', ['format' => 'pdf']) }}"
                class="btn btn-outline-danger btn-sm"><i class="fas fa-file-pdf me-1"></i>PDF</a>
        </div>
    </div>

    <div class="row g-3">
        @forelse($departmentStats as $dept)
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('reports.department', ['department_id' => $dept['id']]) }}"
                class="dept-card text-decoration-none" style="display:block;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="dept-icon"
                        style="background:{{ $dept['color'] ?? '#6B7280' }}15; color:{{ $dept['color'] ?? '#6B7280' }}">
                        <i class="{{ $dept['icon'] ?? 'fas fa-building' }}"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">{{ $dept['name'] }}</h6>
                        <small class="text-muted">{{ $dept['code'] }}</small>
                    </div>
                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-value">{{ $dept['total'] }}</div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-value text-warning">{{ $dept['active'] }}</div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-value text-success">{{ $dept['resolved'] }}</div>
                            <div class="stat-label">Resolved</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-value text-danger">{{ $dept['escalated'] }}</div>
                            <div class="stat-label">Escalated</div>
                        </div>
                    </div>
                </div>
                <div class="mb-1 d-flex justify-content-between"><small class="text-muted">Resolution Rate</small><small
                        class="fw-bold text-dark">{{ $dept['performance'] }}%</small></div>
                <div class="progress" style="height:6px;">
                    <div class="progress-bar {{ $dept['performance'] >= 80 ? 'bg-success' : ($dept['performance'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                        style="width:{{ $dept['performance'] }}%"></div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12 text-center py-5 text-muted"><i class="fas fa-building fa-3x mb-3 d-block"></i>
            <p>No departments found</p>
        </div>
        @endforelse
    </div>
    @endif
</div>
@endsection

@if(isset($department))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('deptTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_keys($deptTrendData ?? [])) !!},
                datasets: [{
                    label: 'Incidents', data: {!! json_encode(array_values($deptTrendData ?? [])) !!},
                    borderColor: '{{ $department->color ?? "#3b82f6" }}', backgroundColor: '{{ $department->color ?? "#3b82f6" }}15',
                    tension: 0.3, fill: true, pointRadius: 2, borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
});
</script>
@endpush
@endif