{{-- resources/views/admin/escalation-matrix/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Escalation Matrix - IRMS')

@push('styles')
<style>
    .level-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    .level-1 { background: #DBEAFE; color: #1E40AF; }
    .level-2 { background: #FEF3C7; color: #92400E; }
    .level-3 { background: #FEE2E2; color: #991B1B; }
    .level-4 { background: #EDE9FE; color: #5B21B6; }

    .dept-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .dept-header {
        background: #f9fafb;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dept-header .dept-name {
        font-weight: 600;
        font-size: 0.9375rem;
    }

    .matrix-row {
        transition: all 0.15s ease;
    }
    .matrix-row:hover {
        background: #f8fafc;
    }

    .no-entries {
        padding: 30px;
        text-align: center;
        color: #9ca3af;
    }

    @media (max-width: 767.98px) {
        .matrix-mobile-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
            background: white;
        }
        .matrix-mobile-card .card-header-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .matrix-mobile-card .card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 0.8125rem;
        }
        .matrix-mobile-card .detail-label {
            color: #6b7280;
            font-size: 0.6875rem;
        }
        .matrix-mobile-card .detail-value {
            font-weight: 500;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1">Escalation Matrix</h4>
            <p class="text-muted small mb-0">Define escalation rules for automatic incident escalation</p>
        </div>
        <a href="{{ route('admin.escalation-matrix.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Entry
        </a>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small mb-1">Filter by Department</label>
                    <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small mb-1">Filter by Category</label>
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <a href="{{ route('admin.escalation-matrix.index') }}" class="btn btn-light btn-sm w-100">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Matrix Display --}}
    @forelse($groupedMatrices as $deptId => $entries)
        @php
            $dept = $entries->first()->department;
            $activeCount = $entries->where('is_active', true)->count();
        @endphp
        <div class="dept-section">
            <div class="dept-header">
                <span style="width: 10px; height: 10px; border-radius: 3px; background: {{ $dept->color ?? '#6B7280' }}; flex-shrink: 0;"></span>
                <span class="dept-name">{{ $dept->name ?? 'Unknown Department' }}</span>
                <span class="badge bg-light text-dark ms-auto">{{ $entries->count() }} entries</span>
                @if($activeCount < $entries->count())
                    <span class="badge bg-warning text-dark">{{ $activeCount }} active</span>
                @endif
            </div>

            {{-- Desktop Table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Level</th>
                            <th>Category</th>
                            <th>Timeout</th>
                            <th>Escalate To (User)</th>
                            <th>Target Dept</th>
                            <th>Notify</th>
                            <th style="width: 80px;">Status</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr class="matrix-row">
                                <td>
                                    <span class="level-badge level-{{ $entry->level }}">L{{ $entry->level }}</span>
                                </td>
                                <td>
                                    @if($entry->category)
                                        <span style="color: {{ $entry->category->color }};">
                                            <i class="{{ $entry->category->icon ?? 'fas fa-tag' }} me-1"></i>
                                            {{ $entry->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted fst-italic">All Categories</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-medium">
                                        @if($entry->timeout_minutes >= 60)
                                            {{ floor($entry->timeout_minutes / 60) }}h{{ $entry->timeout_minutes % 60 > 0 ? ' ' . $entry->timeout_minutes % 60 . 'm' : '' }}
                                        @else
                                            {{ $entry->timeout_minutes }}m
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $entry->escalateToUser?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&size=24' }}"
                                             class="rounded-circle" width="24" height="24" style="object-fit: cover;">
                                        <span class="small">{{ $entry->escalateToUser?->name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background: {{ $entry->escalateToDepartment?->color ?? '#6B7280' }}20; color: {{ $entry->escalateToDepartment?->color ?? '#6B7280' }};">
                                        {{ $entry->escalateToDepartment?->code ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($entry->notify_via_email)
                                            <i class="fas fa-envelope text-info" title="Email notification"></i>
                                        @endif
                                        @if($entry->notify_via_push)
                                            <i class="fas fa-bell text-primary" title="Push notification"></i>
                                        @endif
                                        @if(!$entry->notify_via_email && !$entry->notify_via_push)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $entry->is_active ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.65rem;">
                                        {{ $entry->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.escalation-matrix.show', $entry) }}"
                                           class="btn btn-light btn-sm" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.escalation-matrix.edit', $entry) }}"
                                           class="btn btn-light btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.escalation-matrix.destroy', $entry) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this escalation entry?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-light btn-sm text-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="d-md-none p-2">
                @foreach($entries as $entry)
                    <div class="matrix-mobile-card">
                        <div class="card-header-row">
                            <span class="level-badge level-{{ $entry->level }}">L{{ $entry->level }}</span>
                            <div class="flex-grow-1">
                                <strong style="font-size: 0.875rem;">
                                    @if($entry->category)
                                        {{ $entry->category->name }}
                                    @else
                                        All Categories
                                    @endif
                                </strong>
                            </div>
                            <span class="badge {{ $entry->is_active ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.6rem;">
                                {{ $entry->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="card-details">
                            <div>
                                <div class="detail-label">Timeout</div>
                                <div class="detail-value">
                                    @if($entry->timeout_minutes >= 60)
                                        {{ floor($entry->timeout_minutes / 60) }}h{{ $entry->timeout_minutes % 60 > 0 ? ' ' . $entry->timeout_minutes % 60 . 'm' : '' }}
                                    @else
                                        {{ $entry->timeout_minutes }}m
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="detail-label">Escalate To</div>
                                <div class="detail-value">{{ $entry->escalateToUser?->name ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="detail-label">Target Dept</div>
                                <div class="detail-value">{{ $entry->escalateToDepartment?->code ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="detail-label">Notify</div>
                                <div class="detail-value">
                                    @if($entry->notify_via_email)<i class="fas fa-envelope text-info me-1"></i>@endif
                                    @if($entry->notify_via_push)<i class="fas fa-bell text-primary"></i>@endif
                                    @if(!$entry->notify_via_email && !$entry->notify_via_push)-@endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-1 mt-2 pt-2 border-top">
                            <a href="{{ route('admin.escalation-matrix.show', $entry) }}" class="btn btn-light btn-sm flex-grow-1">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            <a href="{{ route('admin.escalation-matrix.edit', $entry) }}" class="btn btn-light btn-sm flex-grow-1">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-arrow-up-right-dots fa-3x mb-3 d-block"></i>
            <h5>No escalation matrix entries found</h5>
            <p class="mb-3">Define escalation rules to automatically escalate incidents when SLA is breached</p>
            <a href="{{ route('admin.escalation-matrix.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create First Entry
            </a>
        </div>
    @endforelse
</div>
@endsection
