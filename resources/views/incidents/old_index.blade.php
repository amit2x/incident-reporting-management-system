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
    .quick-stat-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 8px;
        text-align: center;
        transition: all 0.2s;
        background: white;
        cursor: pointer;
        text-decoration: none;
        display: block;
    }

    .quick-stat-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    }

    .quick-stat-card.active {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .quick-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }

    .quick-stat-label {
        font-size: 0.65rem;
        color: #6b7280;
        font-weight: 500;
    }

    .incident-row {
        cursor: pointer;
        transition: background 0.15s;
    }

    .incident-row:hover {
        background: #f8fafc;
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

    .view-toggle-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        color: #6b7280;
    }

    .view-toggle-btn.active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .view-toggle-btn:hover {
        border-color: #3b82f6;
    }


    /* Hide scrollbars on modern desktop environments while maintaining touch actions */
    .nav-tabs-custom::-webkit-scrollbar {
        display: none;
    }

    .nav-tabs-custom {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .me-1_5 {
        margin-right: 0.375rem !important;
    }

    .ms-1_5 {
        margin-left: 0.375rem !important;
    }

    /* Add smooth fade indicators for horizontal mobile panning */
    @media (max-width: 767.98px) {
        .tabs-container-responsive::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 32px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 1) 100%);
            pointer-events: none;
            z-index: 2;
        }
    }
</style>
@endpush

@section('content')
{{-- <div class="container-fluid px-3 py-3"> --}}
    <div class="py-3">

        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="fw-bold mb-1">Incidents</h4>
                <p class="text-muted small mb-0">View and manage all reported incidents</p>
            </div>
            <a href="{{ route('incidents.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Report Incident
            </a>
        </div>

        {{-- Quick Stats --}}
        <div class="row g-2 mb-3">
            @php
            $statusList = [
            'open' => ['label' => 'Open', 'color' => '#3B82F6'],
            'acknowledged' => ['label' => 'Acknowledged', 'color' => '#F59E0B'],
            'in_progress' => ['label' => 'In Progress', 'color' => '#8B5CF6'],
            'escalated' => ['label' => 'Escalated', 'color' => '#EF4444'],
            'resolved' => ['label' => 'Resolved', 'color' => '#10B981'],
            'closed' => ['label' => 'Closed', 'color' => '#6B7280']
            ];
            @endphp
            @foreach($statusList as $key => $status)
            <div class="col-4 col-md-2">
                <a href="{{ route('incidents.index', ['status' => $key]) }}"
                    class="quick-stat-card {{ request('status') == $key ? 'active' : '' }}">
                    <div class="quick-stat-value" style="color: {{ $status['color'] }}">{{ $stats[$key] ?? 0 }}</div>
                    <div class="quick-stat-label">{{ $status['label'] }}</div>
                </a>
            </div>
            @endforeach
        </div>

        {{-- ========================================== --}}
        {{-- TABS: All | Escalated to Me | Assigned to Me --}}
        {{-- ========================================== --}}
        {{-- <ul class="nav nav-tabs-custom mb-0 flex-nowrap overflow-x-auto text-nowrap" role="tablist"
            style="border-bottom: 1px solid #e5e7eb; -webkit-overflow-scrolling: touch;">
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'all' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'all'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-list me-1"></i> All
                    <span class="badge rounded-pill bg-light text-dark ms-1 align-self-start"
                        style="font-size: 0.75em; transform: translateY(-3px);">
                        {{ $stats['total'] ?? 0 }}
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'escalated' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'escalated'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-arrow-up text-warning me-1"></i> Escalated
                    @php
                    $escalatedCount = \App\Models\Incident::where('escalated_to', $user->id)->where('status',
                    'escalated')->count();
                    @endphp
                    @if($escalatedCount > 0)
                    <span class="badge rounded-pill bg-warning text-dark ms-1 align-self-start"
                        style="font-size: 0.75em; transform: translateY(-3px);">
                        {{ $escalatedCount }}
                    </span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'assigned' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'assigned'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-user-check text-primary me-1"></i> Assigned
                    @php
                    $assignedCount = \App\Models\Incident::where('assigned_to', $user->id)->whereIn('status',
                    ['open','acknowledged','in_progress'])->count();
                    @endphp
                    @if($assignedCount > 0)
                    <span class="badge rounded-pill bg-primary ms-1 align-self-start"
                        style="font-size: 0.75em; transform: translateY(-3px);">
                        {{ $assignedCount }}
                    </span>
                    @endif
                </a>
            </li>
        </ul> --}}


        {{-- Tabs --}}
        {{-- <ul class="nav nav-tabs-custom mb-0" role="tablist" style="border-bottom: 1px solid #e5e7eb;">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'all' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'all'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-list me-1"></i> All Incidents
                    <span class="badge bg-light text-dark ms-1">{{ $stats['total'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'escalated' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'escalated'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-arrow-up text-warning me-1"></i> Escalated to Me
                    @php $escalatedCount = \App\Models\Incident::where('escalated_to', $user->id)->where('status',
                    'escalated')->count(); @endphp
                    @if($escalatedCount > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $escalatedCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'assigned' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'assigned'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-user-check text-primary me-1"></i> Assigned to Me
                    @php $assignedCount = \App\Models\Incident::where('assigned_to', $user->id)->whereIn('status',
                    ['open','acknowledged','in_progress'])->count(); @endphp
                    @if($assignedCount > 0)
                    <span class="badge bg-primary ms-1">{{ $assignedCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'history'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-history text-info me-1"></i> My History
                    @php
                    $historyCount = \App\Models\Incident::where(function($q) use ($user) {
                    $q->where('reported_by', $user->id)
                    ->orWhere('assigned_to', $user->id)
                    ->orWhere('escalated_to', $user->id)
                    ->orWhereHas('escalations', fn($e) => $e->where('escalated_to', $user->id))
                    ->orWhereHas('assignments', fn($a) => $a->where('assigned_to', $user->id))
                    ->orWhereHas('comments', fn($c) => $c->where('user_id', $user->id));
                    })->count();
                    @endphp
                    <span class="badge bg-info ms-1">{{ $historyCount }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'reported' ? 'active' : '' }}"
                    href="{{ route('incidents.index', ['tab' => 'reported'] + request()->except('tab', 'page')) }}">
                    <i class="fas fa-pen text-success me-1"></i> Reported by Me
                    @php $reportedCount = \App\Models\Incident::where('reported_by', $user->id)->count(); @endphp
                    @if($reportedCount > 0)
                    <span class="badge bg-success ms-1">{{ $reportedCount }}</span>
                    @endif
                </a>
            </li>
        </ul> --}}


        {{-- Responsive Tabs Component --}}
        <div class="tabs-container-responsive position-relative w-100" style="border-bottom: 1px solid #e5e7eb;">
            <ul class="nav nav-tabs-custom flex-nowrap mb-0 border-0" role="tablist"
                style="overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; white-space: nowrap;">

                {{-- Tab: All Incidents --}}
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'all' ? 'active' : '' }}"
                        href="{{ route('incidents.index', ['tab' => 'all'] + request()->except('tab', 'page')) }}"
                        style="padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500;">
                        <i class="fas fa-list me-1_5"></i>
                        <span class="tab-text">All Incidents</span>
                        <span class="badge bg-light text-dark ms-1_5 rounded-pill" style="font-size: 0.75rem;">{{
                            $stats['total'] ?? 0 }}</span>
                    </a>
                </li>

                {{-- Tab: Escalated to Me --}}
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'escalated' ? 'active' : '' }}"
                        href="{{ route('incidents.index', ['tab' => 'escalated'] + request()->except('tab', 'page')) }}"
                        style="padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500;">
                        <i class="fas fa-arrow-up text-warning me-1_5"></i>
                        <span class="tab-text">Escalated to Me</span>
                        @php
                        $escalatedCount = $stats['escalated'] ?? \App\Models\Incident::where('escalated_to',
                        $user->id)->where('status', 'escalated')->count();
                        @endphp
                        @if($escalatedCount > 0)
                        <span class="badge bg-warning text-dark ms-1_5 rounded-pill" style="font-size: 0.75rem;">{{
                            $escalatedCount }}</span>
                        @endif
                    </a>
                </li>

                {{-- Tab: Assigned to Me --}}
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'assigned' ? 'active' : '' }}"
                        href="{{ route('incidents.index', ['tab' => 'assigned'] + request()->except('tab', 'page')) }}"
                        style="padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500;">
                        <i class="fas fa-user-check text-primary me-1_5"></i>
                        <span class="tab-text">Assigned to Me</span>
                        @php
                        $assignedCount = $stats['assigned'] ?? \App\Models\Incident::where('assigned_to',
                        $user->id)->whereIn('status', ['open','acknowledged','in_progress'])->count();
                        @endphp
                        @if($assignedCount > 0)
                        <span class="badge bg-primary ms-1_5 rounded-pill" style="font-size: 0.75rem;">{{ $assignedCount
                            }}</span>
                        @endif
                    </a>
                </li>

                {{-- Tab: My History --}}
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'history' ? 'active' : '' }}"
                        href="{{ route('incidents.index', ['tab' => 'history'] + request()->except('tab', 'page')) }}"
                        style="padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500;">
                        <i class="fas fa-history text-info me-1_5"></i>
                        <span class="tab-text">My History</span>
                        @php
                        $historyCount = $stats['history'] ?? \App\Models\Incident::where(function($q) use ($user) {
                        $q->where('reported_by', $user->id)
                        ->orWhere('assigned_to', $user->id)
                        ->orWhere('escalated_to', $user->id)
                        ->orWhereHas('escalations', fn($e) => $e->where('escalated_to', $user->id))
                        ->orWhereHas('assignments', fn($a) => $a->where('assigned_to', $user->id))
                        ->orWhereHas('comments', fn($c) => $c->where('user_id', $user->id));
                        })->count();
                        @endphp
                        <span class="badge bg-info ms-1_5 rounded-pill" style="font-size: 0.75rem;">{{ $historyCount
                            }}</span>
                    </a>
                </li>

                {{-- Tab: Reported by Me --}}
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link d-inline-flex align-items-center {{ $activeTab === 'reported' ? 'active' : '' }}"
                        href="{{ route('incidents.index', ['tab' => 'reported'] + request()->except('tab', 'page')) }}"
                        style="padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500;">
                        <i class="fas fa-pen text-success me-1_5"></i>
                        <span class="tab-text">Reported by Me</span>
                        @php
                        $reportedCount = $stats['reported'] ?? \App\Models\Incident::where('reported_by',
                        $user->id)->count();
                        @endphp
                        @if($reportedCount > 0)
                        <span class="badge bg-success ms-1_5 rounded-pill" style="font-size: 0.75rem;">{{ $reportedCount
                            }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </div>


        {{-- Filters + List Card --}}
        <div class="card shadow-sm" style="border-radius: 0 0 12px 12px;">
            {{-- Filters (hidden for escalated/assigned tabs) --}}
            @if($activeTab === 'all')
            {{-- Filters Card --}}
            <div class="card mb-3">
                {{-- Mobile Toggle Header: Only visible on small screens --}}
                <div class="card-header d-md-none bg-transparent border-0 py-2">
                    <button
                        class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-between"
                        type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilterCollapse"
                        aria-expanded="false" aria-controls="mobileFilterCollapse">
                        <span><i class="fas fa-filter me-2"></i>Filter Incidents</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </button>
                    <div class="col-md-1 col-sm-4 ms-auto d-flex justify-content-end">
                        <div class="d-flex gap-1">
                            <button type="button" class="view-toggle-btn active" onclick="switchView('table')"
                                id="tableBtn" title="Table"><i class="fas fa-list"></i></button>
                            <button type="button" class="view-toggle-btn" onclick="switchView('cards')" id="cardsBtn"
                                title="Cards"><i class="fas fa-th-large"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Collapsible Container: Hidden on mobile by default, always visible on desktop via d-md-block --}}
                <div class="collapse d-md-block" id="mobileFilterCollapse">
                    <div class="card-body py-2 pt-0 pt-md-2">
                        <form id="filterForm" method="GET" action="{{ route('incidents.index') }}">
                            <div class="row g-2 align-items-end">
                                {{-- Search --}}
                                <div class="col-12 col-md-3">
                                    <label class="form-label mb-1" style="font-size: 0.6875rem;">Search</label>
                                    <input type="text" name="search" class="form-control form-control-sm"
                                        placeholder="Search incidents..." value="{{ request('search') }}">
                                </div>

                                {{-- Status & Severity (Paired 50/50 on mobile) --}}
                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size: 0.6875rem;">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open
                                        </option>
                                        <option value="acknowledged" {{ request('status')=='acknowledged' ? 'selected'
                                            : '' }}>
                                            Acknowledged</option>
                                        <option value="in_progress" {{ request('status')=='in_progress' ? 'selected'
                                            : '' }}>In
                                            Progress</option>
                                        <option value="escalated" {{ request('status')=='escalated' ? 'selected' : ''
                                            }}>
                                            Escalated</option>
                                        <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>
                                            Resolved
                                        </option>
                                        <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>
                                            Closed
                                        </option>
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size: 0.6875rem;">Severity</label>
                                    <select name="severity" class="form-select form-select-sm">
                                        <option value="">All Severity</option>
                                        <option value="critical" {{ request('severity')=='critical' ? 'selected' : ''
                                            }}>
                                            Critical</option>
                                        <option value="high" {{ request('severity')=='high' ? 'selected' : '' }}>High
                                        </option>
                                        <option value="medium" {{ request('severity')=='medium' ? 'selected' : '' }}>
                                            Medium
                                        </option>
                                        <option value="low" {{ request('severity')=='low' ? 'selected' : '' }}>Low
                                        </option>
                                    </select>
                                </div>

                                {{-- Department Filter (Admin Only) --}}
                                @if(Auth::user()->isAdmin())
                                <div class="col-12 col-md-2">
                                    <label class="form-label mb-1" style="font-size: 0.6875rem;">Department</label>
                                    <select name="department_id" class="form-select form-select-sm">
                                        <option value="">All Departments</option>
                                        @foreach(\App\Models\Department::active()->ordered()->get() as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id')==$dept->id ?
                                            'selected'
                                            : ''
                                            }}>
                                            {{ $dept->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                {{-- Date From & Date To (Paired 50/50 on mobile) --}}
                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size: 0.6875rem;">Date From</label>
                                    <input type="date" name="date_from" class="form-control form-control-sm"
                                        value="{{ request('date_from') }}">
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label mb-1" style="font-size: 0.6875rem;">Date To</label>
                                    <input type="date" name="date_to" class="form-control form-control-sm"
                                        value="{{ request('date_to') }}">
                                </div>

                                {{-- Action Buttons --}}
                                <div class="col-12 col-md-auto ms-md-auto d-flex gap-1 pt-2 pt-md-0">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill flex-md-grow-0 px-3"
                                        title="Filter" data-bs-placement="top">
                                        <i class="fas fa-filter me-1"></i>
                                    </button>
                                    <a href="{{ route('incidents.index') }}"
                                        class="btn btn-light btn-sm flex-fill flex-md-grow-0" title="Redo"
                                        data-bs-placement="top">
                                        <i class="fas fa-redo"></i>
                                    </a>

                                    <div class="col-md-1 col-sm-4 ms-auto d-flex justify-content-end">
                                        <div class="d-flex gap-1">
                                            <button type="button" class="view-toggle-btn active"
                                                onclick="switchView('table')" id="tableBtn" title="Table View"
                                                data-bs-placement="top"><i class="fas fa-list"></i></button>
                                            <button type="button" class="view-toggle-btn" onclick="switchView('cards')"
                                                id="cardsBtn" title="Cards view" data-bs-placement="top"><i
                                                    class="fas fa-th-large"></i></button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- Content --}}
            <div class="card-body p-0">
                @if(isset($incidents) && count($incidents) > 0)
                {{-- Table View --}}
                <div id="tableView" class="table-responsive">
                    <div class="table-responsive d-none d-md-block" id="listView">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">ID</th>
                                    <th>Title</th>
                                    @if(in_array($activeTab, ['history', 'reported']))
                                    <th>Your Role</th>
                                    @endif

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
                                        <a href="{{ route('incidents.show', $incident) }}"
                                            class="text-decoration-none fw-medium">
                                            {{ Str::limit($incident->title, 50) }}
                                        </a>
                                    </td>
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

                                    <td>
                                        <span class="badge"
                                            style="background: {{ $incident->category?->color ?? '#6B7280' }}20; color: {{ $incident->category?->color ?? '#6B7280' }}">
                                            {{ $incident->category?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge"
                                            style="background: {{ $incident->department?->color ?? '#6B7280' }}20; color: {{ $incident->department?->color ?? '#6B7280' }}">
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
                                            <img src="{{ $incident->assignedTo->avatar_url }}" alt="Assignee"
                                                class="rounded-circle" width="24" height="24">
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
                                                <a href="{{ route('incidents.show', $incident) }}"
                                                    class="dropdown-item">
                                                    <i class="fas fa-eye me-2"></i>View
                                                </a>
                                                @can('edit-incident')
                                                <a href="{{ route('incidents.edit', $incident) }}"
                                                    class="dropdown-item">
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
                </div>

                {{-- Cards View --}}
                <div id="cardsView" class="d-none p-2">
                    <div class="row g-2">
                        @foreach($incidents as $incident)
                        <div class="col-md-6 col-lg-4">
                            @include('incidents.partials.incident-card', ['incident' => $incident])
                        </div>
                        @endforeach
                    </div>
                </div>
                {{-- @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h6>
                        @if($activeTab === 'escalated')
                        No incidents escalated to you
                        @elseif($activeTab === 'assigned')
                        No incidents assigned to you
                        @else
                        No incidents found
                        @endif
                    </h6>
                    <p class="text-muted small">
                        @if($activeTab === 'escalated')
                        You're all caught up! No pending escalations.
                        @elseif($activeTab === 'assigned')
                        No incidents currently assigned to you.
                        @else
                        @if(request()->hasAny(['search','status','severity']))
                        No incidents match your filters.
                        @else
                        No incidents have been reported yet.
                        @endif
                        @endif
                    </p>
                    @if($activeTab === 'all')
                    <a href="{{ route('incidents.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Report First Incident
                    </a>
                    @endif
                </div>
                @endif --}}

                @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h6>
                        @switch($activeTab)
                        @case('escalated')
                        No incidents escalated to you
                        @break
                        @case('assigned')
                        No incidents currently assigned to you
                        @break
                        @case('history')
                        No incident history found
                        @break
                        @case('reported')
                        You haven't reported any incidents yet
                        @break
                        @default
                        No incidents found
                        @endswitch
                    </h6>
                    <p class="text-muted small">
                        @switch($activeTab)
                        @case('escalated')
                        You're all caught up! No pending escalations requiring your attention.
                        @break
                        @case('assigned')
                        No incidents are currently assigned to you. Great job!
                        @break
                        @case('history')
                        You haven't been involved in any incidents yet. Once you report, get assigned, or escalate
                        incidents, they will appear here.
                        @break
                        @case('reported')
                        You haven't reported any incidents yet. Click below to report your first incident.
                        @break
                        @default
                        @if(request()->hasAny(['search','status','severity']))
                        No incidents match your filters. Try adjusting your search criteria.
                        @else
                        No incidents have been reported yet.
                        @endif
                        @endswitch
                    </p>
                    @if(in_array($activeTab, ['all', 'reported']))
                    <a href="{{ route('incidents.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Report Incident
                    </a>
                    @endif
                </div>
                @endif
            </div>

            @if(isset($incidents) && $incidents->hasPages())
            <div class="card-footer bg-white">
                {{ $incidents->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
    @endsection

    @push('scripts')
    <script>
        function switchView(view) {
        document.getElementById('tableView').classList.toggle('d-none', view !== 'table');
        document.getElementById('cardsView').classList.toggle('d-none', view !== 'cards');
        document.getElementById('tableBtn').classList.toggle('active', view === 'table');
        document.getElementById('cardsBtn').classList.toggle('active', view === 'cards');
        localStorage.setItem('incidentView', view);
    }
    document.addEventListener('DOMContentLoaded', function() {
        switchView(localStorage.getItem('incidentView') || 'table');
    });
    </script>
    @endpush