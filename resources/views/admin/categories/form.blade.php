{{-- resources/views/admin/categories/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($category) ? 'Edit Category - IRMS' : 'Create Category - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="mb-3">
        <a href="{{ route('admin.categories.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Categories
        </a>
        <h4 class="fw-bold mt-1 mb-0">{{ isset($category) ? 'Edit Category' : 'Create Category' }}</h4>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST">
                        @csrf
                        @if(isset($category)) @method('PUT') @endif

                        {{-- Basic Information --}}
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-info-circle text-primary me-2"></i>Basic Information
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $category->name ?? '') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug', $category->slug ?? '') }}" placeholder="auto-generated">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Brief description of this category">{{ old('description', $category->description ?? '') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Category</label>
                                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                    <option value="">None (Top Level)</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}"
                                            {{ old('parent_id', $category->parent_id ?? '') == $parent->id ? 'selected' : '' }}
                                            {{ (isset($category) && $category->id == $parent->id) ? 'disabled' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
                                       value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">Active</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" id="is_active"
                                           {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        {{-- Appearance --}}
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-paint-brush text-success me-2"></i>Appearance
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Color <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color" name="color" class="form-control form-control-color"
                                           value="{{ old('color', $category->color ?? '#3B82F6') }}" style="width: 42px;">
                                    <input type="text" class="form-control" value="{{ old('color', $category->color ?? '#3B82F6') }}"
                                           readonly style="max-width: 100px;">
                                </div>
                                @error('color')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Icon (Font Awesome)</label>
                                <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror"
                                       value="{{ old('icon', $category->icon ?? 'fas fa-tag') }}"
                                       placeholder="e.g., fas fa-exclamation-triangle">
                                @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Font Awesome class name</small>
                            </div>
                        </div>

                        {{-- SLA & Priority Settings --}}
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-clock text-warning me-2"></i>SLA & Priority Settings
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">SLA Time (Minutes) <span class="text-danger">*</span></label>
                                <input type="number" name="sla_minutes" class="form-control @error('sla_minutes') is-invalid @enderror"
                                       value="{{ old('sla_minutes', $category->sla_minutes ?? 120) }}"
                                       min="1" required>
                                @error('sla_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">
                                    {{ isset($category) ? $category->sla_formatted : '2 hours (120 min)' }}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Default Priority <span class="text-danger">*</span></label>
                                <select name="default_priority" class="form-select @error('default_priority') is-invalid @enderror" required>
                                    <option value="1" {{ old('default_priority', $category->default_priority ?? 2) == 1 ? 'selected' : '' }}>Low (1)</option>
                                    <option value="2" {{ old('default_priority', $category->default_priority ?? 2) == 2 ? 'selected' : '' }}>Medium (2)</option>
                                    <option value="3" {{ old('default_priority', $category->default_priority ?? 2) == 3 ? 'selected' : '' }}>High (3)</option>
                                    <option value="4" {{ old('default_priority', $category->default_priority ?? 2) == 4 ? 'selected' : '' }}>Critical (4)</option>
                                </select>
                                @error('default_priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="requires_approval" class="form-check-input" value="1" id="requires_approval"
                                           {{ old('requires_approval', $category->requires_approval ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_approval">
                                        <strong>Requires Approval</strong>
                                        <br><small class="text-muted">Incidents in this category require approval before being processed</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex gap-2 border-top pt-3">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> {{ isset($category) ? 'Update Category' : 'Create Category' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Tips Sidebar --}}
        <div class="col-lg-4 d-none d-lg-block">
            <div class="card shadow-sm sticky-top" style="top: 80px;">
                <div class="card-header bg-white"><strong><i class="fas fa-info-circle me-2"></i>Tips</strong></div>
                <div class="card-body small">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>SLA Time:</strong> Maximum time allowed before the incident is considered breached.
                        </li>
                        <li class="mb-2">
                            <strong>Default Priority:</strong> Auto-assigned priority when incidents are created in this category.
                        </li>
                        <li class="mb-2">
                            <strong>Parent Category:</strong> Use for sub-categories to organize related incident types.
                        </li>
                        <li>
                            <strong>Requires Approval:</strong> Enable if incidents need supervisor/HOD approval before processing.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
