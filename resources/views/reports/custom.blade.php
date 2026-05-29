{{-- resources/views/reports/custom.blade.php --}}
@extends('layouts.app')

@section('title', 'Custom Report - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0"><i class="fas fa-calendar-range text-secondary me-2"></i>Custom Date Report
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.custom.export', array_merge(['format' => 'excel'], request()->all())) }}"
                class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel me-1"></i>Excel</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1 fw-semibold">Start Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}"
                        onchange="this.form.submit()">
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1 fw-semibold">End Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}"
                        onchange="this.form.submit()">
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
                    <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="open" {{ $statusFilter=='open' ? 'selected' : '' }}>Open</option>
                        <option value="resolved" {{ $statusFilter=='resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="escalated" {{ $statusFilter=='escalated' ? 'selected' : '' }}>Escalated</option>
                        <option value="closed" {{ $statusFilter=='closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <a href="{{ route('reports.custom') }}" class="btn btn-light btn-sm w-100"><i
                            class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-primary">{{ $kpiData['total_incidents'] ?? 0 }}</div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-warning">{{ $kpiData['open_incidents'] ?? 0 }}</div>
                <div class="stat-label">Open</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-success">{{ $kpiData['resolved_incidents'] ?? 0 }}</div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-danger">{{ $kpiData['escalated_incidents'] ?? 0 }}</div>
                <div class="stat-label">Escalated</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value text-info">{{ round($kpiData['avg_response_time'] ?? 0, 1) }}m</div>
                <div class="stat-label">Avg Response</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="quick-stat-mini">
                <div class="stat-value"
                    style="color:{{ ($kpiData['sla_compliance'] ?? 100) >= 80 ? '#10b981' : '#f59e0b' }}">{{
                    $kpiData['sla_compliance'] ?? 100 }}%</div>
                <div class="stat-label">SLA</div>
            </div>
        </div>
    </div>

    {{-- Incidents Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between"><strong>Incidents ({{ $incidents->total()
                }})</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Reported</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $incident)
                        <tr style="cursor:pointer" onclick="window.location='{{ route('incidents.show', $incident) }}'">
                            <td><span class="badge bg-light text-dark">{{ $incident->incident_id }}</span></td>
                            <td class="small">{{ Str::limit($incident->title, 50) }}</td>
                            <td><small>{{ $incident->category?->name }}</small></td>
                            <td><small>{{ $incident->department?->name }}</small></td>
                            <td><span class="badge status-{{ str_replace('_','-',$incident->status) }} small">{{
                                    str_replace('_',' ',ucfirst($incident->status)) }}</span></td>
                            <td><small class="text-muted">{{ $incident->created_at->format('d M Y') }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No incidents found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($incidents->hasPages())
        <div class="card-footer bg-white">{{ $incidents->appends(request()->query())->links('pagination::bootstrap-5')
            }}</div>
        @endif
    </div>
</div>
@endsection