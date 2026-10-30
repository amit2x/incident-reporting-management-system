@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Date</label>
                    <input type="date" name="date" value="{{ $date }}"
                           max="{{ now()->toDateString() }}"
                           min="{{ now()->subDays(7)->toDateString() }}"
                           class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Name, employee ID, email"
                           class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-3">
        <div class="col"><div class="card text-center p-2"><div class="text-muted small">Total</div><div class="h4 mb-0">{{ $stats['total'] }}</div></div></div>
        <div class="col"><div class="card text-center p-2 border-success"><div class="text-muted small">Present</div><div class="h4 mb-0 text-success">{{ $stats['present'] }}</div></div></div>
        <div class="col"><div class="card text-center p-2 border-danger"><div class="text-muted small">Absent</div><div class="h4 mb-0 text-danger">{{ $stats['absent'] }}</div></div></div>
        <div class="col"><div class="card text-center p-2 border-warning"><div class="text-muted small">Unmarked</div><div class="h4 mb-0 text-warning">{{ $stats['unmarked'] }}</div></div></div>
        <div class="col"><div class="card text-center p-2 border-info"><div class="text-muted small">Self-marked</div><div class="h4 mb-0 text-info">{{ $stats['self'] }}</div></div></div>
    </div>

    {{-- Main Form --}}
    <form method="POST" action="{{ route('nodal.attendance.bulk') }}" id="bulkForm">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">
                        <i class="fas fa-check-double me-1"></i> Select All Visible
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllBtn">
                        <i class="fas fa-times me-1"></i> Clear Selection
                    </button>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary" id="selectedBadge">0 selected</span>
                    
                    <select name="status" class="form-select form-select-sm" style="width: auto;" required>
                        <option value="present" selected>Mark as Present</option>
                        <option value="absent">Mark as Absent</option>
                        <option value="late">Mark as Late</option>
                        <option value="half_day">Mark as Half Day</option>
                        <option value="on_leave">Mark as On Leave</option>
                    </select>
                    
                    <input type="text" name="remarks" class="form-control form-control-sm" 
                           placeholder="Remarks (optional)" style="width: 200px;">
                           
                    <button type="submit" class="btn btn-sm btn-success" id="submitBulkBtn" disabled>
                        <i class="fas fa-save me-1"></i> Save Selected
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            @php $att = $u->todayAttendance ?? null; @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" 
                                           name="user_ids[]" 
                                           value="{{ $u->id }}" 
                                           class="form-check-input user-checkbox">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="text-muted small">{{ $u->employee_id ?? '—' }}</div>
                                </td>
                                <td class="small">{{ $u->department?->name ?? '—' }}</td>
                                <td>
                                    @if($att)
                                        <span class="badge bg-{{ $att->status_badge }}">
                                            {{ ucfirst(str_replace('_',' ', $att->status)) }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted">Unmarked</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($att)
                                        {{ $att->marked_by_label }}
                                        @if($att->is_modified)
                                            <i class="fas fa-pen text-warning ms-1" title="Modified"></i>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small">{{ $att?->marked_at?->format('h:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted p-4">No employees found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $users->links() }}</div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const selectedBadge = document.getElementById('selectedBadge');
    const submitBtn = document.getElementById('submitBulkBtn');

    function updateSelectionCount() {
        const checked = document.querySelectorAll('.user-checkbox:checked').length;
        selectedBadge.textContent = `${checked} selected`;
        submitBtn.disabled = checked === 0;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionCount);
    });

    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateSelectionCount();
    });

    clearAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateSelectionCount();
    });

    // Form validation
    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.user-checkbox:checked').length;
        if (checked === 0) {
            e.preventDefault();
            alert('Please select at least one employee.');
        }
    });

    updateSelectionCount();
});
</script>
@endpush