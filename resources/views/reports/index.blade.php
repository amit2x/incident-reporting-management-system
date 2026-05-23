{{-- resources/views/reports/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Reports - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    <h4 class="fw-bold mb-3">Reports</h4>
    
    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('reports.kpi') }}" class="text-decoration-none">
                <div class="card shadow-sm hover-lift h-100">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="fas fa-chart-pie fa-2x text-primary"></i>
                        </div>
                        <h5>KPI Dashboard</h5>
                        <p class="text-muted small mb-0">Key performance indicators and charts</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-4">
            <a href="{{ route('reports.department') }}" class="text-decoration-none">
                <div class="card shadow-sm hover-lift h-100">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="fas fa-building fa-2x text-success"></i>
                        </div>
                        <h5>Department Report</h5>
                        <p class="text-muted small mb-0">Department-wise performance analysis</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-4">
            <a href="{{ route('reports.sla') }}" class="text-decoration-none">
                <div class="card shadow-sm hover-lift h-100">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="fas fa-stopwatch fa-2x text-warning"></i>
                        </div>
                        <h5>SLA Report</h5>
                        <p class="text-muted small mb-0">Service level agreement compliance</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection