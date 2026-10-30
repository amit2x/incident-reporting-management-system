@extends('layouts.app')

@section('content')
<div class="mt-2 pb-5 mb-5 mb-md-0">
    {{-- Filters --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Date</label>
                    {{-- Added onchange to auto-submit --}}
                    <input type="date" name="date" value="{{ $date }}"
                           max="{{ now()->toDateString() }}"
                           min="{{ now()->subDays(7)->toDateString() }}"
                           class="form-control form-control-sm"
                           onchange="this.form.submit()">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Department</label>
                    {{-- Added onchange to auto-submit --}}
                    <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">Search</label>
                    {{-- Kept as normal text input, user presses Enter or clicks Filter --}}
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Name, ID, email"
                           class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-2 mt-3 mt-md-0">
                    <button class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-3 text-center">
        <div class="col-4 col-md">
            <div class="card p-2 border-0 shadow-sm">
                <div class="text-muted small">Total</div>
                <div class="h5 mb-0 fw-bold">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-4 col-md">
            <div class="card p-2 border-0 shadow-sm">
                <div class="text-muted small text-success">Present</div>
                <div class="h5 mb-0 text-success fw-bold">{{ $stats['present'] }}</div>
            </div>
        </div>
        <div class="col-4 col-md">
            <div class="card p-2 border-0 shadow-sm">
                <div class="text-muted small text-danger">Absent</div>
                <div class="h5 mb-0 text-danger fw-bold">{{ $stats['absent'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card p-2 border-0 shadow-sm">
                <div class="text-muted small text-warning">Unmarked</div>
                <div class="h5 mb-0 text-warning fw-bold">{{ $stats['unmarked'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card p-2 border-0 shadow-sm">
                <div class="text-muted small text-info">Self</div>
                <div class="h5 mb-0 text-info fw-bold">{{ $stats['self'] }}</div>
            </div>
        </div>
    </div>

    {{-- Main Form --}}
    <form method="POST" action="{{ route('nodal.attendance.bulk') }}" id="bulkForm">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="card shadow-sm border-0">
            {{-- Header / Action Bar --}}
            <div class="card-header bg-white py-3 px-3">
                
                <!-- ========================================== -->
                <!-- DESKTOP VIEW (Single Row, Clean Alignment) -->
                <!-- ========================================== -->
                <div class="d-none d-md-flex justify-content-between align-items-center gap-2">
                    <!-- Left: Selection Actions -->
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="selectAllBtn">
                            <i class="fas fa-check-double me-1"></i> Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="clearAllBtn">
                            <i class="fas fa-times me-1"></i> Clear
                        </button>
                        <span class="badge bg-primary fs-6 py-2 px-3 ms-2 text-nowrap" id="selectedBadge">
                            0 selected
                        </span>
                    </div>

                    <!-- Right: Bulk Actions & Save -->
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('nodal.attendance.review', ['date' => $date]) }}" class="btn btn-sm btn-warning text-nowrap me-2">
                            <i class="fas fa-edit me-1"></i> Review & Override
                        </a>
                        
                        <select name="status" class="form-select form-select-sm" style="width: 150px;" required>
                            <option value="present" selected>Mark Present</option>
                            <option value="absent">Mark Absent</option>
                            <option value="late">Mark Late</option>
                            <option value="half_day">Mark Half Day</option>
                            <option value="on_leave">Mark On Leave</option>
                        </select>
                        
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Remarks (optional)" style="min-width: 280px;">
                        
                        <button type="submit" class="btn btn-sm btn-success text-nowrap" id="submitBulkBtnTop" disabled>
                            <i class="fas fa-save me-1"></i> Save Selected
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- MOBILE VIEW (Stacked, full-width inputs)   -->
                <!-- ========================================== -->
                <div class="d-md-none">
                    <!-- Row 1: Buttons & Badge -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="selectAllBtnMobile">
                                <i class="fas fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="clearAllBtnMobile">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                        <span class="badge bg-primary fs-6 py-2 px-3" id="selectedBadgeMobile">
                            0 selected
                        </span>
                    </div>

                    

                    <!-- Row 3: Status & Remarks -->
                    <div class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm" style="flex: 1;" required>
                            <option value="present" selected>Mark Present</option>
                            <option value="absent">Mark Absent</option>
                            <option value="late">Mark Late</option>
                            <option value="half_day">Mark Half Day</option>
                            <option value="on_leave">Mark On Leave</option>
                        </select>
                        <input type="text" name="remarks" class="form-control form-control-sm w-100" placeholder="Remarks" style="flex: 1;">
                    </div>
                    
                    <!-- Row 2: Review Button -->
                    <div class="mt-3">
                        <a href="{{ route('nodal.attendance.review', ['date' => $date]) }}" class="btn btn-sm btn-warning w-100">
                            <i class="fas fa-edit me-1"></i> Review & Override
                        </a>
                    </div>
                </div>

            </div>

            {{-- Desktop Table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table table-striped table-hover align-middle"">
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
                                    {{-- Desktop Checkbox --}}
                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="form-check-input user-checkbox desktop-checkbox" style="width: 1.2em; height: 1.2em;">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="text-muted small">{{ $u->designation ?? '—' }}</div>
                                </td>
                                <td class="small">{{ $u->department?->name ?? '—' }}</td>
                                <td>
                                    @if($att)
                                        <span class="badge bg-{{ $att->status_badge }}">{{ ucfirst(str_replace('_',' ', $att->status)) }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Unmarked</span>
                                    @endif
                                </td>
                                <td class="small">{{ $att?->marked_by_label ?? '—' }}</td>
                                <td class="small">{{ $att?->marked_at?->format('h:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted p-4">No employees found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile List View --}}
            <div class="d-md-none">
                @forelse($users as $u)
                    @php $att = $u->todayAttendance ?? null; @endphp
                    
                    <div class="d-flex align-items-start gap-3 p-3 border-bottom mb-0 w-100 clickable-row" style="cursor: pointer;">
                        <div class="pt-1">
                            {{-- Mobile Checkbox (pointer-events none to prevent double firing) --}}
                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="form-check-input user-checkbox mobile-checkbox" style="width: 20px; height: 20px; pointer-events: none;">
                        </div>
                        <div class="flex-grow-1 w-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="fw-bold text-dark">{{ $u->name }} </div>
                                @if($att)
                                    <span class="badge bg-{{ $att->status_badge }}">{{ ucfirst(str_replace('_',' ', $att->status)) }}</span>
                                @else
                                    <span class="badge bg-light text-muted border">Unmarked</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1">{{ $u->designation ?? '—' }} &middot; {{ $u->department?->name ?? '—' }}</div>
                            @if($att)
                                <div class="small mt-1 text-secondary">
                                    Marked by {{ $att->marked_by_label }} at {{ $att->marked_at?->format('h:i A') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted p-4">No employees found.</div>
                @endforelse
            </div>

            <div class="card-footer bg-white">{{ $users->appends(request()->query())->links('pagination::bootstrap-5')}}</div>
        </div>

        {{-- Mobile Sticky Bottom Bar --}}
        <div class="d-md-none fixed-bottom bg-white border-top p-3 shadow-lg" style="z-index: 1030; bottom: 60px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold fs-5" id="selectedBadgeBottom">0 selected</div>
                </div>
                <button type="submit" class="btn btn-success px-4 py-2" id="submitBulkBtnBottom" disabled>
                    <i class="fas fa-save me-1"></i> Save Attendance
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const desktopCheckboxes = document.querySelectorAll('.desktop-checkbox');
    const mobileCheckboxes = document.querySelectorAll('.mobile-checkbox');
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    
    // Select All / Clear Buttons
    const selectAllBtn = document.getElementById('selectAllBtn');
    const selectAllBtnMobile = document.getElementById('selectAllBtnMobile');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const clearAllBtnMobile = document.getElementById('clearAllBtnMobile');
    
    // Badges & Submit Buttons
    const badges = [
        document.getElementById('selectedBadge'), 
        document.getElementById('selectedBadgeMobile'), 
        document.getElementById('selectedBadgeBottom')
    ];
    const submitBtns = [
        document.getElementById('submitBulkBtnTop'), 
        document.getElementById('submitBulkBtnBottom')
    ];

    // 1. Disable hidden view checkboxes to prevent duplicates in form submit
    function handleResize() {
        const isMobile = window.innerWidth < 768; // Bootstrap md breakpoint

        if (isMobile) {
            desktopCheckboxes.forEach(cb => {
                cb.disabled = true; 
                cb.checked = false; // Clear desktop selection
            });
            mobileCheckboxes.forEach(cb => cb.disabled = false);
        } else {
            mobileCheckboxes.forEach(cb => {
                cb.disabled = true;
                cb.checked = false; // Clear mobile selection
            });
            desktopCheckboxes.forEach(cb => cb.disabled = false);
        }
        updateSelectionCount();
    }

    // 2. Update the counter badges based on enabled checkboxes only
    function updateSelectionCount() {
        const checked = document.querySelectorAll('.user-checkbox:checked:not(:disabled)').length;
        
        badges.forEach(badge => {
            if (badge) badge.textContent = `${checked} selected`;
        });
        
        submitBtns.forEach(btn => {
            if (btn) btn.disabled = checked === 0;
        });
    }

    // Initialize view state
    handleResize();
    window.addEventListener('resize', handleResize);

    // Handle Checkbox changes directly (Desktop)
    allCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionCount);
    });

    // Handle Row Click (Mobile ONLY)
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function(e) {
            const cb = this.querySelector('.mobile-checkbox');
            if (cb && !cb.disabled) {
                cb.checked = !cb.checked;
                updateSelectionCount();
            }
        });
    });

    // Desktop Select All
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            desktopCheckboxes.forEach(cb => { if(!cb.disabled) cb.checked = true; });
            updateSelectionCount();
        });
    }

    // Mobile Select All
    if (selectAllBtnMobile) {
        selectAllBtnMobile.addEventListener('click', function() {
            mobileCheckboxes.forEach(cb => { if(!cb.disabled) cb.checked = true; });
            updateSelectionCount();
        });
    }

    // Desktop Clear
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            desktopCheckboxes.forEach(cb => { if(!cb.disabled) cb.checked = false; });
            updateSelectionCount();
        });
    }

    // Mobile Clear
    if (clearAllBtnMobile) {
        clearAllBtnMobile.addEventListener('click', function() {
            mobileCheckboxes.forEach(cb => { if(!cb.disabled) cb.checked = false; });
            updateSelectionCount();
        });
    }

    // Form validation
    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.user-checkbox:checked:not(:disabled)').length;
        if (checked === 0) {
            e.preventDefault();
            alert('Please select at least one employee.');
        }
    });
});
</script>
@endpush