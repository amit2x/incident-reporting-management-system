@extends('layouts.app')

@section('title', 'User Management - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">User Management</li>
@endsection

@section('content')
<div class="container-fluid p-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>User Management
            </h5>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Add User
            </a>
        </div>
        
        <div class="card-body">
            {{-- Filters --}}
            <form class="row g-3 mb-4" method="GET">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search users..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-light btn-sm w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>

            {{-- Users Table --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" 
                                             class="rounded-circle" width="32" height="32">
                                        <div>
                                            <div class="fw-medium">{{ $user->name }}</div>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($user->department)
                                        <span class="badge" style="background: {{ $user->department->color }}20; color: {{ $user->department->color }}">
                                            {{ $user->department->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $user->role_name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-light">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-light">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" 
                                                data-bs-toggle="dropdown"></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route('admin.users.activity', $user) }}" class="dropdown-item">
                                                    <i class="fas fa-history me-2"></i>Activity Log
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" onclick="toggleUserStatus({{ $user->id }})">
                                                    <i class="fas fa-power-off me-2"></i>
                                                    {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No users found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection