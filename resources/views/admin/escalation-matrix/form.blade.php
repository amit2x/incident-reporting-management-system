{{-- resources/views/admin/escalation-matrix/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($escalationMatrix) ? 'Edit Escalation Entry - IRMS' : 'Create Escalation Entry - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="mb-3">
        <a href="{{ route('admin.escalation-matrix.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Escalation Matrix
        </a>
        <h4 class="fw-bold mt-1 mb-0">{{ isset($escalationMatrix) ? 'Edit Escalation Entry #' . $escalationMatrix->id : 'Create Escalation Entry' }}</h4>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ isset($escalationMatrix) ? route('admin.escalation-matrix.update', $escalationMatrix) : route('admin.escalation-matrix.store') }}" method="POST" id="escalationForm">
                        @csrf
                        @if(isset($escalationMatrix)) @method('PUT') @endif

                        {{-- Step 1: Select Department --}}
                        <h6 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-building me-2"></i>Step 1: Select Department
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" id="departmentSelect" class="form-select @error('department_id') is-invalid @enderror" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('department_id', $escalationMatrix->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Users will be filtered based on selected department</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">All Categories (Default Rule)</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_id', $escalationMatrix->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Leave empty to apply this rule to all categories</small>
                            </div>
                        </div>

                        {{-- Step 2: Escalation Level --}}
                        <h6 class="fw-bold mb-3 text-warning">
                            <i class="fas fa-layer-group me-2"></i>Step 2: Escalation Level & Timing
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Escalation Level <span class="text-danger">*</span></label>
                                <select name="level" class="form-select @error('level') is-invalid @enderror" required>
                                    <option value="">Select Level</option>
                                    <option value="1" {{ old('level', $escalationMatrix->level ?? '') == 1 ? 'selected' : '' }}>
                                        🔵 Level 1 - First Escalation (Supervisor)
                                    </option>
                                    <option value="2" {{ old('level', $escalationMatrix->level ?? '') == 2 ? 'selected' : '' }}>
                                        🟡 Level 2 - Second Escalation (HOD)
                                    </option>
                                    <option value="3" {{ old('level', $escalationMatrix->level ?? '') == 3 ? 'selected' : '' }}>
                                        🔴 Level 3 - Third Escalation (Admin/Manager)
                                    </option>
                                    <option value="4" {{ old('level', $escalationMatrix->level ?? '') == 4 ? 'selected' : '' }}>
                                        🟣 Level 4 - Final Escalation (Director)
                                    </option>
                                </select>
                                @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Timeout Before Escalation <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="timeout_minutes" id="timeoutInput"
                                           class="form-control @error('timeout_minutes') is-invalid @enderror"
                                           value="{{ old('timeout_minutes', $escalationMatrix->timeout_minutes ?? 30) }}"
                                           min="5" required>
                                    <span class="input-group-text">minutes</span>
                                </div>
                                @error('timeout_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted" id="timeoutDisplay">
                                    <i class="fas fa-clock me-1"></i>
                                    @php $mins = old('timeout_minutes', $escalationMatrix->timeout_minutes ?? 30); @endphp
                                    @if($mins >= 1440) {{ floor($mins/1440) }}d {{ floor(($mins%1440)/60) }}h
                                    @elseif($mins >= 60) {{ floor($mins/60) }}h {{ $mins%60 > 0 ? $mins%60 . 'm' : '' }}
                                    @else {{ $mins }}m @endif
                                </small>
                            </div>
                        </div>

                        {{-- Step 3: Target --}}
                        <h6 class="fw-bold mb-3 text-success">
                            <i class="fas fa-user-check me-2"></i>Step 3: Escalation Target
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Escalate To (User) <span class="text-danger">*</span></label>
                                <select name="escalate_to_user_id" id="userSelect" class="form-select @error('escalate_to_user_id') is-invalid @enderror" required>
                                    <option value="">Select Department First</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ old('escalate_to_user_id', $escalationMatrix->escalate_to_user_id ?? '') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->role_name }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('escalate_to_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted" id="userCount">Showing {{ $users->count() }} user(s)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Target Department <span class="text-danger">*</span></label>
                                <select name="escalate_to_department_id" class="form-select @error('escalate_to_department_id') is-invalid @enderror" required>
                                    <option value="">Select Department</option>
                                    @foreach($allDepartments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('escalate_to_department_id', $escalationMatrix->escalate_to_department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('escalate_to_department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Step 4: Notifications --}}
                        <h6 class="fw-bold mb-3 text-info">
                            <i class="fas fa-bell me-2"></i>Step 4: Notification Settings
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 text-center">
                                    <i class="fas fa-envelope fa-2x text-info mb-2"></i>
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input type="checkbox" name="notify_via_email" class="form-check-input" value="1" id="notify_email"
                                               {{ old('notify_via_email', $escalationMatrix->notify_via_email ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="notify_email">Email</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 text-center">
                                    <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input type="checkbox" name="notify_via_push" class="form-check-input" value="1" id="notify_push"
                                               {{ old('notify_via_push', $escalationMatrix->notify_via_push ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="notify_push">Push</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 text-center">
                                    <i class="fas fa-power-off fa-2x text-success mb-2"></i>
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input type="checkbox" name="is_active" class="form-check-input" value="1" id="is_active"
                                               {{ old('is_active', $escalationMatrix->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex gap-2 border-top pt-3">
                            <a href="{{ route('admin.escalation-matrix.index') }}" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i>
                                {{ isset($escalationMatrix) ? 'Update Entry' : 'Create Entry' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Sidebar --}}
        <div class="col-lg-4 d-none d-lg-block">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><strong><i class="fas fa-info-circle me-2"></i>Escalation Levels</strong></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-3 py-2 small">
                            <span class="level-badge level-1 d-inline-flex me-2" style="width:22px;height:22px;font-size:0.6rem;">L1</span>
                            <strong>Supervisor</strong> - First responder
                        </div>
                        <div class="list-group-item px-3 py-2 small">
                            <span class="level-badge level-2 d-inline-flex me-2" style="width:22px;height:22px;font-size:0.6rem;">L2</span>
                            <strong>HOD</strong> - Department head
                        </div>
                        <div class="list-group-item px-3 py-2 small">
                            <span class="level-badge level-3 d-inline-flex me-2" style="width:22px;height:22px;font-size:0.6rem;">L3</span>
                            <strong>Admin</strong> - Administration
                        </div>
                        <div class="list-group-item px-3 py-2 small">
                            <span class="level-badge level-4 d-inline-flex me-2" style="width:22px;height:22px;font-size:0.6rem;">L4</span>
                            <strong>Director</strong> - Top management
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    var departmentSelect = document.getElementById('departmentSelect');
    var userSelect = document.getElementById('userSelect');
    var timeoutInput = document.getElementById('timeoutInput');
    var timeoutDisplay = document.getElementById('timeoutDisplay');
    var userCount = document.getElementById('userCount');

    // ==========================================
    // FILTER USERS BY DEPARTMENT (AJAX)
    // ==========================================
    if (departmentSelect && userSelect) {
        departmentSelect.addEventListener('change', function() {
            var deptId = this.value;

            if (!deptId) {
                userSelect.innerHTML = '<option value="">Select Department First</option>';
                if (userCount) userCount.textContent = 'Select a department to see users';
                return;
            }

            // Show loading
            userSelect.innerHTML = '<option value="">Loading users...</option>';

            fetch('{{ route("admin.escalation-matrix.users-by-department") }}?department_id=' + deptId, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    userSelect.innerHTML = '<option value="">Select User</option>';

                    if (data.data.length === 0) {
                        userSelect.innerHTML += '<option value="" disabled>No active users in this department</option>';
                        if (userCount) userCount.textContent = 'No users found in this department';
                    } else {
                        data.data.forEach(function(user) {
                            userSelect.innerHTML +=
                                '<option value="' + user.id + '">' +
                                user.name + ' (' + user.role_name + ')' +
                                '</option>';
                        });
                        if (userCount) userCount.textContent = 'Showing ' + data.data.length + ' user(s) from selected department';
                    }
                }
            })
            .catch(function(error) {
                console.error('Error loading users:', error);
                userSelect.innerHTML = '<option value="">Error loading users</option>';
            });
        });
    }

    // ==========================================
    // TIMEOUT DISPLAY
    // ==========================================
    if (timeoutInput && timeoutDisplay) {
        timeoutInput.addEventListener('input', function() {
            var mins = parseInt(this.value) || 0;
            var text = '';

            if (mins >= 1440) {
                var days = Math.floor(mins / 1440);
                var hours = Math.floor((mins % 1440) / 60);
                text = days + 'd ' + (hours > 0 ? hours + 'h' : '');
            } else if (mins >= 60) {
                var hours = Math.floor(mins / 60);
                var remainingMins = mins % 60;
                text = hours + 'h ' + (remainingMins > 0 ? remainingMins + 'm' : '');
            } else {
                text = mins + 'm';
            }

            timeoutDisplay.innerHTML = '<i class="fas fa-clock me-1"></i> ' + text;
        });
    }

    // ==========================================
    // TRIGGER ON PAGE LOAD (for edit)
    // ==========================================
    @if(isset($escalationMatrix) && $escalationMatrix->department_id)
        // Pre-select department triggers user load on edit
        // Department is already selected via old() or model value
    @endif

});
</script>
@endpush
