{{-- resources/views/reports/sla.blade.php --}}
@extends('layouts.app')

@section('title', 'SLA Report - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0">SLA Compliance Report</h4>
        </div>
    </div>

    {{-- SLA Overview --}}
    <div class="row g-3 mb-3">
        @php
            $overallSla = !empty($slaData) ? round(array_sum($slaData) / count($slaData), 2) : 0;
        @endphp
        <div class="col-md-4">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body p-4">
                    <div class="display-5 fw-bold {{ $overallSla >= 80 ? 'text-success' : ($overallSla >= 50 ? 'text-warning' : 'text-danger') }}">
                        {{ $overallSla }}%
                    </div>
                    <p class="text-muted mb-0">Overall SLA Compliance</p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><strong>Department-wise SLA Compliance</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Department</th>
                                    <th>SLA Compliance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slaData as $dept => $compliance)
                                    <tr>
                                        <td>{{ $dept }}</td>
                                        <td>{{ $compliance }}%</td>
                                        <td>
                                            <span class="badge {{ $compliance >= 80 ? 'bg-success' : ($compliance >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ $compliance >= 80 ? 'Good' : ($compliance >= 50 ? 'Average' : 'Poor') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Breaches --}}
    @if(!empty($slaBreaches))
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong><i class="fas fa-exclamation-triangle text-danger me-2"></i>SLA Breaches</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Incident ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Breach Count</th>
                            <th>SLA Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slaBreaches as $breach)
                            <tr>
                                <td><span class="badge bg-light text-dark">{{ $breach['incident_id'] }}</span></td>
                                <td>{{ Str::limit($breach['title'], 50) }}</td>
                                <td>{{ $breach['department'] }}</td>
                                <td>{{ $breach['category'] }}</td>
                                <td><span class="badge bg-danger">{{ $breach['breach_count'] }}</span></td>
                                <td>{{ $breach['sla_due_at'] }}</td>
                                <td><span class="badge status-{{ str_replace('_', '-', $breach['status']) }}">{{ str_replace('_', ' ', ucfirst($breach['status'])) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection