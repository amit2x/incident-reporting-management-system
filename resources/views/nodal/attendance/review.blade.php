@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-history me-2"></i> Attendance Review — {{ $date }}</h4>
        <a href="{{ route('nodal.attendance.index', ['date' => $date]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Status</th>
                        <th>Marked By</th>
                        <th>Marked At</th>
                        <th>Flags</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $a)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $a->user?->name }}</div>
                                <div class="text-muted small">{{ $a->user?->employee_id }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $a->status_badge }}">
                                    {{ ucfirst(str_replace('_',' ', $a->status)) }}
                                </span>
                            </td>
                            <td class="small">
                                {{ $a->marked_by_label }}
                                @if($a->markedBy && $a->marked_by !== $a->user_id)
                                    <div class="text-muted">{{ $a->markedBy->name }}</div>
                                @endif
                            </td>
                            <td class="small">
                                {{ $a->marked_at?->format('d M, h:i A') }}
                                @if($a->is_backfilled)
                                    <span class="badge bg-secondary ms-1">backfilled</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($a->is_modified)
                                    <span class="badge bg-warning text-dark">
                                        modified by {{ $a->modifiedBy?->name }}
                                    </span>
                                    <div class="text-muted mt-1">{{ $a->modification_reason }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#overrideModal{{ $a->id }}">
                                    <i class="fas fa-pen"></i> Override
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted p-4">No records for this date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $attendances->links() }}</div>
    </div>
</div>

{{-- Move Modals OUTSIDE the table to fix UI overlapping --}}
@foreach($attendances as $a)
    <div class="modal fade" id="overrideModal{{ $a->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('nodal.attendance.override', $a) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Override — {{ $a->user?->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">New Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['present','absent','late','half_day','on_leave'] as $s)
                                <option value="{{ $s }}" @selected($a->status === $s)>
                                    {{ ucfirst(str_replace('_',' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3" class="form-control" required minlength="10" 
                                  placeholder="Why are you overriding this attendance? (Min 10 chars)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> Save Override
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection