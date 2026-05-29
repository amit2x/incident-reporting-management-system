{{-- resources/views/reports/escalation.blade.php --}}
@extends('layouts.app')

@section('title', 'Escalation Analysis - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0"><i class="fas fa-arrow-up-right-dots text-purple me-2"
                    style="color:#7c3aed;"></i>Escalation Analysis</h4>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-danger">{{ $totalEscalated }}</div>
                <div class="stat-label">Total Escalated</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-warning">{{ $autoEscalated }}</div>
                <div class="stat-label">Auto-Escalated</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-info">{{ $manualEscalated }}</div>
                <div class="stat-label">Manual Escalated</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="quick-stat-mini">
                <div class="stat-value text-success">{{ count($escalationLevels) }}</div>
                <div class="stat-label">Levels Used</div>
            </div>
        </div>
    </div>

    {{-- Escalation by Level --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white"><strong>Escalation by Level</strong></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Level</th>
                        <th>Count</th>
                        <th>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($escalationLevels as $level => $count)
                    <tr>
                        <td><span
                                class="badge bg-{{ $level == 1 ? 'warning' : ($level == 2 ? 'danger' : ($level == 3 ? 'dark' : 'secondary')) }}">Level
                                {{ $level }}</span></td>
                        <td><strong>{{ $count }}</strong></td>
                        <td>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-danger"
                                    style="width:{{ $totalEscalated > 0 ? ($count/$totalEscalated)*100 : 0 }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Escalated Incidents --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between"><strong>Escalated Incidents</strong><small
                class="text-muted">{{ $escalatedIncidents->count() }} incidents</small></div>
        <div class="card-body p-0">
            @forelse($escalatedIncidents as $incident)
            <div class="px-3 py-2 border-bottom small d-flex justify-content-between align-items-center"
                style="cursor:pointer" onclick="window.location='{{ route('incidents.show', $incident) }}'">
                <div>
                    <span class="badge bg-light text-dark me-1">#{{ $incident->incident_id }}</span>
                    {{ Str::limit($incident->title, 40) }}
                </div>
                <div class="d-flex gap-1">
                    <span class="badge bg-light text-dark small">{{ $incident->department?->name }}</span>
                    <span class="badge bg-warning text-dark small">→ {{ $incident->escalatedTo?->name ?? 'N/A' }}</span>
                </div>
            </div>
            @empty
            <p class="text-center py-4 text-muted small">No escalated incidents</p>
            @endforelse
        </div>
    </div>
</div>
@endsection