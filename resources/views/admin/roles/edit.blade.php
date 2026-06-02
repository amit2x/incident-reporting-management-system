@extends('layouts.app')

@section('title', 'Edit Role - ' . $role->name)

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-edit text-primary me-2"></i>Edit Role
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Edit {{ $role->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-outline-info me-2">
                <i class="fas fa-eye me-2"></i>View Details
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $role) }}" method="POST" id="roleForm">
        @csrf
        @method('PUT')

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
                                    name="name" value="{{ old('name', $role->name) }}" required {{ $role->name ===
                                'super-admin' ? 'readonly' : '' }}>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                name="description"
                                rows="3">{{ old('description', $role->description ?? '') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Statistics -->
                        <div class="row g-3 mt-2">
                            <div class="col-sm-6">
                                <div class="border rounded-3 p-3 text-center">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-users me-1"></i>Users
                                    </h6>
                                    <h4 class="text-primary mb-0">{{ $usersCount }}</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded-3 p-3 text-center">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-key me-1"></i>Current Permissions
                                    </h6>
                                    <h4 class="text-success mb-0">{{ $permissionsCount }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-key me-2"></i>Manage Permissions
                                <span class="text-danger">*</span>
                            </h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deselectAll()">
                                    <i class="fas fa-square me-1"></i>Deselect All
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="resetPermissions()">
                                    <i class="fas fa-undo me-1"></i>Reset
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

                        <!-- Changes Alert -->
                        <div id="changesAlert" class="alert alert-info d-none">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="changesMessage"></span>
                        </div>

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
                                            $isChecked = in_array($permission->name, old('permissions',
                                            $rolePermissions));

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
                                                        data-group="group_{{ Str::slug($group) }}"
                                                        data-original="{{ $isChecked ? 'true' : 'false' }}" {{
                                                        $isChecked ? 'checked' : '' }}>
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
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-warning w-100 mb-2">
                            <i class="fas fa-save me-2"></i>Update Role
                        </button>
                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>

                <!-- Permissions Summary -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Permissions Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fas fa-check-circle text-success me-2"></i>Selected:</span>
                            <span class="badge bg-primary fs-6" id="permissionsCount">{{ count($rolePermissions)
                                }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-success" id="permissionsProgress" role="progressbar"
                                style="width: {{ $totalPermissionsCount > 0 ? (count($rolePermissions) / $totalPermissionsCount) * 100 : 0 }}%">
                            </div>
                        </div>
                        <div id="permissionChanges" class="d-none">
                            <h6 class="text-muted">Changes:</h6>
                            <div class="d-flex gap-3">
                                <small class="text-success">
                                    <i class="fas fa-plus-circle"></i>
                                    <span id="addedCount">0</span> Added
                                </small>
                                <small class="text-danger">
                                    <i class="fas fa-minus-circle"></i>
                                    <span id="removedCount">0</span> Removed
                                </small>
                            </div>
                        </div>
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
</style>
@endpush

@push('scripts')
<script>
    // Store data passed from controller
const originalPermissions = {!! json_encode($rolePermissions) !!};
const totalPermissionsCount = {{ $totalPermissionsCount }};

document.addEventListener('DOMContentLoaded', function() {
    initializeGroups();
    updatePermissionsCount();
    trackChanges();

    document.querySelectorAll('.group-check-all').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const group = this.dataset.group;
            document.querySelectorAll(`input[data-group="${group}"]`).forEach(cb => {
                cb.checked = this.checked;
            });
            updatePermissionsCount();
            trackChanges();
        });
    });

    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateGroupCheckAll(this.dataset.group);
            updatePermissionsCount();
            trackChanges();
        });
    });
});

function initializeGroups() {
    document.querySelectorAll('.group-check-all').forEach(cb => {
        updateGroupCheckAll(cb.dataset.group);
    });
}

function updateGroupCheckAll(group) {
    const total = document.querySelectorAll(`input[data-group="${group}"]`).length;
    const checked = document.querySelectorAll(`input[data-group="${group}"]:checked`).length;
    const groupCheckbox = document.getElementById(group.replace('group_', 'group_'));

    groupCheckbox.checked = total === checked;
    groupCheckbox.indeterminate = checked > 0 && checked < total;
}

function updatePermissionsCount() {
    const count = document.querySelectorAll('.permission-checkbox:checked').length;
    const percentage = totalPermissionsCount > 0 ? (count / totalPermissionsCount * 100) : 0;

    document.getElementById('permissionsCount').textContent = count;
    document.getElementById('permissionsProgress').style.width = percentage + '%';
}

function getCurrentPermissions() {
    const permissions = [];
    document.querySelectorAll('.permission-checkbox:checked').forEach(cb => {
        permissions.push(cb.value);
    });
    return permissions;
}

function trackChanges() {
    const current = getCurrentPermissions();
    const added = current.filter(p => !originalPermissions.includes(p));
    const removed = originalPermissions.filter(p => !current.includes(p));

    if (added.length > 0 || removed.length > 0) {
        document.getElementById('permissionChanges').classList.remove('d-none');
        document.getElementById('changesAlert').classList.remove('d-none');
        document.getElementById('addedCount').textContent = added.length;
        document.getElementById('removedCount').textContent = removed.length;

        let message = '';
        if (added.length > 0 && removed.length > 0) {
            message = `<strong>${added.length}</strong> added and <strong>${removed.length}</strong> removed.`;
        } else if (added.length > 0) {
            message = `<strong>${added.length}</strong> new permission(s) will be added.`;
        } else {
            message = `<strong>${removed.length}</strong> permission(s) will be removed.`;
        }
        document.getElementById('changesMessage').innerHTML = message;
    } else {
        document.getElementById('permissionChanges').classList.add('d-none');
        document.getElementById('changesAlert').classList.add('d-none');
    }
}

function selectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
    initializeGroups();
    updatePermissionsCount();
    trackChanges();
}

function deselectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
    initializeGroups();
    updatePermissionsCount();
    trackChanges();
}

function resetPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.checked = cb.dataset.original === 'true';
    });
    initializeGroups();
    updatePermissionsCount();
    trackChanges();
}

function selectByAction(action) {
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        if (cb.value.startsWith(action + '-')) cb.checked = true;
    });
    initializeGroups();
    updatePermissionsCount();
    trackChanges();
}

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', function(e) {
    const current = getCurrentPermissions();
    if (JSON.stringify(current.sort()) !== JSON.stringify(originalPermissions.sort())) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
@endpush