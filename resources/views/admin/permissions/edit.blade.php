@extends('layouts.app')

@section('title', 'Edit Permission')

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-edit text-primary me-2"></i>Edit Permission
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Edit {{ $permission->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Permission
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Permission Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-tag"></i>
                                </span>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name', $permission->name) }}" required
                                    pattern="[a-zA-Z0-9-]+">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assigned to Roles:</label>
                            @if($roles->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($roles as $role)
                                <span class="badge bg-info">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    {{ $role->name }} ({{ $role->users_count }} users)
                                </span>
                                @endforeach
                            </div>
                            @else
                            <p class="text-muted">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                Not assigned to any role
                            </p>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Permission
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection