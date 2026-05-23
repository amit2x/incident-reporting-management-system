{{-- resources/views/admin/departments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Department Management - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Department Management</h4>
            <p class="text-muted small mb-0">Manage departments and their settings</p>
        </div>
        <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Department
        </a>
    </div>

    <div class="row g-3">
        @forelse($departments as $department)
            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card shadow-sm h-100 border-start border-4" style="border-color: {{ $department->color }} !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 48px; height: 48px; background: {{ $department->color }}20; color: {{ $department->color }};">
                                <i class="{{ $department->icon ?? 'fas fa-building' }} fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $department->name }}</h6>
                                <small class="text-muted">{{ $department->code }}</small>
                            </div>
                            @if(!$department->is_active)
                                <span class="badge bg-danger ms-auto">Inactive</span>
                            @endif
                        </div>
                        
                        <div class="row g-1 mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="fw-bold">{{ $department->users_count ?? 0 }}</div>
                                    <small class="text-muted" style="font-size: 0.6rem;">Users</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="fw-bold">{{ $department->incidents_count ?? 0 }}</div>
                                    <small class="text-muted" style="font-size: 0.6rem;">Incidents</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.departments.show', $department) }}" class="btn btn-light btn-sm flex-grow-1">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-light btn-sm flex-grow-1">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.departments.destroy', $department) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-light btn-sm text-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-sitemap fa-3x mb-3"></i>
                    <p>No departments found</p>
                    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">Create First Department</a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection