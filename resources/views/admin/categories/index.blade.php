{{-- resources/views/admin/categories/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Category Management - IRMS')

@push('styles')
<style>
    .category-card {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    .category-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .category-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .category-desc {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
        max-height: 2.8em;
    }
    .sla-badge {
        font-size: 0.65rem;
        padding: 3px 8px;
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
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1">Category Management</h4>
            <p class="text-muted small mb-0">Manage incident categories, SLA settings, and priorities</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Category
            </a>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3 col-lg-2">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold fs-5 text-primary">{{ $categories->count() }}</div>
                <small class="text-muted">Total Categories</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold fs-5 text-success">{{ $categories->where('is_active', true)->count() }}</div>
                <small class="text-muted">Active</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold fs-5 text-danger">{{ $categories->where('is_active', false)->count() }}</div>
                <small class="text-muted">Inactive</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold fs-5 text-warning">{{ $categories->where('requires_approval', true)->count() }}</div>
                <small class="text-muted">Need Approval</small>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-section">
        <form method="GET" action="{{ route('admin.categories.index') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                {{-- Search --}}
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search categories..." 
                           value="{{ request('search') }}">
                </div>
    
                {{-- Status --}}
                <div class="col-md-1 col-sm-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
    
                {{-- Department --}}
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
    
                {{-- Approval --}}
                <div class="col-md-1 col-sm-6">
                    <label class="form-label">Approval</label>
                    <select name="requires_approval" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="1" {{ request('requires_approval') == '1' ? 'selected' : '' }}>Requires Approval</option>
                        <option value="0" {{ request('requires_approval') == '0' ? 'selected' : '' }}>No Approval</option>
                    </select>
                </div>
    
                {{-- Sort --}}
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                        <option value="incidents_desc" {{ request('sort') == 'incidents_desc' ? 'selected' : '' }}>Most Used</option>
                        <option value="incidents_asc" {{ request('sort') == 'incidents_asc' ? 'selected' : '' }}>Least Used</option>
                        <option value="sla_asc" {{ request('sort') == 'sla_asc' ? 'selected' : '' }}>SLA (Low to High)</option>
                        <option value="sla_desc" {{ request('sort') == 'sla_desc' ? 'selected' : '' }}>SLA (High to Low)</option>
                        <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Newest</option>
                        <option value="created_asc" {{ request('sort') == 'created_asc' ? 'selected' : '' }}>Oldest</option>
                    </select>
                </div>
    
                {{-- Per Page --}}
                <div class="col-md-1 col-sm-4">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="12" {{ request('per_page') == 12 ? 'selected' : '' }}>12</option>
                        <option value="24" {{ request('per_page') == 24 ? 'selected' : '' }}>24</option>
                        <option value="48" {{ request('per_page') == 48 ? 'selected' : '' }}>48</option>
                        <option value="96" {{ request('per_page') == 96 ? 'selected' : '' }}>96</option>
                        <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
    
                {{-- Actions --}}
                <div class="col-md-1 col-sm-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-1 col-sm-4">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-light btn-sm w-100">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Category Grid --}}
    <div class="row g-3">
        @forelse($categories as $category)
            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card shadow-sm category-card h-100" style="border-left-color: {{ $category->color }};">
                    <div class="card-body d-flex flex-column">
                        {{-- Header --}}
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <a href="{{ route('admin.categories.show', $category) }}" class="text-decoration-none">
                                <div class="category-icon" style="background: {{ $category->color }}15; color: {{ $category->color }};">
                                    <i class="{{ $category->icon ?? 'fas fa-tag' }}"></i>
                                </div>
                            </a>
                            <div class="flex-grow-1 min-width-0">
                                <a href="{{ route('admin.categories.show', $category) }}" class="text-decoration-none">
                                    <h6 class="mb-1 text-dark">{{ $category->name }}</h6>
                                </a>
                                <p class="text-muted small mb-0 category-desc">
                                    {{ $category->description ?? 'No description' }}
                                </p>
                                
                                <p class="text-info small mb-0 category-desc">
                                    {{ $category->department->name ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="dropdown flex-shrink-0">
                                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-label="Actions">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a href="{{ route('admin.categories.show', $category) }}" class="dropdown-item">
                                            <i class="fas fa-eye text-primary me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="dropdown-item">
                                            <i class="fas fa-edit text-warning me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item" {{ $category->incidents_count > 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-trash text-danger me-2"></i>
                                                Delete
                                                @if($category->incidents_count > 0)
                                                    <small class="d-block text-muted">({{ $category->incidents_count }} incidents)</small>
                                                @endif
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {{-- Stats Row --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-2 text-center">
                                    <div class="fw-bold small">{{ $category->incidents_count }}</div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Incidents</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-2 text-center">
                                    <div class="fw-bold small">
                                        @if($category->sla_minutes >= 60)
                                            {{ floor($category->sla_minutes / 60) }}h{{ $category->sla_minutes % 60 > 0 ? ' ' . $category->sla_minutes % 60 . 'm' : '' }}
                                        @else
                                            {{ $category->sla_minutes }}m
                                        @endif
                                    </div>
                                    <small class="text-muted" style="font-size: 0.65rem;">SLA</small>
                                </div>
                            </div>
                        </div>

                        {{-- Badges --}}
                        <div class="d-flex flex-wrap gap-1 mt-auto">
                            <span class="badge priority-{{ $category->default_priority == 4 ? 'critical' : ($category->default_priority == 3 ? 'high' : ($category->default_priority == 2 ? 'medium' : 'low')) }}"
                                  style="font-size: 0.65rem;">
                                {{ $category->default_priority == 4 ? 'Critical' : ($category->default_priority == 3 ? 'High' : ($category->default_priority == 2 ? 'Medium' : 'Low')) }} Priority
                            </span>

                            @if($category->requires_approval)
                                <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">
                                    <i class="fas fa-check-circle me-1"></i>Approval
                                </span>
                            @endif

                            @if(!$category->is_active)
                                <span class="badge bg-danger" style="font-size: 0.65rem;">Inactive</span>
                            @else
                                <span class="badge bg-success" style="font-size: 0.65rem;">Active</span>
                            @endif

                            @if($category->parent)
                                <span class="badge bg-light text-dark" style="font-size: 0.65rem;"
                                      title="Parent: {{ $category->parent->name }}">
                                    <i class="fas fa-level-up-alt fa-rotate-90 me-1"></i>{{ $category->parent->name }}
                                </span>
                            @endif

                            @if($category->relationLoaded('children') && $category->children->count() > 0)
                                <span class="badge bg-light text-dark" style="font-size: 0.65rem;">
                                    <i class="fas fa-sitemap me-1"></i>{{ $category->children->count() }} sub
                                </span>
                            @endif
                        </div>

                        {{-- Bottom Actions --}}
                        <div class="d-flex gap-2 mt-3 pt-2 border-top">
                            <a href="{{ route('admin.categories.show', $category) }}"
                               class="btn btn-light btn-sm flex-grow-1"
                               title="View Details">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                            <a href="{{ route('admin.categories.edit', $category) }}"
                               class="btn btn-outline-primary btn-sm flex-grow-1"
                               title="Edit Category">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-tags fa-3x mb-3 d-block mx-auto"></i>
                    <h5>No categories found</h5>
                    <p class="mb-3">Create your first incident category to get started</p>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create First Category
                    </a>
                </div>
            </div>

        @endforelse
    </div>

    {{-- Pagination --}}
    @if($categories->hasPages())
        <div class="mt-4">
            {{ $categories->appends(request()->query())->links('pagination::bootstrap-5') }}
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
    document.querySelectorAll('#filterForm select, #filterForm input').forEach(function(el) {
        el.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
</script>
@endpush