{{-- resources/views/incidents/index.blade.php --}}
@extends('layouts.app')

@section('title', 'All Incidents - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Incidents</li>
@endsection

@section('content')
<div class="page-enter">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">All Incidents</h4>
            <p class="text-muted mb-0" style="font-size: 0.8125rem;">
                View and manage all reported incidents
            </p>
        </div>
        <a href="{{ route('incidents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Report Incident
        </a>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form id="filterForm" method="GET" action="{{ route('incidents.index') }}">
                <div class="row g-2 align-items-end">
                    {{-- Search --}}
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label" style="font-size: 0.6875rem;">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Search incidents..." value="{{ request('search') }}">
                    </div>

                    {{-- Status Filter --}}
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" style="font-size: 0.6875rem;">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="escalated" {{ request('status') == 'escalated' ? 'selected' : '' }}>Escalated</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    {{-- Severity Filter --}}
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" style="font-size: 0.6875rem;">Severity</label>
                        <select name="severity" class="form-select form-select-sm">
                            <option value="">All Severity</option>
                            <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                            <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                    </div>

                    {{-- Department Filter (Admin Only) --}}
                    @if(Auth::user()->isAdmin())
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" style="font-size: 0.6875rem;">Department</label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            @foreach(\App\Models\Department::active()->ordered()->get() as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Date Range --}}
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" style="font-size: 0.6875rem;">Date From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" style="font-size: 0.6875rem;">Date To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>

                    {{-- Buttons --}}
                    <div class="col-md-1 col-sm-6 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="{{ route('incidents.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3 col-lg-2">
            <a href="{{ route('incidents.index', ['status' => 'open']) }}"
               class="text-decoration-none">
                <div class="stat-card p-3 text-center {{ request('status') == 'open' ? 'border-primary' : '' }}">
                    <div class="fw-bold" style="color: #3B82F6;">
                        {{ \App\Models\Incident::where('status', 'open')->when(!Auth::user()->isAdmin(), fn($q) => $q->where('department_id', Auth::user()->department_id))->count() }}
                    </div>
                    <small class="text-muted">Open</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <a href="{{ route('incidents.index', ['status' => 'in_progress']) }}"
               class="text-decoration-none">
                <div class="stat-card p-3 text-center {{ request('status') == 'in_progress' ? 'border-primary' : '' }}">
                    <div class="fw-bold" style="color: #8B5CF6;">
                        {{ \App\Models\Incident::where('status', 'in_progress')->when(!Auth::user()->isAdmin(), fn($q) => $q->where('department_id', Auth::user()->department_id))->count() }}
                    </div>
                    <small class="text-muted">In Progress</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <a href="{{ route('incidents.index', ['status' => 'escalated']) }}"
               class="text-decoration-none">
                <div class="stat-card p-3 text-center {{ request('status') == 'escalated' ? 'border-primary' : '' }}">
                    <div class="fw-bold" style="color: #EF4444;">
                        {{ \App\Models\Incident::where('status', 'escalated')->when(!Auth::user()->isAdmin(), fn($q) => $q->where('department_id', Auth::user()->department_id))->count() }}
                    </div>
                    <small class="text-muted">Escalated</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <a href="{{ route('incidents.index', ['status' => 'resolved']) }}"
               class="text-decoration-none">
                <div class="stat-card p-3 text-center {{ request('status') == 'resolved' ? 'border-primary' : '' }}">
                    <div class="fw-bold" style="color: #10B981;">
                        {{ \App\Models\Incident::where('status', 'resolved')->when(!Auth::user()->isAdmin(), fn($q) => $q->where('department_id', Auth::user()->department_id))->count() }}
                    </div>
                    <small class="text-muted">Resolved</small>
                </div>
            </a>
        </div>
    </div>

    {{-- Incidents List --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Incidents
                <span class="text-muted fw-normal">({{ $incidents->total() ?? 0 }})</span>
            </h6>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-light active" onclick="toggleView('list')" id="listViewBtn">
                    <i class="fas fa-list"></i>
                </button>
                <button class="btn btn-light" onclick="toggleView('grid')" id="gridViewBtn">
                    <i class="fas fa-grid-2"></i>
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @if(isset($incidents) && count($incidents) > 0)
                {{-- Table View (Desktop) --}}
                <div class="table-responsive d-none d-md-block" id="listView">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 100px;">ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Reported</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incidents as $incident)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $incident->incident_id }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('incidents.show', $incident) }}" class="text-decoration-none fw-medium">
                                        {{ Str::limit($incident->title, 50) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge" style="background: {{ $incident->category?->color ?? '#6B7280' }}20; color: {{ $incident->category?->color ?? '#6B7280' }}">
                                        {{ $incident->category?->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background: {{ $incident->department?->color ?? '#6B7280' }}20; color: {{ $incident->department?->color ?? '#6B7280' }}">
                                        {{ $incident->department?->code ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge priority-{{ $incident->severity }}">
                                        {{ ucfirst($incident->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-{{ str_replace('_', '-', $incident->status) }}">
                                        {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($incident->assignedTo)
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $incident->assignedTo->avatar_url }}"
                                                 alt="Assignee"
                                                 class="rounded-circle"
                                                 width="24" height="24">
                                            <small>{{ $incident->assignedTo->name }}</small>
                                        </div>
                                    @else
                                        <small class="text-muted">Unassigned</small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $incident->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('incidents.show', $incident) }}" class="dropdown-item">
                                                <i class="fas fa-eye me-2"></i>View
                                            </a>
                                            @can('edit-incident')
                                            <a href="{{ route('incidents.edit', $incident) }}" class="dropdown-item">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Card View (Mobile) --}}
                <div class="d-md-none" id="gridView">
                    @foreach($incidents as $incident)
                        @include('incidents.partials.incident-card', ['incident' => $incident])
                    @endforeach
                </div>
            @else
                <div class="empty-state py-5">
                    <div class="empty-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="empty-title">No incidents found</div>
                    <div class="empty-description">
                        @if(request()->hasAny(['search', 'status', 'severity']))
                            No incidents match your filters. Try adjusting your search criteria.
                        @else
                            No incidents have been reported yet.
                        @endif
                    </div>
                    <a href="{{ route('incidents.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Report First Incident
                    </a>
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if(isset($incidents) && $incidents->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $incidents->firstItem() ?? 0 }} - {{ $incidents->lastItem() ?? 0 }}
                        of {{ $incidents->total() }} incidents
                    </small>
                    {{ $incidents->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleView(view) {
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        const listBtn = document.getElementById('listViewBtn');
        const gridBtn = document.getElementById('gridViewBtn');

        if (view === 'list') {
            listView.classList.remove('d-none');
            gridView.classList.add('d-none');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        } else {
            listView.classList.add('d-none');
            gridView.classList.remove('d-none');
            listBtn.classList.remove('active');
            gridBtn.classList.add('active');
        }

        localStorage.setItem('incidentView', view);
    }

    // Restore saved view preference
    const savedView = localStorage.getItem('incidentView') || 'list';
    toggleView(savedView);
</script>
@endpush
