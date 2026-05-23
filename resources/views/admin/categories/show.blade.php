{{-- resources/views/admin/categories/show.blade.php --}}
@extends('layouts.app')

@section('title', $category->name . ' - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="mb-3">
        <a href="{{ route('admin.categories.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Categories
        </a>
        <h4 class="fw-bold mt-1 mb-0">
            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: {{ $category->color }}; margin-right: 8px;"></span>
            {{ $category->name }}
        </h4>
    </div>

    <div class="row g-3">
        {{-- Details --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Category Details</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Slug</td><td class="fw-medium">{{ $category->slug }}</td></tr>
                        <tr><td class="text-muted">Description</td><td>{{ $category->description ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Parent</td><td>{{ $category->parent?->name ?? 'None' }}</td></tr>
                        <tr><td class="text-muted">SLA</td><td><span class="badge bg-info">{{ $category->sla_formatted }}</span></td></tr>
                        <tr><td class="text-muted">Default Priority</td><td>
                            <span class="badge priority-{{ $category->default_priority == 4 ? 'critical' : ($category->default_priority == 3 ? 'high' : ($category->default_priority == 2 ? 'medium' : 'low')) }}">
                                {{ $category->default_priority == 4 ? 'Critical' : ($category->default_priority == 3 ? 'High' : ($category->default_priority == 2 ? 'Medium' : 'Low')) }}
                            </span>
                        </td></tr>
                        <tr><td class="text-muted">Approval Required</td><td>{{ $category->requires_approval ? 'Yes' : 'No' }}</td></tr>
                        <tr><td class="text-muted">Status</td><td>
                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td></tr>
                        <tr><td class="text-muted">Total Incidents</td><td class="fw-bold">{{ $category->incidents_count }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Incidents --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Recent Incidents in this Category</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Reported</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($category->incidents as $incident)
                                    <tr>
                                        <td><span class="badge bg-light text-dark">{{ $incident->incident_id }}</span></td>
                                        <td>
                                            <a href="{{ route('incidents.show', $incident) }}" class="text-decoration-none">
                                                {{ Str::limit($incident->title, 50) }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge status-{{ str_replace('_', '-', $incident->status) }}">
                                                {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                                            </span>
                                        </td>
                                        <td><small class="text-muted">{{ $incident->created_at->diffForHumans() }}</small></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No incidents in this category</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sub Categories --}}
            @if($category->children->count() > 0)
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white"><strong>Sub Categories</strong></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($category->children as $child)
                            <div class="col-md-6">
                                <a href="{{ route('admin.categories.show', $child) }}" class="text-decoration-none">
                                    <div class="border rounded p-2 d-flex align-items-center gap-2">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $child->color }};"></span>
                                        <span class="small">{{ $child->name }}</span>
                                        <span class="badge bg-light text-dark ms-auto">{{ $child->incidents_count }}</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
