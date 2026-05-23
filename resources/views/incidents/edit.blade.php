{{-- resources/views/incidents/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Incident #' . $incident->incident_id . ' - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('incidents.index') }}">Incidents</a></li>
    <li class="breadcrumb-item"><a href="{{ route('incidents.show', $incident) }}">{{ $incident->incident_id }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="page-enter">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Edit Incident #{{ $incident->incident_id }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('incidents.update', $incident) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="title" class="form-label required">Incident Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="{{ old('title', $incident->title) }}" required maxlength="255">
                            </div>

                            <div class="col-md-6">
                                <label for="category_id" class="form-label required">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $incident->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="department_id" class="form-label required">Department</label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id', $incident->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="severity" class="form-label required">Severity</label>
                                <select class="form-select" id="severity" name="severity" required>
                                    <option value="critical" {{ old('severity', $incident->severity) == 'critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="high" {{ old('severity', $incident->severity) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="medium" {{ old('severity', $incident->severity) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="low" {{ old('severity', $incident->severity) == 'low' ? 'selected' : '' }}>Low</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="priority" class="form-label required">Priority</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="critical" {{ old('priority', $incident->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="high" {{ old('priority', $incident->priority) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="medium" {{ old('priority', $incident->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="low" {{ old('priority', $incident->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location"
                                       value="{{ old('location', $incident->location) }}">
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label required">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required maxlength="5000">{{ old('description', $incident->description) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <a href="{{ route('incidents.show', $incident) }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Incident
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
