@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-plus-circle text-primary me-2"></i>Create New Role
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Roles
            </a>
        </div>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST" id="roleForm">
        @csrf

        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Role Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Role Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-tag"></i>
                                </span>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name') }}" placeholder="e.g., manager, supervisor"
                                    required pattern="[a-zA-Z0-9-_]+">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Use lowercase letters, numbers, hyphens, and underscores only.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                name="description" rows="3"
                                placeholder="Brief description of this role's responsibilities">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-key me-2"></i>Assign Permissions
                                <span class="text-danger">*</span>
                            </h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deselectAll()">
                                    <i class="fas fa-square me-1"></i>Deselect All
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @error('permissions')
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                        </div>
                        @enderror

                        <!-- Quick Actions -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-bolt me-1 text-warning"></i>Quick Select:
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-success btn-sm"
                                    onclick="selectByAction('create')">
                                    <i class="fas fa-plus-circle me-1"></i>All Create
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm"
                                    onclick="selectByAction('view')">
                                    <i class="fas fa-eye me-1"></i>All View
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm"
                                    onclick="selectByAction('edit')">
                                    <i class="fas fa-edit me-1"></i>All Edit
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                    onclick="selectByAction('delete')">
                                    <i class="fas fa-trash me-1"></i>All Delete
                                </button>
                            </div>
                        </div>

                        <!-- Permission Groups -->
                        <div class="accordion" id="permissionsAccordion">
                            @foreach($permissions as $group => $groupPermissions)
                            <div class="accordion-item border mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ Str::slug($group) }}">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <div class="form-check d-flex align-items-center gap-2"
                                                onclick="event.stopPropagation()">
                                                <input class="form-check-input group-check-all" type="checkbox"
                                                    id="group_{{ Str::slug($group) }}"
                                                    data-group="group_{{ Str::slug($group) }}">
                                                <span class="fw-bold">
                                                    <i class="fas fa-folder me-2 text-warning"></i>{{ $group }}
                                                </span>
                                            </div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            {{ $groupPermissions->count() }}
                                        </span>
                                    </button>
                                </h2>
                                <div id="collapse{{ Str::slug($group) }}" class="accordion-collapse collapse show"
                                    data-bs-parent="#permissionsAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-2">
                                            @foreach($groupPermissions as $permission)
                                            @php
                                            $parts = explode('-', $permission->name);
                                            $action = $parts[0] ?? '';
                                            $resource = implode(' ', array_slice($parts, 1));

                                            $badgeClass = match($action) {
                                            'create' => 'success',
                                            'view' => 'info',
                                            'edit' => 'warning',
                                            'delete' => 'danger',
                                            'manage' => 'primary',
                                            'approve' => 'secondary',
                                            default => 'dark'
                                            };

                                            $iconClass = match($action) {
                                            'create' => 'fa-plus-circle',
                                            'view' => 'fa-eye',
                                            'edit' => 'fa-edit',
                                            'delete' => 'fa-trash',
                                            'manage' => 'fa-cogs',
                                            'approve' => 'fa-check-circle',
                                            default => 'fa-circle'
                                            };
                                            @endphp
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox" type="checkbox"
                                                        name="permissions[]" value="{{ $permission->name }}"
                                                        id="perm_{{ $permission->id }}"
                                                        data-group="group_{{ Str::slug($group) }}">
                                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                        <span class="badge bg-{{ $badgeClass }} me-1">
                                                            <i class="fas {{ $iconClass }} me-1"></i>
                                                            {{ ucfirst($action) }}
                                                        </span>
                                                        {{ ucwords($resource) }}
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Actions Card -->
                <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 20px; z-index: 1020;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>Create Role
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fas fa-check-circle text-success me-2"></i>Selected Permissions:</span>
                            <span class="badge bg-primary fs-6" id="permissionsCount">0</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" id="permissionsProgress" role="progressbar"
                                style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <span id="percentageText">0%</span> of available permissions selected
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('styles')
<style>
    .sticky-top {
        z-index: 1020;
    }

    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #0d6efd;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .accordion-item {
        border-radius: 0.5rem !important;
        overflow: hidden;
    }

    .badge {
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<script>
    // Store the total permissions count passed from controller
const totalPermissionsCount = {{ $totalPermissionsCount }};

document.addEventListener('DOMContentLoaded', function() {
    updatePermissionsCount();

    // Group check-all handler
    document.querySelectorAll('.group-check-all').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const group = this.dataset.group;
            const isChecked = this.checked;
            document.querySelectorAll(`input[data-group="${group}"]`).forEach(cb => {
                cb.checked = isChecked;
            });
            updatePermissionsCount();
        });
    });

    // Individual permission handler
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateGroupCheckAll(this.dataset.group);
            updatePermissionsCount();
        });
    });
});

function updateGroupCheckAll(group) {
    const total = document.querySelectorAll(`input[data-group="${group}"]`).length;
    const checked = document.querySelectorAll(`input[data-group="${group}"]:checked`).length;
    const groupCheckbox = document.getElementById(group.replace('group_', 'group_'));

    groupCheckbox.checked = total === checked;
    groupCheckbox.indeterminate = checked > 0 && checked < total;
}

function updatePermissionsCount() {
    const count = document.querySelectorAll('.permission-checkbox:checked').length;
    const percentage = totalPermissionsCount > 0 ? Math.round((count / totalPermissionsCount) * 100) : 0;

    document.getElementById('permissionsCount').textContent = count;
    document.getElementById('permissionsProgress').style.width = percentage + '%';
    document.getElementById('percentageText').textContent = percentage + '%';
}

function selectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
    document.querySelectorAll('.group-check-all').forEach(cb => {
        cb.checked = true;
        cb.indeterminate = false;
    });
    updatePermissionsCount();
}

function deselectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
    document.querySelectorAll('.group-check-all').forEach(cb => {
        cb.checked = false;
        cb.indeterminate = false;
    });
    updatePermissionsCount();
}

function selectByAction(action) {
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        if (cb.value.startsWith(action + '-')) {
            cb.checked = true;
        }
    });
    document.querySelectorAll('.group-check-all').forEach(cb => {
        updateGroupCheckAll(cb.dataset.group);
    });
    updatePermissionsCount();
}
</script>
@endpush