@extends('layouts.app')

@section('title', 'Permission Management')

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-key text-primary me-2"></i>Permission Management
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>
        <div>
            {{-- @can('create-roles') --}}
            <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Permission
            </a>
            {{-- @endcan --}}
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-success bg-opacity-10 p-3">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Permissions</h6>
                            <h4 class="mb-0">{{ $totalPermissions }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                                <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Unused Permissions</h6>
                            <h4 class="mb-0">{{ $unusedPermissions }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-info bg-opacity-10 p-3">
                                <i class="fas fa-layer-group fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Permission Groups</h6>
                            <h4 class="mb-0">{{ $permissionGroups->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Permissions Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>All Permissions
                    </h5>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('admin.permissions.index') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control" placeholder="Search permissions..."
                                value="{{ request('search') }}">
                            @if(request()->has('search'))
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </a>
                            @endif
                        </div>
                        <select name="per_page" class="form-select w-auto" onchange="this.form.submit()">
                            <option value="20" {{ request('per_page')==20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('per_page')==50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page')==100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>
                                <a href="{{ route('admin.permissions.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-decoration-none text-dark">
                                    Permission Name
                                    @if(request('sort') == 'name')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Group</th>
                            <th>Roles</th>
                            <th>Guard</th>
                            <th>Created</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $permission)
                        @php
                        $parts = explode('-', $permission->name);
                        $group = ucfirst($parts[0] ?? 'Other');
                        $action = $parts[0] ?? '';

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
                        <tr>
                            <td>{{ $permissions->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-{{ $badgeClass }} me-2">
                                        <i class="fas {{ $iconClass }} me-1"></i>
                                        {{ ucfirst($action) }}
                                    </span>
                                    <span>{{ $permission->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $group }}</span>
                            </td>
                            <td>
                                @if($permission->roles_count > 0)
                                <span class="badge bg-info">{{ $permission->roles_count }} roles</span>
                                @else
                                <span class="badge bg-danger">Unused</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $permission->guard_name }}</small></td>
                            <td>
                                <small class="text-muted">{{ $permission->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @can('edit-roles')
                                    <a href="{{ route('admin.permissions.edit', $permission) }}"
                                        class="btn btn-outline-warning" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete-roles')
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="tooltip"
                                        title="Delete"
                                        onclick="confirmDeletePermission('{{ $permission->id }}', '{{ $permission->name }}', {{ $permission->roles_count }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-key fa-3x mb-3 opacity-50"></i>
                                    <h5 class="mt-2">No permissions found</h5>
                                    <p>Create your first permission to get started</p>
                                    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create Permission
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">
                    <i class="fas fa-database me-1"></i>
                    Showing {{ $permissions->firstItem() ?? 0 }} to {{ $permissions->lastItem() ?? 0 }} of {{
                    $permissions->total() }} entries
                </span>
                {{ $permissions->links('pagination::simple-bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Permission Modal -->
<div class="modal fade" id="deletePermissionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Permission
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <p>Are you sure you want to delete this permission?</p>
                    <h5 class="text-danger" id="deletePermissionName"></h5>
                </div>
                <div id="deletePermissionWarning" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="deletePermissionWarningText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form id="deletePermissionForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const deletePermissionRouteUrl = "{{ route('admin.permissions.destroy', ':id') }}";

function confirmDeletePermission(permissionId, permissionName, rolesCount) {
    document.getElementById('deletePermissionName').textContent = permissionName;
    document.getElementById('deletePermissionForm').action = deletePermissionRouteUrl.replace(':id', permissionId);

    const warningDiv = document.getElementById('deletePermissionWarning');
    const confirmBtn = document.getElementById('confirmDeleteBtn');

    if (rolesCount > 0) {
        warningDiv.classList.remove('d-none');
        document.getElementById('deletePermissionWarningText').textContent =
            `This permission is assigned to ${rolesCount} role(s). Cannot delete.`;
        confirmBtn.disabled = true;
    } else {
        warningDiv.classList.add('d-none');
        confirmBtn.disabled = false;
    }

    new bootstrap.Modal(document.getElementById('deletePermissionModal')).show();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush