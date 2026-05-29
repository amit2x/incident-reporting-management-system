{{-- resources/views/admin/audit-logs.blade.php --}}
@extends('layouts.app')

@section('title', 'Audit Logs - IRMS')

@push('styles')
<style>
    .log-row {
        transition: all 0.15s ease;
    }

    .log-row:hover {
        background: #f8fafc;
    }

    .action-badge {
        font-size: 0.6875rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .log-detail {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .hide-mobile-col {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Audit Logs</h4>
            <p class="text-muted small mb-0">Track all user activities and system events</p>
        </div>
        <div>
            <form action="{{ route('admin.audit-logs') }}" method="GET" class="d-inline">
                <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i> Export
                </button>
            </form>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id')==$user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action')==$action ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $action)) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="form-label small mb-1">IP Address</label>
                    <input type="text" name="ip_address" class="form-control form-control-sm"
                        placeholder="e.g., 192.168.1.1" value="{{ request('ip_address') }}">
                </div>
                <div class="col-md-2 col-sm-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.audit-logs') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Summary --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold text-primary">{{ $logs->total() }}</div>
                <small class="text-muted">Total Logs</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold text-info">{{ $logs->where('action', 'login')->count() }}</div>
                <small class="text-muted">Logins</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold text-success">{{ $logs->where('action', 'created')->count() }}</div>
                <small class="text-muted">Created</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 p-2 text-center border">
                <div class="fw-bold text-danger">{{ $logs->where('action', 'deleted')->count() }}</div>
                <small class="text-muted">Deleted</small>
            </div>
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <strong>Activity Logs ({{ $logs->total() }})</strong>
            <small class="text-muted">Showing {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>User</th>
                            <th>Action</th>
                            <th class="hide-mobile-col">Details</th>
                            <th class="hide-mobile-col">IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr class="log-row">
                            <td class="text-muted small">#{{ $log->id }}</td>
                            <td>
                                @if($log->user)
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $log->user->avatar_url }}" class="rounded-circle" width="28"
                                        height="28" style="object-fit:cover;">
                                    <div>
                                        <div class="fw-medium small">{{ $log->user->name }}</div>
                                        <small class="text-muted" style="font-size:0.65rem;">{{ $log->user->email
                                            }}</small>
                                    </div>
                                </div>
                                @else
                                <span class="text-muted">System</span>
                                @endif
                            </td>
                            <td>
                                <span class="action-badge"
                                    style="background: {{ $log->action_color }}20; color: {{ $log->action_color }};">
                                    <i class="fas {{ $log->action_icon }} me-1"></i>
                                    {{ $log->action_label }}
                                </span>
                            </td>
                            <td class="hide-mobile-col">
                                <div class="log-detail small">
                                    @if($log->model_type)
                                    <span class="text-muted">{{ class_basename($log->model_type) }}</span>
                                    @if($log->model_id)
                                    <span class="badge bg-light text-dark">#{{ $log->model_id }}</span>
                                    @endif
                                    @endif
                                    @if($log->url)
                                    <div class="text-muted" style="font-size:0.65rem;">{{ Str::limit($log->url, 60) }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="hide-mobile-col">
                                <small class="text-muted">{{ $log->ip_address ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $log->created_at->format('d M Y') }}</small>
                                <br>
                                <small class="text-muted" style="font-size:0.65rem;">{{
                                    $log->created_at->format('H:i:s') }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-history fa-3x mb-3 d-block"></i>
                                No activity logs found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection