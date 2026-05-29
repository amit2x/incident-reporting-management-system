@extends('layouts.app')

@section('title', 'Category Analysis - IRMS')

@push('styles')
<style>
    .cat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .cat-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    }

    .cat-bar {
        height: 6px;
        border-radius: 3px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .cat-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0"><i class="fas fa-tags text-warning me-2"></i>Category Analysis</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.category.export', array_merge(['format' => 'excel'], request()->all())) }}"
                class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel me-1"></i>Excel</a>
            <a href="{{ route('reports.category.export', array_merge(['format' => 'pdf'], request()->all())) }}"
                class="btn btn-outline-danger btn-sm"><i class="fas fa-file-pdf me-1"></i>PDF</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="period" onchange="this.form.submit()">
                        <option value="last7days" {{ $period=='last7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last30days" {{ $period=='last30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="thisMonth" {{ $period=='thisMonth' ? 'selected' : '' }}>This Month</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="department_id" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId==$dept->id ? 'selected' : '' }}>{{ $dept->name
                            }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <a href="{{ route('reports.category') }}" class="btn btn-light btn-sm w-100"><i
                            class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-4 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-primary">{{ $categoryStats->sum('total') }}</div>
                <div class="stat-label">Total Incidents</div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-success">{{ $categoryStats->sum('resolved') }}</div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-danger">{{ $categoryStats->sum('breached') }}</div>
                <div class="stat-label">SLA Breaches</div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-warning">{{ $categoryStats->sum('escalated') }}</div>
                <div class="stat-label">Escalated</div>
            </div>
        </div>
    </div>

    {{-- Category List --}}
    @foreach($categoryStats as $cat)
    <div class="cat-card">
        <div class="row align-items-center">
            <div class="col-md-3">
                <span class="fw-semibold"><i class="{{ $cat['icon'] ?? 'fas fa-tag' }}"
                        style="color:{{ $cat['color'] }}"></i> {{ $cat['name'] }}</span>
            </div>
            <div class="col-md-2">
                <div class="d-flex justify-content-between small">
                    <span>Total: <strong>{{ $cat['total'] }}</strong></span>
                    <span class="text-success">Resolved: <strong>{{ $cat['resolved'] }}</strong></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Resolution Rate</span><strong>{{ $cat['resolution_rate'] }}%</strong>
                </div>
                <div class="cat-bar">
                    <div class="cat-bar-fill bg-success" style="width:{{ $cat['resolution_rate'] }}%"></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span>SLA</span><strong>{{ $cat['sla_compliance'] }}%</strong>
                </div>
                <div class="cat-bar">
                    <div class="cat-bar-fill bg-info" style="width:{{ $cat['sla_compliance'] }}%"></div>
                </div>
            </div>
            <div class="col-md-2 text-end">
                <span class="badge bg-light text-dark small">{{ $cat['avg_response_time'] }}m avg</span>
                @if($cat['breached'] > 0)<span class="badge bg-danger small ms-1">{{ $cat['breached'] }}
                    breached</span>@endif
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection