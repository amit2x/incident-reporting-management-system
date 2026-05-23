@extends('layouts.app')

@section('title', 'Department Management - IRMS')

@section('content')
<div class="container-fluid p-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-sitemap me-2"></i>Department Management
            </h5>
            <a href="{{ route('admin.departments.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Add Department
            </a>
        </div>
        
        <div class="card-body">
            <div class="row g-3">
                @foreach($departments as $department)
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <div class="card border-start border-4" style="border-color: {{ $department->color }} !important;">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="rounded-circle p-2" style="background: {{ $department->color }}20; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                        <i class="{{ $department->icon ?? 'fas fa-building' }}" style="color: {{ $department->color }}; font-size: 1.25rem;"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $department->name }}</h6>
                                        <small class="text-muted">{{ $department->code }}</small>
                                    </div>
                                </div>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="bg-light rounded p-2 text-center">
                                            <div class="fw-bold">{{ $department->users_count }}</div>
                                            <small class="text-muted">Users</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-light rounded p-2 text-center">
                                            <div class="fw-bold">{{ $department->incidents_count }}</div>
                                            <small class="text-muted">Incidents</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.departments.show', $department) }}" class="btn btn-light btn-sm flex-grow-1">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-light btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection