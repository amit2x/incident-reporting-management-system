{{-- resources/views/admin/users/show.blade.php --}}
@extends('layouts.app')

@section('title', $user->name . ' - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
    </div>

    <div class="row g-3">
        {{-- Profile Card --}}
        <div class="col-lg-4">
            <div class="card shadow-sm text-center">
                <div class="card-body p-4">
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" 
                         class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted small mb-2">{{ $user->email }}</p>
                    <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'danger' : 'warning') }} mb-3">
                        {{ ucfirst($user->status) }}
                    </span>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <div class="fw-bold">{{ $stats['reported'] }}</div>
                                <small class="text-muted">Reported</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <div class="fw-bold">{{ $stats['assigned'] }}</div>
                                <small class="text-muted">Assigned</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <div class="fw-bold text-success">{{ $stats['resolved'] }}</div>
                                <small class="text-muted">Resolved</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <div class="fw-bold text-warning">{{ $stats['open'] }}</div>
                                <small class="text-muted">Open</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-1">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit User
                        </a>
                        <a href="{{ route('admin.users.activity', $user) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-history me-1"></i> Activity Log
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Details --}}
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><strong>User Details</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Username</small>
                            <div class="fw-medium">{{ $user->username }}</div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Employee ID</small>
                            <div class="fw-medium">{{ $user->employee_id ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Phone</small>
                            <div class="fw-medium">{{ $user->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Designation</small>
                            <div class="fw-medium">{{ $user->designation ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Department</small>
                            <div class="fw-medium">
                                @if($user->department)
                                    <span class="badge" style="background: {{ $user->department->color }}20; color: {{ $user->department->color }};">
                                        {{ $user->department->name }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Roles</small>
                            <div>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-light text-dark me-1">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Last Login</small>
                            <div class="fw-medium">{{ $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Never' }}</div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Last Login IP</small>
                            <div class="fw-medium">{{ $user->last_login_ip ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted">Created At</small>
                            <div class="fw-medium">{{ $user->created_at->format('d M Y, H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection