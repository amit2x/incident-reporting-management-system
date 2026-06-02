@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-shield-alt text-primary me-2"></i>Role Management
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Roles</li>
                </ol>
            </nav>
        </div>
        <div>
            {{-- @can('create-roles') --}}
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create New Role
            </a>
            {{-- @endcan --}}
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-shield-alt fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Roles</h6>
                            <h4 class="mb-0">{{ $totalRoles }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-success bg-opacity-10 p-3">
                                <i class="fas fa-key fa-2x text-success"></i>
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
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-info bg-opacity-10 p-3">
                                <i class="fas fa-users fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Users with Roles</h6>
                            <h4 class="mb-0">{{ $totalUsersWithRoles }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-chart-line fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Roles</h6>
                            <h4 class="mb-0">{{ $roles->total() }}</h4>
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

    <!-- Roles Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>All Roles
                    </h5>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('admin.roles.index') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control" placeholder="Search roles..."
                                value="{{ request('search') }}">
                            @if(request()->has('search'))
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </a>
                            @endif
                        </div>
                        <select name="per_page" class="form-select w-auto" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page')==10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page')==25 ? 'selected' : '' }}>25</option>
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
                                <a href="{{ route('admin.roles.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="text-decoration-none text-dark">
                                    Role Name
                                    @if(request('sort') == 'name')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                    <i class="fas fa-sort ms-1 text-muted"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Created</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                        <tr>
                            <td>{{ $roles->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3"
                                        style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <span class="text-primary fw-bold">{{ strtoupper(substr($role->name, 0, 2))
                                            }}</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $role->name }}</h6>
                                        @if($role->name === 'super-admin')
                                        <span class="badge bg-danger">System</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info cursor-pointer" data-bs-toggle="tooltip"
                                    title="Click to view permissions" style="cursor: pointer;">
                                    <i class="fas fa-key me-1"></i>
                                    {{ $role->permissions_count }} permissions
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.users.index', ['role' => $role->name]) }}"
                                    class="badge bg-secondary text-decoration-none">
                                    <i class="fas fa-user me-1"></i>
                                    {{ $role->users_count }} users
                                </a>
                            </td>
                            <td>
                                <div data-bs-toggle="tooltip" title="{{ $role->created_at->format('M d, Y H:i') }}">
                                    <small>
                                        <i class="far fa-clock me-1 text-muted"></i>
                                        {{ $role->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-outline-info"
                                        data-bs-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('edit-roles')
                                    @if(!($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')))
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-outline-warning"
                                        data-bs-toggle="tooltip" title="Edit Role">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @endcan
                                    @can('delete-roles')
                                    @if($role->name !== 'super-admin')
                                    <button type="button" class="btn btn-outline-danger delete-role-btn"
                                        data-bs-toggle="tooltip" title="Delete Role" data-role-id="{{ $role->id }}"
                                        data-role-name="{{ $role->name }}" data-users-count="{{ $role->users_count }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-shield-alt fa-3x mb-3 opacity-50"></i>
                                    <h5 class="mt-2">No roles found</h5>
                                    <p>
                                        @if(request()->has('search'))
                                        No roles match your search criteria.
                                        @else
                                        Start by creating your first role.
                                        @endif
                                    </p>
                                    @can('create-roles')
                                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create Role
                                    </a>
                                    @endcan
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
                    Showing {{ $roles->firstItem() ?? 0 }} to {{ $roles->lastItem() ?? 0 }} of {{ $roles->total() }}
                    entries
                </span>
                {{ $roles->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <p class="mb-2">Are you sure you want to delete this role?</p>
                    <h5 class="text-danger" id="deleteRoleName"></h5>
                </div>
                <div id="deleteWarning" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="deleteWarningText"></span>
                </div>
                <p class="text-muted mb-0"><small><i class="fas fa-info-circle me-1"></i>This action cannot be
                        undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Delete Role
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin: 0 2px;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .badge {
        font-weight: 500;
    }

    .table-hover tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .rounded-circle {
        transition: all 0.3s ease;
    }

    .rounded-circle:hover {
        transform: scale(1.1);
    }

    .pagination {
        margin-bottom: 0;
    }

    .input-group-text {
        border-right: 0;
    }

    .input-group .form-control {
        border-left: 0;
    }

    .input-group .form-control:focus {
        border-color: #dee2e6;
        box-shadow: none;
    }

    .input-group:focus-within {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        border-radius: 0.375rem;
    }

    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control {
        border-color: #86b7fe;
    }
</style>
@endpush

@push('scripts')
<script>
    // Store the delete route URL with a placeholder
    const deleteRouteUrl = "{{ route('admin.roles.destroy', ':id') }}";

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle delete button clicks
        document.querySelectorAll('.delete-role-btn').forEach(button => {
            button.addEventListener('click', function() {
                const roleId = this.dataset.roleId;
                const roleName = this.dataset.roleName;
                const usersCount = parseInt(this.dataset.usersCount);

                confirmDelete(roleId, roleName, usersCount);
            });
        });
    });

    function confirmDelete(roleId, roleName, usersCount) {
        // Set role name in modal
        document.getElementById('deleteRoleName').textContent = roleName;

        // Set form action with correct role ID
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = deleteRouteUrl.replace(':id', roleId);

        // Handle warning for roles with users
        const warningDiv = document.getElementById('deleteWarning');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        if (usersCount > 0) {
            warningDiv.classList.remove('d-none');
            document.getElementById('deleteWarningText').textContent =
                `This role has ${usersCount} user(s) assigned. Please reassign these users before deleting this role.`;
            confirmBtn.disabled = true;
        } else {
            warningDiv.classList.add('d-none');
            confirmBtn.disabled = false;
        }

        // Show modal
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
@endpush