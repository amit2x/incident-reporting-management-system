{{-- resources/views/incidents/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Incidents - IRMS')

@php
$user = Auth::user();
$isAdmin = $user->isAdmin();
$activeTab = request('tab', 'all');
@endphp

@push('styles')
<style>
    /* Modern Variables */
    :root {
        --primary-color: #4361ee;
        --success-color: #06ffa5;
        --warning-color: #ff9f1c;
        --danger-color: #ef233c;
        --dark-color: #2b2d42;
        --light-bg: #f8f9fa;
    }

    /* Quick Stats Cards */
    .quick-stat-card {
        border: 1px solid #e9ecef;
        border-radius: 14px;
        padding: 12px 8px;
        text-align: center;
        transition: all 0.2s;
        background: white;
        cursor: pointer;
        text-decoration: none;
        display: block;
    }

    .quick-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: var(--primary-color);
    }

    .quick-stat-card.active {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
    }

    .quick-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }

    .quick-stat-label {
        font-size: 0.7rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Modern Card Design */
    .incident-card {
        background: white;
        border-radius: 16px;
        margin-bottom: 14px;
        transition: all 0.25s ease;
        border: 1px solid #e9ecef;
        overflow: hidden;
        position: relative;
    }

    .incident-card:active {
        transform: scale(0.99);
    }

    /* Compact Action Buttons */
    .action-buttons {
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 10px 16px;
        background: #fff;
        border-top: 1px solid #f0f0f0;
    }

    .action-icon-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 40px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #6c757d;
        background: transparent;
        border: none;
        transition: all 0.2s;
        cursor: pointer;
    }

    .action-icon-btn i {
        font-size: 1.1rem;
    }

    .action-icon-btn:hover {
        background: #f8f9fa;
        color: var(--primary-color);
    }

    .action-icon-btn.view-btn:hover {
        color: var(--primary-color);
    }

    .action-icon-btn.comment-btn:hover {
        color: #06ffa5;
    }

    .action-icon-btn.share-btn:hover {
        color: #ff9f1c;
    }

    /* Badge Styles */
    .priority-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .priority-critical { background: linear-gradient(135deg, #ef233c, #dc2626); color: white; }
    .priority-high { background: linear-gradient(135deg, #ff9f1c, #f97316); color: white; }
    .priority-medium { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #78350f; }
    .priority-low { background: linear-gradient(135deg, #06ffa5, #10b981); color: white; }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .status-open { background: #dbeafe; color: #1e40af; }
    .status-acknowledged { background: #fed7aa; color: #92400e; }
    .status-in-progress { background: #e9d5ff; color: #5b21b6; }
    .status-escalated { background: #fee2e2; color: #dc2626; }
    .status-resolved { background: #d1fae5; color: #065f46; }
    .status-closed { background: #e5e7eb; color: #374151; }

    /* Info Badges */
    .info-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8f9fa;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 0.7rem;
        color: #4b5563;
        font-weight: 500;
    }

    .info-chip i {
        font-size: 0.7rem;
        color: var(--primary-color);
    }

    /* Media Grid */
    .media-grid {
        display: grid;
        gap: 6px;
        padding: 0 16px 12px 16px;
    }

    .media-item {
        position: relative;
        aspect-ratio: 16/9;
        background: #f8f9fa;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
    }

    .media-item img,
    .media-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .media-item:hover img,
    .media-item:hover video {
        transform: scale(1.05);
    }

    .media-count {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }

    /* Modal Fix - Important */
    .modal.show {
        background-color: rgba(0, 0, 0, 0.8);
    }

    .modal.show .modal-content {
        background: transparent;
        border: none;
        box-shadow: none;
    }

    /* Table View */
    .table-view-container {
        overflow-x: auto;
    }

    .incident-table {
        width: 100%;
        margin-bottom: 0;
    }

    .incident-table th {
        background: #f8f9fa;
        font-size: 0.75rem;
        font-weight: 600;
        color: #4b5563;
        padding: 12px;
        border-bottom: 2px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .incident-table td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    .incident-row {
        cursor: pointer;
        transition: background 0.2s;
    }

    .incident-row:hover {
        background: #f8f9fa;
    }
    
    .nav-tabs-custom .nav-link {
        border: none;
        color: #6b7280;
        font-weight: 500;
        font-size: 0.8125rem;
        padding: 10px 16px;
        border-radius: 8px 8px 0 0;
        position: relative;
        transition: all 0.2s;
    }

    .nav-tabs-custom .nav-link:hover {
        color: #1f2937;
        background: #f9fafb;
    }

    .nav-tabs-custom .nav-link.active {
        color: #1a56db;
        background: white;
        font-weight: 600;
    }

    .nav-tabs-custom .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 16px;
        right: 16px;
        height: 2px;
        background: #1a56db;
        border-radius: 2px;
    }

    .nav-tabs-custom .nav-link .badge-pill {
        font-size: 0.6rem;
        padding: 2px 8px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .quick-stat-value {
            font-size: 1.1rem;
        }
        
        .action-icon-btn span {
            display: inline;
        }
        
        .action-icon-btn {
            padding: 6px 8px;
        }
    }

    @media (min-width: 769px) {
        #cardsView { display: none; }
        #tableView { display: block; }
    }

    @media (max-width: 768px) {
        #cardsView { display: block; }
        #tableView { display: none; }
    }
    
    {{-- Add Webkit scrollbar cleaner block to your CSS file --}}
    .nav-tabs::-webkit-scrollbar {
        display: none !important;
    }
    .me-1_5 {
        margin-right: 0.375rem !important;
    }
    .ms-1_5 {
        margin-left: 0.375rem !important;
    }
</style>
@endpush

@section('content')
<div class="py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--dark-color);">Incidents</h4>
            <p class="text-muted small mb-0">Track and manage all incidents</p>
        </div>
        <a href="{{ route('incidents.create') }}" class="btn btn-primary rounded-pill px-4">
            <i class="fas fa-plus me-2"></i>New Incident
        </a>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-2 mb-3 px-2">
        @php
        $statusList = [
            'open' => ['label' => 'Open', 'color' => '#3B82F6', 'icon' => 'fa-circle-info'],
            'acknowledged' => ['label' => 'Acknowledged', 'color' => '#F59E0B', 'icon' => 'fa-eye'],
            'in_progress' => ['label' => 'In Progress', 'color' => '#8B5CF6', 'icon' => 'fa-spinner'],
            'escalated' => ['label' => 'Escalated', 'color' => '#EF4444', 'icon' => 'fa-arrow-up'],
            'resolved' => ['label' => 'Resolved', 'color' => '#10B981', 'icon' => 'fa-check-circle'],
            'closed' => ['label' => 'Closed', 'color' => '#6B7280', 'icon' => 'fa-times-circle']
        ];
        @endphp
        @foreach($statusList as $key => $status)
        <div class="col-4 col-md-2">
            <a href="{{ route('incidents.index', ['status' => $key]) }}"
                class="quick-stat-card {{ request('status') == $key ? 'active' : '' }}">
                <div class="quick-stat-value" style="color: {{ $status['color'] }}">{{ $stats[$key] ?? 0 }}</div>
                <div class="quick-stat-label">
                    <i class="fas {{ $status['icon'] }} me-1" style="font-size: 0.6rem;"></i>
                    {{ $status['label'] }}
                </div>
            </a>
        </div>
        @endforeach
    </div>

   
    
    {{-- Tabs --}}
    <div class="px-2 overflow-hidden">
    <ul class="nav nav-tabs border-0 gap-2 flex-nowrap overflow-x-auto overflow-y-hidden" style="scrollbar-width: none; -ms-overflow-style: none;">
        <li class="nav-item d-flex d-md-inline-flex">
            <a class="nav-link rounded-pill px-3 py-2 d-inline-flex align-items-center justify-content-center text-nowrap {{ $activeTab === 'all' ? 'active bg-primary text-white' : 'bg-light text-dark' }}" 
               href="{{ route('incidents.index', ['tab' => 'all']) }}">
                <i class="fas fa-list me-1_5"></i> <span>All</span>
                <span class="badge rounded-pill {{ $activeTab === 'all' ? 'bg-white text-primary' : 'bg-secondary text-white' }} ms-1_5 lh-1 px-2 py-1 small fw-bold">{{ $stats['total'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item d-flex d-md-inline-flex">
            <a class="nav-link rounded-pill px-3 py-2 d-inline-flex align-items-center justify-content-center text-nowrap {{ $activeTab === 'escalated' ? 'active bg-danger text-white' : 'bg-light text-dark' }}" 
               href="{{ route('incidents.index', ['tab' => 'escalated']) }}">
                <i class="fas fa-arrow-up me-1_5 text-warning text-center"></i> <span>Escalated</span>
                @if(($escalatedCount = $stats['escalated'] ?? 0) > 0)
                <span class="badge rounded-pill bg-white text-danger ms-1_5 lh-1 px-2 py-1 small fw-bold">{{ $escalatedCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item d-flex d-md-inline-flex">
            <a class="nav-link rounded-pill px-3 py-2 d-inline-flex align-items-center justify-content-center text-nowrap {{ $activeTab === 'assigned' ? 'active bg-success text-white' : 'bg-light text-dark' }}" 
               href="{{ route('incidents.index', ['tab' => 'assigned']) }}">
                <i class="fas fa-user-check me-1_5 text-primary"></i> <span>Assigned</span>
                @if(($assignedCount = $stats['assigned'] ?? 0) > 0)
                <span class="badge rounded-pill bg-white text-success ms-1_5 lh-1 px-2 py-1 small fw-bold">{{ $assignedCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item d-flex d-md-inline-flex">
            <a class="nav-link rounded-pill px-3 py-2 d-inline-flex align-items-center justify-content-center text-nowrap {{ $activeTab === 'history' ? 'active bg-info text-white' : 'bg-light text-dark' }}" 
               href="{{ route('incidents.index', ['tab' => 'history']) }}">
                <i class="fas fa-history me-1_5"></i> <span>History</span>
            </a>
        </li>
        <li class="nav-item d-flex d-md-inline-flex">
            <a class="nav-link rounded-pill px-3 py-2 d-inline-flex align-items-center justify-content-center text-nowrap {{ $activeTab === 'reported' ? 'active bg-warning text-dark' : 'bg-light text-dark' }}" 
               href="{{ route('incidents.index', ['tab' => 'reported']) }}">
                <i class="fas fa-pen me-1_5"></i> <span>My Reports</span>
            </a>
        </li>
    </ul>
</div>




    {{-- Filters --}}
    
    {{-- Filters - Collapsible on Mobile --}}
    @if($activeTab === 'all')
    <div class="card shadow-sm mt-3 border-0 rounded-3">
        <div class="card-body py-3">
            {{-- Filter Toggle Button for Mobile --}}
            <div class="d-md-none">
                <button class="btn btn-outline-primary w-100 rounded-pill d-flex justify-content-between align-items-center" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#filterCollapse" 
                        aria-expanded="false" 
                        aria-controls="filterCollapse">
                    <span><i class="fas fa-filter me-2"></i>Filter Incidents</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
            </div>
            
            {{-- Filter Form - Collapsible on mobile, always visible on desktop --}}
            <div class="collapse d-md-block" id="filterCollapse">
                <form id="filterForm" method="GET" class="mt-3 mt-md-0">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm rounded-pill"
                                placeholder="🔍 Search incidents..." value="{{ request('search') }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="status" class="form-select form-select-sm rounded-pill">
                                <option value="">All Status</option>
                                <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open</option>
                                <option value="acknowledged" {{ request('status')=='acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                <option value="in_progress" {{ request('status')=='in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="escalated" {{ request('status')=='escalated' ? 'selected' : '' }}>Escalated</option>
                                <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="severity" class="form-select form-select-sm rounded-pill">
                                <option value="">All Severity</option>
                                <option value="critical" {{ request('severity')=='critical' ? 'selected' : '' }}>Critical</option>
                                <option value="high" {{ request('severity')=='high' ? 'selected' : '' }}>High</option>
                                <option value="medium" {{ request('severity')=='medium' ? 'selected' : '' }}>Medium</option>
                                <option value="low" {{ request('severity')=='low' ? 'selected' : '' }}>Low</option>
                            </select>
                        </div>
                        @if($isAdmin)
                        <div class="col-12 col-md-3">
                            <select name="department_id" class="form-select form-select-sm rounded-pill">
                                <option value="">All Departments</option>
                                @foreach(\App\Models\Department::active()->ordered()->get() as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id')==$dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-auto ms-auto">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('incidents.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Results --}}
    <div class="card shadow-sm mt-3 border-0 rounded-3 overflow-hidden">
        <div class="card-body p-0">
            @if(isset($incidents) && count($incidents) > 0)
            
            {{-- Desktop Table View --}}
            <div id="tableView">
                <table class="incident-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            @if(in_array($activeTab, ['history', 'reported']))
                            <th>Your Role</th>
                            @endif

                            <th>Category</th>
                            <th>Department</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incidents as $incident)
                        <tr class="incident-row" data-href="{{ route('incidents.show', $incident->id) }}">
                            <td><code class="small">{{ $incident->incident_id }}</code></td>
                            <td class="fw-medium">{{ Str::limit($incident->title, 50) }}</td>
                            @if(in_array($activeTab, ['history', 'reported']))
                            <td>
                                    @php
                                        $roles = [];
                                        if ($incident->reported_by === $user->id) $roles[] = '📝 Reporter';
                                        if ($incident->assigned_to === $user->id) $roles[] = '👤 Assigned';
                                        if ($incident->escalated_to === $user->id) $roles[] = '⬆️ Escalated';
                                        if ($incident->escalations()->where('escalated_by', $user->id)->exists())
                                        $roles[] = '📤 Escalated Out';
                                        if ($incident->assignments()->where('assigned_by', $user->id)->exists())
                                        $roles[] = '📋 Assigned Out';
                                        if ($incident->comments()->where('user_id', $user->id)->exists()) $roles[] = '💬
                                        Commented';
                                    @endphp
                                    @foreach($roles as $role)
                                        <span class="badge bg-light text-dark d-block mb-1 small">{{ $role }}</span>
                                    @endforeach
                            </td>
                            @endif

                            <td><span class="badge info-chip py-1" 
                                style="background: {{ $incident->category?->color ?? '#6B7280' }}20; color: {{ $incident->category?->color ?? '#6B7280'}}">
                                {{ $incident->category?->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge" style="background: {{ $incident->department?->color ?? '#6B7280' }}20; color: {{ $incident->department?->color ?? '#6B7280' }}">
                                            {{ $incident->department?->code ?? 'N/A' }}
                                        </span>
                            </td>
                            <td><span class="priority-badge priority-{{ $incident->priority }}">{{ ucfirst($incident->severity) }}</span></td>
                            <td><span class="status-badge status-{{ $incident->status }}">{{ ucfirst($incident->status) }}</span></td>
                            <!--<td><small>{{ $incident->assignedTo?->name ?? 'Unassigned' }}</small></td>-->
                            <td>
                                 @if($incident->assignedTo)
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $incident->assignedTo->avatar_url }}" alt="Assignee" class="rounded-circle" width="24" height="24">
                                        <small>{{ $incident->assignedTo->name }}</small>
                                    </div>
                                @else
                                    <small class="text-muted">Unassigned</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $incident->created_at->diffForHumans() }}</small></td>
                            <td>
                                <a href="{{ route('incidents.show', $incident->id) }}" class="btn btn-sm btn-light rounded-circle">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards View --}}
            <div id="cardsView">
                @php
                    // Use the variable passed from controller
                    // Or use hasPermissionTo() directly
                    $canEdit = auth()->user()->hasPermissionTo('edit-incident');
                @endphp

                @foreach($incidents as $incident)
                <div class="incident-card" onclick="goToIncident({{ $incident->id }})">
                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start p-3 pb-2">
                        <div class="d-flex gap-2">
                            <div class="user-avatar">
                                <img src="{{ $incident->reporter?->avatar_url ?? asset('images/default-avatar.png') }}" 
                                     class="rounded-circle" width="42" height="42" style="object-fit: cover;">
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 0.9rem;">{{ $incident->reporter?->name ?? 'Anonymous' }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">
                                    {{ $incident->department?->name ?? 'No Dept' }} • {{ $incident->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm rounded-circle" data-bs-toggle="dropdown" onclick="event.stopPropagation()">
                                <i class="fas fa-ellipsis-v text-muted"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="{{ route('incidents.show', $incident->id) }}" class="dropdown-item">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                                @if($canEdit)
                                    <a href="{{ route('incidents.edit', $incident->id) }}" class="dropdown-item">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a>
                                @endif
                                <button class="dropdown-item" onclick="shareIncident({{ $incident->id }}, '{{ addslashes($incident->title) }}', event)">
                                    <i class="fas fa-share-alt me-2"></i>Share
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Location --}}
                    @if($incident->location)
                    <div class="px-3 pb-1">
                        <i class="fas fa-map-marker-alt text-muted" style="font-size: 0.65rem;"></i>
                        <span class="text-muted" style="font-size: 0.7rem;">{{ $incident->location }}</span>
                    </div>
                    @endif

                    {{-- Badges --}}
                    <div class="px-3 pb-2 d-flex gap-2 flex-wrap align-items-center">
                        <code class="small bg-light px-2 py-1 rounded" style="font-size: 0.65rem;">{{ $incident->incident_id }}</code>
                        <span class="priority-badge priority-{{ $incident->priority }}">{{ ucfirst($incident->priority) }}</span>
                        <span class="status-badge status-{{ $incident->status }}">{{ ucfirst($incident->status) }}</span>
                        @if($incident->sla_due_at && $incident->sla_due_at < now())
                        <span class="badge bg-danger px-2 py-1" style="font-size: 0.65rem;">
                            <i class="fas fa-clock"></i> Overdue
                        </span>
                        @endif
                    </div>

                    {{-- Title & Description --}}
                    <div class="px-3 pb-2">
                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">{{ $incident->title }}</h6>
                        <p class="text-muted mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                            {{ Str::limit($incident->description, 80) }}
                        </p>
                    </div>

                    {{-- Info Chips --}}
                    <div class="px-3 pb-2 d-flex gap-2 flex-wrap">
                        <span class="info-chip">
                            <i class="fas fa-folder-open"></i> {{ $incident->category?->name ?? 'Uncategorized' }}
                        </span>
                        <span class="info-chip">
                            <i class="fas fa-building"></i> {{ $incident->department?->code ?? 'N/A' }}
                        </span>
                    </div>

                    {{-- Media Grid --}}
                    @if($incident->media && $incident->media->count() > 0)
                    <div class="media-grid" style="grid-template-columns: repeat({{ min($incident->media->count(), 3) }}, 1fr);">
                        @foreach($incident->media->take(3) as $index => $media)
                        <div class="media-item" onclick="event.stopPropagation(); openMediaModal({{ $incident->id }}, {{ $index }})">
                            @if($media->media_type === 'image')
                                <img src="{{ Storage::url($media->file_path) }}" loading="lazy">
                            @elseif($media->media_type === 'video')
                                <video src="{{ Storage::url($media->file_path) }}" preload="metadata"></video>
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <i class="fas fa-play-circle text-white" style="font-size: 2rem; text-shadow: 0 0 5px black;"></i>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                    <i class="fas fa-file-alt fa-2x text-muted"></i>
                                </div>
                            @endif
                            @if($index === 2 && $incident->media->count() > 3)
                            <div class="media-count">+{{ $incident->media->count() - 3 }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Compact Action Buttons --}}
                    <div class="action-buttons">
                        <button class="action-icon-btn view-btn" onclick="event.stopPropagation(); goToIncident({{ $incident->id }})">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </button>
                        <button class="action-icon-btn comment-btn" onclick="event.stopPropagation(); openCommentsModal({{ $incident->id }})">
                            <i class="fas fa-comment"></i>
                            <span>Comment</span>
                            @if($incident->comments_count > 0)
                            <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">{{ $incident->comments_count }}</span>
                            @endif
                        </button>
                        <button class="action-icon-btn share-btn" onclick="event.stopPropagation(); shareIncident({{ $incident->id }}, '{{ addslashes($incident->title) }}', event)">
                            <i class="fas fa-share-alt"></i>
                            <span>Share</span>
                        </button>
                    </div>

                    {{-- Footer --}}
                    <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center" style="border-top: 1px solid #f0f0f0;">
                        <div class="d-flex gap-3">
                            <span class="text-muted" style="font-size: 0.65rem;">
                                <i class="far fa-calendar-alt me-1"></i>{{ $incident->created_at->format('d M Y') }}
                            </span>
                            <span class="text-muted" style="font-size: 0.65rem;">
                                <i class="fas fa-user-check me-1"></i>{{ $incident->assignedTo?->name ?? 'Unassigned' }}
                            </span>
                        </div>
                        <i class="fas fa-chevron-right text-primary" style="font-size: 0.8rem;"></i>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($incidents->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $incidents->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h6 class="fw-semibold">No incidents found</h6>
                <p class="text-muted small">
                    @if(request()->hasAny(['search','status','severity']))
                    No incidents match your filters.
                    @else
                    Start by reporting your first incident.
                    @endif
                </p>
                @if($activeTab === 'all')
                <a href="{{ route('incidents.create') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-plus me-2"></i>Report Incident
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
    </div>


{{-- Media Modal - Fixed Focus Issue --}}
<div class="modal fade" id="mediaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="true" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0">
                <div class="position-relative bg-black rounded-3 overflow-hidden" style="min-height: 500px;">
                    <img id="modalMedia" src="" alt="" class="w-100" style="display: none; max-height: 80vh; object-fit: contain;">
                    <video id="modalVideo" controls style="display: none; width: 100%; max-height: 80vh;">
                        <source id="videoSource" src="">
                    </video>
                    <div id="modalDocument" class="text-center text-white p-5" style="display: none;">
                        <i class="fas fa-file fa-4x mb-3"></i>
                        <p>Document preview not available</p>
                        <a id="downloadLink" href="#" class="btn btn-primary rounded-pill px-4" download>Download File</a>
                    </div>
                    
                    {{-- Navigation Buttons --}}
                    <button id="prevMediaBtn" class="btn btn-dark rounded-circle position-absolute start-3 top-50 translate-middle-y" style="width: 40px; height: 40px; z-index: 10;">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button id="nextMediaBtn" class="btn btn-dark rounded-circle position-absolute end-3 top-50 translate-middle-y" style="width: 40px; height: 40px; z-index: 10;">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div id="mediaCounter" class="position-absolute bottom-3 start-50 translate-middle-x bg-dark bg-opacity-75 text-white px-3 py-1 rounded-pill" style="font-size: 0.8rem; bottom: 10px;"></div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-3">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" id="closeMediaModalBtn">Close</button>
            </div>
        </div>
    </div>
</div>
<!--{{-- Media Modal - Fixed with proper focus management --}}-->
<!--<div class="modal fade" id="mediaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="true">-->
<!--    <div class="modal-dialog modal-dialog-centered modal-lg">-->
<!--        <div class="modal-content bg-transparent border-0">-->
<!--            <div class="modal-body p-0">-->
<!--                <div class="position-relative bg-white rounded-3 overflow-hidden" style="min-height: 500px;">-->
<!--                    <img id="modalMedia" src="" alt="" class="w-100" style="display: none; max-height: 80vh; object-fit: contain;">-->
<!--                    <video id="modalVideo" controls style="display: none; width: 100%; max-height: 80vh;">-->
<!--                        <source id="videoSource" src="">-->
<!--                    </video>-->
<!--                    <div id="modalDocument" class="text-center text-white p-5" style="display: none;">-->
<!--                        <i class="fas fa-file fa-4x mb-3"></i>-->
<!--                        <p>Document preview not available</p>-->
<!--                        <a id="downloadLink" href="#" class="btn btn-primary rounded-pill px-4" download>Download File</a>-->
<!--                    </div>-->
                    
<!--                    {{-- Navigation Buttons --}}-->
<!--                    <button id="prevMediaBtn" class="btn btn-dark rounded-circle position-absolute start-3 top-50 translate-middle-y" style="width: 40px; height: 40px; z-index: 10;">-->
<!--                        <i class="fas fa-chevron-left"></i>-->
<!--                    </button>-->
<!--                    <button id="nextMediaBtn" class="btn btn-dark rounded-circle position-absolute end-3 top-50 translate-middle-y" style="width: 40px; height: 40px; z-index: 10;">-->
<!--                        <i class="fas fa-chevron-right"></i>-->
<!--                    </button>-->
                    
<!--                    <div id="mediaCounter" class="position-absolute bottom-3 start-50 translate-middle-x bg-dark bg-opacity-75 text-white px-3 py-1 rounded-pill" style="font-size: 0.8rem; bottom: 10px;"></div>-->
<!--                </div>-->
<!--            </div>-->
<!--            <div class="modal-footer border-0 justify-content-center pt-3">-->
<!--                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</div>-->

{{-- Comments Modal --}}
<div class="modal fade" id="commentsModal" tabindex="-1" role="dialog" aria-labelledby="commentsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content bg-white border-0 rounded-3 shadow">
            <!-- Forced background utility onto header directly -->
            <div class="modal-header bg-white border-0 pb-0">
                <h5 class="modal-title fw-semibold text-dark" id="commentsModalTitle">
                    <i class="fas fa-comments text-primary me-2" aria-hidden="true"></i>Comments
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close comments"></button>
            </div>
            <!-- Ensured background matching on body wrapper -->
            <div class="modal-body bg-white" id="commentsModalBody" role="region" aria-live="polite" aria-busy="true">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <p class="mt-2 text-muted">Loading comments...</p>
                </div>
            </div>
        </div>
    </div>
</div>





@endsection

@push('scripts')
<script>
let currentMediaList = [];
let currentMediaIndex = 0;
let mediaModal = null;
let modalElement = null;

async function openMediaModal(incidentId, index = 0) {
    currentMediaIndex = index;
    
    try {
        const response = await fetch(`/incidents/${incidentId}/media`);
        const data = await response.json();
        
        if (data.success && data.media.length > 0) {
            currentMediaList = data.media;
            displayCurrentMedia();
            
            modalElement = document.getElementById('mediaModal');
            
            // Remove aria-hidden before showing
            modalElement.removeAttribute('aria-hidden');
            modalElement.style.display = 'block';
            
            document.body.classList.add('modal-open');
            
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
            
            setTimeout(() => {
                modalElement.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                const closeBtn = document.getElementById('closeMediaModalBtn');
                if (closeBtn) closeBtn.focus();
            }, 100);
            
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeMediaModal();
                }
            };
            document.addEventListener('keydown', handleEscape);
            modalElement._escapeHandler = handleEscape;
            
        } else {
            showToast('No media found');
        }
    } catch (error) {
        console.error('Error loading media:', error);
        showToast('Error loading media');
    }
}

function closeMediaModal() {
    if (!modalElement) return;
    
    // CORRECTION 1: Explicitly drop active button focus immediately to prevent WAI-ARIA error
    if (document.activeElement && modalElement.contains(document.activeElement)) {
        document.activeElement.blur();
    }
    
    modalElement.classList.remove('show');
    
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.classList.remove('show');
        setTimeout(() => {
            if (backdrop && backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
        }, 150);
    }
    
    document.body.classList.remove('modal-open');
    
    setTimeout(() => {
        if (modalElement) {
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.style.display = 'none';
            
            if (modalElement._escapeHandler) {
                document.removeEventListener('keydown', modalElement._escapeHandler);
                delete modalElement._escapeHandler;
            }
            
            const mediaItem = document.querySelector('.media-item'); 
            if (mediaItem) {
                mediaItem.focus();
            }
        }
    }, 150);
}

function displayCurrentMedia() {
    const media = currentMediaList[currentMediaIndex];
    const imgElement = document.getElementById('modalMedia');
    const videoElement = document.getElementById('modalVideo');
    const docElement = document.getElementById('modalDocument');
    const counter = document.getElementById('mediaCounter');
    
    imgElement.style.display = 'none';
    videoElement.style.display = 'none';
    docElement.style.display = 'none';
    
    if (counter) {
        counter.textContent = `${currentMediaIndex + 1} of ${currentMediaList.length}`;
    }
    
    if (media.type === 'image') {
        imgElement.src = media.url;
        imgElement.style.display = 'block';
    } else if (media.type === 'video') {
        const videoSource = document.getElementById('videoSource');
        if (videoSource) {
            videoSource.src = media.url;
            videoElement.load();
        }
        videoElement.style.display = 'block';
    } else {
        const downloadLink = document.getElementById('downloadLink');
        if (downloadLink) {
            downloadLink.href = media.url;
        }
        docElement.style.display = 'block';
    }
    
    const prevBtn = document.getElementById('prevMediaBtn');
    const nextBtn = document.getElementById('nextMediaBtn');
    
    if (prevBtn) {
        prevBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            navigateMedia(-1);
        };
    }
    
    if (nextBtn) {
        nextBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            navigateMedia(1);
        };
    }
}

function navigateMedia(direction) {
    const newIndex = currentMediaIndex + direction;
    if (newIndex >= 0 && newIndex < currentMediaList.length) {
        currentMediaIndex = newIndex;
        displayCurrentMedia();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.getElementById('closeMediaModalBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeMediaModal();
        });
    }
    
    document.addEventListener('click', function(e) {
        // CORRECTION 2: Check target parent container context exactly so comments click won't trip media close routines
        if (modalElement && modalElement.classList.contains('show') && modalElement.contains(e.target)) {
            const modalDialog = modalElement.querySelector('.modal-dialog');
            if (modalDialog && !modalDialog.contains(e.target)) {
                closeMediaModal();
            }
        }
    });
    
    // the comments modal starts its closing hide animation sequence.
    const commentsModalEl = document.getElementById('commentsModal');
    if (commentsModalEl) {
        commentsModalEl.addEventListener('hide.bs.modal', function() {
            if (document.activeElement && commentsModalEl.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    }
});

async function openCommentsModal(incidentId, event) {
    if (event) event.stopPropagation();
    
    const modalTarget = document.getElementById('commentsModal');
    const modal = new bootstrap.Modal(modalTarget);
    const modalBody = document.getElementById('commentsModalBody');
    
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading comments...</p></div>';
    modal.show();
    
    try {
        const response = await fetch(`/incidents/${incidentId}/comments`);
        const data = await response.json();
        
        if (data.success && data.comments.length > 0) {
            let html = '<div class="list-group list-group-flush">';
            data.comments.forEach(comment => {
                html += `
                    <div class="list-group-item border-0 py-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <strong class="small text-dark">${escapeHtml(comment.user.name)}</strong>
                            <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                        </div>
                        <p class="mb-0 small text-muted">${escapeHtml(comment.content)}</p>
                    </div>
                `;
            });
            html += '</div>';
            modalBody.innerHTML = html;
        } else {
            modalBody.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-comment-slash fa-2x mb-2"></i><p>No comments yet</p></div>';
        }
    } catch (error) {
        modalBody.innerHTML = '<div class="text-center py-4 text-danger">Error loading comments</div>';
    }
}

function closeCommentsModal() {
    const commentsModalEl = document.getElementById('commentsModal');
    if (!commentsModalEl) return;
    
    // CORRECTION: Aggressively drop focus immediately from the close button 
    // before the hiding animation runs.
    if (document.activeElement && commentsModalEl.contains(document.activeElement)) {
        document.activeElement.blur();
    }
    
    const instance = bootstrap.Modal.getInstance(commentsModalEl);
    if (instance) {
        instance.hide();
    }
}


// Simple placeholder helper in case you don't have it defined globally
function escapeHtml(string) {
    return String(string).replace(/[&<>"']/g, function (s) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
    });
}


function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-3 p-2 bg-dark text-white rounded-pill px-3';
    toast.style.zIndex = '9999';
    toast.style.fontSize = '0.8rem';
    toast.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

function goToIncident(id) {
    window.location.href = `/incidents/${id}`;
}

function shareIncident(id, title, event) {
    if (event) event.stopPropagation();
    const url = `${window.location.origin}/incidents/${id}`;
    
    if (navigator.share) {
        navigator.share({ title: `Incident: ${title}`, url: url });
    } else {
        navigator.clipboard.writeText(url);
        showToast('Link copied to clipboard!');
    }
}

// Initialize table row clicks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.incident-row').forEach(row => {
        row.addEventListener('click', function() {
            if (this.dataset.href) window.location.href = this.dataset.href;
        });
    });
});
    

document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.querySelector('[data-bs-target="#filterCollapse"]');
    if (filterToggle) {
        filterToggle.addEventListener('click', function() {
            const icon = this.querySelector('.toggle-icon');
            if (icon) {
                icon.style.transform = this.getAttribute('aria-expanded') === 'true' ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
    }
});


</script>
@endpush