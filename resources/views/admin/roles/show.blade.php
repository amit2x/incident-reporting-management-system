@extends('layouts.app')

@section('title', 'Role Details - ' . $role->name)

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-eye text-primary me-2"></i>Role Details
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">{{ $role->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            {{-- @can('edit-roles') --}}
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning me-2">
                <i class="bi bi-pencil me-2"></i>Edit
            </a>
            {{-- @endcan --}}
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Role Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-muted" width="120">Name:</th>
                                    <td>
                                        <span class="fw-bold fs-5">{{ $role->name }}</span>
                                        @if($role->name === 'super-admin')
                                        <span class="badge bg-danger ms-2">System Role</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Guard:</th>
                                    <td>{{ $role->guard_name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Created:</th>
                                    <td>{{ $role->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center py-3">
                                            <i class="bi bi-people fs-3"></i>
                                            <h4 class="mb-0">{{ $role->users_count }}</h4>
                                            <small>Users</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center py-3">
                                            <i class="bi bi-key fs-3"></i>
                                            <h4 class="mb-0">{{ $role->permissions_count }}</h4>
                                            <small>Permissions</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>Assigned Permissions
                        <span class="badge bg-success ms-2">{{ $role->permissions_count }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($role->permissions_count > 0)
                    <div class="mb-3">
                        <input type="text" class="form-control" id="permissionSearch"
                            placeholder="Search permissions...">
                    </div>
                    <div class="accordion" id="permissionsAccordion">
                        @foreach($permissionsByGroup as $group => $groupPermissions)
                        <div class="accordion-item border mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse{{ Str::slug($group) }}">
                                    <span class="fw-bold">{{ $group }}</span>
                                    <span class="badge bg-primary rounded-pill ms-2">{{ $groupPermissions->count()
                                        }}</span>
                                </button>
                            </h2>
                            <div id="collapse{{ Str::slug($group) }}" class="accordion-collapse collapse show">
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
                                        default => 'dark'
                                        };
                                        @endphp
                                        <div class="col-md-4 col-sm-6 permission-item">
                                            <div class="border rounded-3 p-2 bg-light">
                                                <span class="badge bg-{{ $badgeClass }} me-2">{{ ucfirst($action)
                                                    }}</span>
                                                <span>{{ ucwords($resource) }}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-shield-x fs-1"></i>
                        <h5>No Permissions</h5>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Users -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Users with this Role
                        <span class="badge bg-info ms-2">{{ $role->users_count }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($role->users_count > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Employee ID</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($role->users->take(10) as $index => $user)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2"
                                                style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                <span class="text-primary fw-bold">{{ strtoupper(substr($user->name, 0,
                                                    2)) }}</span>
                                            </div>
                                            <strong>{{ $user->name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td><span class="badge bg-secondary">{{ $user->employee_id ?? 'N/A' }}</span></td>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($user->status ?? 'active') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($role->users_count > 10)
                    <div class="text-center py-3">
                        <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="btn btn-link">
                            View all {{ $role->users_count }} users
                        </a>
                    </div>
                    @endif
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-person-x fs-1"></i>
                        <h5>No Users Assigned</h5>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-activity me-2"></i>Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <div class="me-3">
                            <div class="rounded-circle bg-success p-2 text-white">
                                <i class="bi bi-plus-lg"></i>
                            </div>
                        </div>
                        <div>
                            <p class="mb-0">Role created</p>
                            <small class="text-muted">{{ $role->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @if($role->updated_at->gt($role->created_at))
                    <hr>
                    <div class="d-flex">
                        <div class="me-3">
                            <div class="rounded-circle bg-warning p-2 text-white">
                                <i class="bi bi-pencil"></i>
                            </div>
                        </div>
                        <div>
                            <p class="mb-0">Last updated</p>
                            <small class="text-muted">{{ $role->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('permissionSearch')?.addEventListener('keyup', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.permission-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(search) ? '' : 'none';
    });
});
</script>
@endpush