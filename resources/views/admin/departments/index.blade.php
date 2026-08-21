{{-- resources/views/admin/departments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Department Management - IRMS')

@push('styles')
<style>
    .department-card {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    .department-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .department-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .filter-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .filter-section .form-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .filter-section .form-control,
    .filter-section .form-select {
        font-size: 0.8125rem;
        border-radius: 8px;
    }
    .stat-box {
        background: white;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        border: 1px solid #e5e7eb;
    }
    .stat-box .number {
        font-size: 1.25rem;
        font-weight: 700;
    }
    .stat-box .label {
        font-size: 0.65rem;
        color: #6b7280;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">
    
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1">Department Management</h4>
            <p class="text-muted small mb-0">Manage departments and their settings</p>
        </div>
        <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Department
        </a>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="number text-primary">{{ $departments->count() }}</div>
                <div class="label">Total Departments</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="number text-success">{{ $departments->where('is_active', true)->count() }}</div>
                <div class="label">Active</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="number text-danger">{{ $departments->where('is_active', false)->count() }}</div>
                <div class="label">Inactive</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="number text-warning">{{ $departments->sum('users_count') }}</div>
                <div class="label">Total Users</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-section">
        <form method="GET" action="{{ route('admin.departments.index') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                {{-- Search --}}
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search departments..." 
                           value="{{ request('search') }}">
                </div>

                {{-- Status --}}
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Has Users --}}
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">Has Users</label>
                    <select name="has_users" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="1" {{ request('has_users') == '1' ? 'selected' : '' }}>With Users</option>
                        <option value="0" {{ request('has_users') == '0' ? 'selected' : '' }}>Without Users</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                        <option value="users_desc" {{ request('sort') == 'users_desc' ? 'selected' : '' }}>Most Users</option>
                        <option value="users_asc" {{ request('sort') == 'users_asc' ? 'selected' : '' }}>Least Users</option>
                        <option value="incidents_desc" {{ request('sort') == 'incidents_desc' ? 'selected' : '' }}>Most Incidents</option>
                        <option value="incidents_asc" {{ request('sort') == 'incidents_asc' ? 'selected' : '' }}>Least Incidents</option>
                        <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Newest</option>
                        <option value="created_asc" {{ request('sort') == 'created_asc' ? 'selected' : '' }}>Oldest</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="col-md-2 col-sm-6">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-1 col-sm-6">
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-light btn-sm w-100">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Departments Grid --}}
    <div class="row g-3">
        @forelse($departments as $department)
            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card shadow-sm h-100 department-card" style="border-left-color: {{ $department->color }} !important;">
                    <div class="card-body d-flex flex-column">
                        {{-- Header --}}
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="department-icon" 
                                 style="background: {{ $department->color }}20; color: {{ $department->color }};">
                                <i class="{{ $department->icon ?? 'fas fa-building' }}"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h6 class="mb-0 text-dark">{{ $department->name }}</h6>
                                <small class="text-muted">{{ $department->code }}</small>
                            </div>
                            <div class="flex-shrink-0">
                                @if(!$department->is_active)
                                    <span class="badge bg-danger">Inactive</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Stats --}}
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
                        
                        {{-- HOD Info --}}
                        @php
                            $hod = $department->getHeadOfDepartment();
                        @endphp
                        @if($hod)
                            <div class="d-flex align-items-center gap-2 mb-2 small">
                                <i class="fas fa-user-tie text-muted"></i>
                                <span class="text-muted">HOD:</span>
                                <span class="fw-medium">{{ $hod->name }}</span>
                            </div>
                        @endif
                        
                        {{-- Actions --}}
                        <div class="d-flex gap-1 mt-auto pt-2 border-top">
                            <a href="{{ route('admin.departments.show', $department) }}" 
                               class="btn btn-light btn-sm flex-grow-1"
                               title="View Details">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                            <a href="{{ route('admin.departments.edit', $department) }}" 
                               class="btn btn-outline-primary btn-sm flex-grow-1"
                               title="Edit Department">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <form action="{{ route('admin.departments.destroy', $department) }}" 
                                  method="POST" 
                                  class="d-inline flex-grow-0"
                                  onsubmit="return confirm('Delete this department? This will also remove all associated users and incidents.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-light btn-sm text-danger" 
                                        title="Delete Department"
                                        {{ $department->users_count > 0 || $department->incidents_count > 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        
                        @if($department->users_count > 0 || $department->incidents_count > 0)
                            <small class="text-muted mt-1" style="font-size: 0.6rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                Cannot delete: {{ $department->users_count > 0 ? $department->users_count . ' users' : '' }}
                                {{ $department->users_count > 0 && $department->incidents_count > 0 ? ' & ' : '' }}
                                {{ $department->incidents_count > 0 ? $department->incidents_count . ' incidents' : '' }}
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-sitemap fa-3x mb-3 d-block"></i>
                    <h5>No departments found</h5>
                    <p class="mb-3">Create your first department to get started</p>
                    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create First Department
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($departments->hasPages())
        <div class="mt-4">
            {{ $departments->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(function(el) {
        if (!el.hasAttribute('data-bs-toggle')) {
            el.setAttribute('data-bs-toggle', 'tooltip');
            el.setAttribute('data-bs-placement', 'top');
        }
    });

    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }

    // Auto-submit on filter change (optional)
    document.querySelectorAll('#filterForm select').forEach(function(el) {
        el.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
</script>
@endpush