@extends('layouts.app')

@section('content')
<div class="containers py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-history text-primary "></i> My Attendance</h4>
        <a href="{{ route('attendance.mark-page') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-fingerprint me-1"></i> Mark Today's Attendance
        </a>
    </div>

    {{-- Stats Overview --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body p-3">
                    <div class="text-muted small mb-1">Total Present</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $stats['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body p-3">
                    <div class="text-muted small mb-1">Total Absent</div>
                    <div class="h3 mb-0 fw-bold text-danger">{{ $stats['absent'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body p-3">
                    <div class="text-muted small mb-1">Total Late</div>
                    <div class="h3 mb-0 fw-bold text-warning">{{ $stats['late'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance List --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Recent Records</h6>
        </div>
        <div class="card-body p-0">
            
            {{-- Desktop Table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th>Time</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                            <tr>
                                <td class="fw-semibold">{{ $att->attendance_date->format('l, d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $att->status_badge }}">
                                        {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $att->marked_by_label }}
                                    @if($att->marked_by !== $att->user_id)
                                        <span class="text-muted small">({{ $att->markedBy?->name }})</span>
                                    @endif
                                </td>
                                <td class="small">{{ $att->marked_at->format('h:i A') }}</td>
                                <td class="small text-muted">{{ $att->remarks ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile List View --}}
            <div class="d-md-none">
                @forelse($attendances as $att)
                    <div class="p-3 border-bottom d-flex flex-column gap-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-bold text-dark">{{ $att->attendance_date->format('d M Y') }}</div>
                            <span class="badge bg-{{ $att->status_badge }}">
                                {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                            </span>
                        </div>
                        <div class="text-muted small">
                            {{ $att->attendance_date->format('l') }} at {{ $att->marked_at->format('h:i A') }}
                        </div>
                        <div class="small mt-1">
                            <span class="fw-semibold">Marked by:</span> {{ $att->marked_by_label }}
                            @if($att->marked_by !== $att->user_id)
                                ({{ $att->markedBy?->name }})
                            @endif
                        </div>
                        @if($att->remarks)
                            <div class="small text-muted mt-1 fst-italic">"{{ $att->remarks }}"</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted p-4">No attendance records found.</div>
                @endforelse
            </div>

        </div>
        <div class="card-footer bg-white">
            {{ $attendances->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection