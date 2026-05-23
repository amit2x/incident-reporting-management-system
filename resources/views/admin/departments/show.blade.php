{{-- resources/views/admin/departments/show.blade.php --}}
@extends('layouts.app')

@section('title', $department->name . ' - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="mb-3">
        <a href="{{ route('admin.departments.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Departments
        </a>
        <h4 class="fw-bold mt-1 mb-0">
            <span class="badge-dot me-2" style="background: {{ $department->color }}; display: inline-block; width: 12px; height: 12px; border-radius: 50%;"></span>
            {{ $department->name }}
        </h4>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Department Details</strong></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td class="text-muted">Code</td><td class="fw-medium">{{ $department->code }}</td></tr>
                        <tr><td class="text-muted">Description</td><td>{{ $department->description ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Email</td><td>{{ $department->email ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Phone</td><td>{{ $department->phone ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Location</td><td>{{ $department->location ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Status</td><td>
                            <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Department Users</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($department->users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $user->avatar_url }}" class="rounded-circle" width="28" height="28">
                                                {{ $user->name }}
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $user->role_name }}</span></td>
                                        <td><span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ $user->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No users in this department</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection