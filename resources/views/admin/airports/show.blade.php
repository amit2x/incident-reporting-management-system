{{-- resources/views/admin/airports/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Airport Details')
@section('page-title', 'Airport Details')

@push('styles')
<style>
    .hero-header {
        background: linear-gradient(135deg, {{ $airport->primary_color ?? '#3B82F6' }} 0%, rgba(0,0,0,0.8) 150%);
        color: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .hero-header::after {
        content: '\f072'; /* FontAwesome Plane */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        right: -20px;
        bottom: -40px;
        font-size: 15rem;
        opacity: 0.1;
        transform: rotate(-15deg);
    }
    .info-card {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .info-card:hover {
        transform: translateY(-2px);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
    }
    .timeline-item {
        border-left: 3px solid #e9ecef;
        padding-left: 1rem;
        margin-bottom: 1rem;
        position: relative;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6.5px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Hero Header --}}
    <div class="hero-header shadow-sm">
        <div class="d-flex justify-content-between align-items-start position-relative" style="z-index: 2;">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <h2 class="mb-0 fw-bold">{{ $airport->name }}</h2>
                    <span class="badge {{ $airport->is_active ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-2 rounded-pill">
                        <i class="fas fa-{{ $airport->is_active ? 'check-circle' : 'times-circle' }} me-1"></i>
                        {{ $airport->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="mb-0 fs-5 opacity-75">
                    <i class="fas fa-map-marker-alt me-2"></i> {{ $airport->city }}, {{ $airport->country }}
                </p>
                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <span class="badge bg-white text-dark fs-6 px-3 py-2 shadow-sm">
                        <i class="fas fa-plane text-primary me-1"></i> IATA: {{ $airport->iata_code ?? 'N/A' }}
                    </span>
                    <span class="badge bg-white text-dark fs-6 px-3 py-2 shadow-sm">
                        <i class="fas fa-broadcast-tower text-primary me-1"></i> ICAO: {{ $airport->icao_code ?? 'N/A' }}
                    </span>
                    <span class="badge bg-white text-dark fs-6 px-3 py-2 shadow-sm">
                        <i class="fas fa-clock text-primary me-1"></i> {{ $airport->timezone }}
                    </span>
                </div>
            </div>
            <div class="text-end d-none d-md-block">
                <a href="{{ route('admin.airports.edit', $airport) }}" class="btn btn-light text-dark shadow-sm fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Airport
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            
            {{-- Location & Contact --}}
            <div class="card info-card mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-info-circle text-primary me-2"></i> Airport Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Location Data</h6>
                            <div class="d-flex mb-3">
                                <div class="text-primary mt-1 me-3"><i class="fas fa-map-pin fa-lg"></i></div>
                                <div>
                                    <div class="text-muted small">Address</div>
                                    <div class="fw-semibold">{{ $airport->address ?? 'Not provided' }}</div>
                                    <div class="text-muted small mt-1">{{ $airport->city }}, {{ $airport->state ?? '' }} {{ $airport->country }}</div>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="text-primary mt-1 me-3"><i class="fas fa-crosshairs fa-lg"></i></div>
                                <div>
                                    <div class="text-muted small">Coordinates</div>
                                    <div class="fw-semibold">
                                        @if($airport->latitude && $airport->longitude)
                                            {{ $airport->latitude }}, {{ $airport->longitude }}
                                        @else
                                            Not set
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Contact Info</h6>
                            <div class="d-flex mb-3">
                                <div class="text-primary mt-1 me-3"><i class="fas fa-envelope fa-lg"></i></div>
                                <div>
                                    <div class="text-muted small">Email</div>
                                    <div class="fw-semibold">{{ $airport->contact_email ?? 'Not provided' }}</div>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="text-primary mt-1 me-3"><i class="fas fa-phone fa-lg"></i></div>
                                <div>
                                    <div class="text-muted small">Phone</div>
                                    <div class="fw-semibold">{{ $airport->contact_phone ?? 'Not provided' }}</div>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="text-primary mt-1 me-3"><i class="fas fa-globe fa-lg"></i></div>
                                <div>
                                    <div class="text-muted small">Website</div>
                                    @if($airport->website)
                                        <a href="{{ $airport->website }}" target="_blank" class="fw-semibold text-decoration-none">{{ $airport->website }}</a>
                                    @else
                                        <div class="fw-semibold">Not provided</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($airport->description)
                        <hr class="my-4 text-muted opacity-25">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">About</h6>
                        <p class="mb-0 text-secondary">{{ $airport->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Attendance Configuration --}}
            @php $att = $airport->attendanceSettings(); @endphp
            <div class="card info-card mb-4 border-start border-4 border-warning">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-fingerprint text-warning me-2"></i> Attendance Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <i class="fas fa-user-check fa-2x mb-2 {{ $att['allow_self_mark'] ? 'text-success' : 'text-danger' }}"></i>
                                <div class="small text-muted fw-bold">Self-Marking</div>
                                <div class="fw-bold">{{ $att['allow_self_mark'] ? 'Enabled' : 'Disabled' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <i class="fas fa-map-marker-alt fa-2x mb-2 {{ $att['location_check_required'] ? 'text-success' : 'text-secondary' }}"></i>
                                <div class="small text-muted fw-bold">Geofence Check</div>
                                <div class="fw-bold">{{ $att['location_check_required'] ? 'Required' : 'Not Required' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <i class="fas fa-clock fa-2x mb-2 text-primary"></i>
                                <div class="small text-muted fw-bold">Time Window</div>
                                <div class="fw-bold">{{ $att['self_mark_start_time'] }} - {{ $att['self_mark_end_time'] }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-light rounded text-center h-100">
                                <i class="fas fa-bullseye fa-2x mb-2 text-info"></i>
                                <div class="small text-muted fw-bold">Geofence Radius</div>
                                <div class="fw-bold">{{ $att['geofence_radius_meters'] }}m</div>
                            </div>
                        </div>
                        @if($att['location_check_required'])
                        <div class="col-12">
                            <div class="alert alert-info mb-0 py-2 mt-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Geofence Center: <strong>{{ $att['geofence_lat'] }}, {{ $att['geofence_lng'] }}</strong>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            
            {{-- Statistics --}}
            <div class="card info-card mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie text-success me-2"></i> System Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded bg-primary bg-opacity-10 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="stat-icon bg-primary text-white me-2"><i class="fas fa-exclamation-triangle"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-primary">{{ $statistics['total_incidents'] ?? 0 }}</h3>
                                <div class="text-muted small">Total Incidents</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-warning bg-opacity-10 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="stat-icon bg-warning text-white me-2"><i class="fas fa-fire"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-warning">{{ $statistics['open_incidents'] ?? 0 }}</h3>
                                <div class="text-muted small">Open Incidents</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-info bg-opacity-10 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="stat-icon bg-info text-white me-2"><i class="fas fa-users"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-info">{{ $statistics['total_users'] ?? 0 }}</h3>
                                <div class="text-muted small">Total Users</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-success bg-opacity-10 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="stat-icon bg-success text-white me-2"><i class="fas fa-building"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-success">{{ $statistics['total_departments'] ?? 0 }}</h3>
                                <div class="text-muted small">Departments</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Incidents --}}
            <div class="card info-card">
                <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-history text-secondary me-2"></i> Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if($recentIncidents->isNotEmpty())
                        <div class="mt-2">
                            @foreach($recentIncidents as $incident)
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <a href="{{ route('incidents.show', $incident->id) }}" class="text-decoration-none fw-bold text-dark text-truncate" style="max-width: 70%;">
                                            {{ $incident->title }}
                                        </a>
                                        <small class="text-muted text-nowrap ms-2">{{ $incident->created_at->diffForHumans(null, true) }} ago</small>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge bg-{{ $incident->severity_color }}">{{ ucfirst($incident->severity) }}</span>
                                        <span class="text-muted small"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> {{ ucfirst(str_replace('_', ' ', $incident->status)) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x mb-3 text-light"></i>
                            <p class="mb-0">No recent incidents found.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
    
    {{-- Mobile Edit Button (Visible only on small screens) --}}
    <div class="d-md-none mt-4">
        <a href="{{ route('admin.airports.edit', $airport) }}" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
            <i class="fas fa-edit me-2"></i> Edit Airport Settings
        </a>
    </div>

</div>
@endsection