{{-- resources/views/admin/airports/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Airport Details')
@section('page-title', 'Airport Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Airport Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Code</dt>
                            <dd class="col-sm-8"><strong>{{ $airport->code }}</strong></dd>

                            <dt class="col-sm-4">IATA Code</dt>
                            <dd class="col-sm-8">{{ $airport->iata_code ?? '-' }}</dd>

                            <dt class="col-sm-4">ICAO Code</dt>
                            <dd class="col-sm-8">{{ $airport->icao_code ?? '-' }}</dd>

                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">{{ $airport->name }}</dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span
                                    class="status-badge {{ $airport->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $airport->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </dl>
                    </div>

                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">City</dt>
                            <dd class="col-sm-8">{{ $airport->city }}</dd>

                            <dt class="col-sm-4">State</dt>
                            <dd class="col-sm-8">{{ $airport->state ?? '-' }}</dd>

                            <dt class="col-sm-4">Country</dt>
                            <dd class="col-sm-8">{{ $airport->country }}</dd>

                            <dt class="col-sm-4">Timezone</dt>
                            <dd class="col-sm-8">{{ $airport->timezone }}</dd>

                            <dt class="col-sm-4">Location</dt>
                            <dd class="col-sm-8">
                                @if($airport->latitude && $airport->longitude)
                                {{ $airport->latitude }}, {{ $airport->longitude }}
                                @else
                                -
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                @if($airport->description)
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6 class="border-bottom pb-2">Description</h6>
                        <p>{{ $airport->description }}</p>
                    </div>
                </div>
                @endif

                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6 class="border-bottom pb-2">Contact Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><i class="fas fa-envelope me-2"></i> {{ $airport->contact_email ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-phone me-2"></i> {{ $airport->contact_phone ?? '-' }}</p>
                            </div>
                            <div class="col-md-12">
                                <p><i class="fas fa-globe me-2"></i> {{ $airport->website ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Statistics Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i> Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Total Incidents
                        <span class="badge bg-primary rounded-pill">{{ $statistics['total_incidents'] ?? 0 }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Open Incidents
                        <span class="badge bg-warning rounded-pill">{{ $statistics['open_incidents'] ?? 0 }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Total Users
                        <span class="badge bg-info rounded-pill">{{ $statistics['total_users'] ?? 0 }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        Departments
                        <span class="badge bg-success rounded-pill">{{ $statistics['total_departments'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Incidents -->
        @if($recentIncidents->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i> Recent Incidents
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($recentIncidents as $incident)
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $incident->title }}</h6>
                            <small>{{ $incident->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-1 small text-muted">
                            <span class="badge bg-{{ $incident->severity_color }}">{{ $incident->severity }}</span>
                            {{ $incident->status }}
                        </p>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection