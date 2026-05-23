{{-- resources/views/admin/departments/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($department) ? 'Edit Department - IRMS' : 'Create Department - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="mb-3">
        <a href="{{ route('admin.departments.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Departments
        </a>
        <h4 class="fw-bold mt-1 mb-0">{{ isset($department) ? 'Edit Department' : 'Create Department' }}</h4>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ isset($department) ? route('admin.departments.update', $department) : route('admin.departments.store') }}" method="POST">
                        @csrf
                        @if(isset($department)) @method('PUT') @endif
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $department->name ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $department->code ?? '') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $department->description ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color</label>
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $department->color ?? '#3B82F6') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Icon (Font Awesome class)</label>
                                <input type="text" name="icon" class="form-control" placeholder="e.g., fas fa-building" value="{{ old('icon', $department->icon ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $department->email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $department->phone ?? '') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" value="{{ old('location', $department->location ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $department->sort_order ?? 0) }}">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" id="is_active"
                                           {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex gap-2">
                            <a href="{{ route('admin.departments.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                {{ isset($department) ? 'Update' : 'Create' }} Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection