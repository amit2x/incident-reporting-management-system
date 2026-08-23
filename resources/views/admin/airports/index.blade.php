{{-- resources/views/admin/airports/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Manage Airports')
@section('page-title', 'Airport Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-plane me-2"></i> Airports
                <span class="badge bg-secondary">{{ $airports->total() }}</span>
            </h5>
        </div>
        <div>
            <a href="{{ route('admin.airports.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Airport
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Search and Filter -->
        <div class="row mb-3">
            <div class="col-md-12">
                <form method="GET" action="{{ route('admin.airports.index') }}" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control"
                                placeholder="Search by name, code, city, country..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('admin.airports.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Airports Grid -->
        @if($airports->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-plane-slash fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No airports found</h5>
            <p class="text-muted">Click the "Add New Airport" button to create your first airport.</p>
        </div>
        @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            @foreach($airports as $airport)
            <div class="col">
                <div class="card airport-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1">
                                    <i class="fas fa-plane me-2" style="color: {{ $airport->primary_color }}"></i>
                                    {{ $airport->name }}
                                </h5>
                                <div class="text-muted small">
                                    <strong>Code:</strong> {{ $airport->code }}
                                    @if($airport->iata_code)
                                    | <strong>IATA:</strong> {{ $airport->iata_code }}
                                    @endif
                                    @if($airport->icao_code)
                                    | <strong>ICAO:</strong> {{ $airport->icao_code }}
                                    @endif
                                </div>
                            </div>
                            <span class="status-badge {{ $airport->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $airport->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            {{ $airport->city }}, {{ $airport->state ?? $airport->country }}
                        </div>

                        @if($airport->description)
                        <p class="card-text small text-muted">{{ Str::limit($airport->description, 100) }}</p>
                        @endif

                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="fw-bold">{{ $airport->incidents()->count() }}</div>
                                    <div class="text-muted small">Incidents</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="fw-bold">{{ $airport->users()->count() }}</div>
                                    <div class="text-muted small">Users</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">{{ $airport->departments()->count() }}</div>
                                <div class="text-muted small">Departments</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100" role="group">
                            <a href="{{ route('admin.airports.show', $airport) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('admin.airports.edit', $airport) }}"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="{{ route('admin.airports.toggle-status', $airport) }}"
                                class="btn btn-sm {{ $airport->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                onclick="return confirm('Are you sure you want to {{ $airport->is_active ? 'deactivate' : 'activate' }} this airport?')">
                                <i class="fas {{ $airport->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteModal{{ $airport->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="deleteModal{{ $airport->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete airport <strong>{{ $airport->name }}</strong>?</p>
                            <p class="text-danger small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                This action cannot be undone.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form action="{{ route('admin.airports.destroy', $airport) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i> Delete Airport
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $airports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .airport-card {
        transition: transform 0.2s;
    }

    .airport-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush