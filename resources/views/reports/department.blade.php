{{-- resources/views/reports/department.blade.php --}}
@extends('layouts.app')

@section('title', 'Department Report - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0">Department Report</h4>
        </div>
        <div>
            <a href="{{ route('reports.export', ['excel', 'type' => 'department']) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    {{-- Department Cards --}}
    <div class="row g-3">
        @forelse($departmentStats as $dept)
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <span class="badge-dot" style="background: {{ $dept['color'] ?? '#6B7280' }}; width: 10px; height: 10px; border-radius: 50%;"></span>
                        <strong>{{ $dept['name'] }}</strong>
                        <small class="text-muted ms-auto">{{ $dept['code'] ?? '' }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="fw-bold fs-5">{{ $dept['total'] }}</div>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="fw-bold fs-5 text-warning">{{ $dept['active'] }}</div>
                                    <small class="text-muted">Active</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="fw-bold fs-5 text-success">{{ $dept['resolved'] }}</div>
                                    <small class="text-muted">Resolved</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="fw-bold fs-5 text-danger">{{ $dept['escalated'] }}</div>
                                    <small class="text-muted">Escalated</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-1 d-flex justify-content-between">
                            <small class="text-muted">Performance</small>
                            <small class="fw-bold">{{ $dept['performance'] }}%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar {{ $dept['performance'] >= 80 ? 'bg-success' : ($dept['performance'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 style="width: {{ $dept['performance'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-building fa-3x mb-3"></i>
                    <p>No department data available</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection