{{-- resources/views/reports/user-performance.blade.php --}}
@extends('layouts.app')

@section('title', 'User Performance - IRMS')

@section('content')
<div class="py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <h4 class="fw-bold mt-1 mb-0"><i class="fas fa-users text-info me-2"></i>User Performance</h4>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="period" onchange="this.form.submit()">
                        <option value="last7days" {{ $period=='last7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last30days" {{ $period=='last30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="thisMonth" {{ $period=='thisMonth' ? 'selected' : '' }}>This Month</option>
                        <option value="lastMonth" {{ $period=='lastMonth' ? 'selected' : '' }}>Last Month</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="department_id" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId==$dept->id ? 'selected' : '' }}>{{ $dept->name
                            }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="role" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $roleFilter==$role->name ? 'selected' : '' }}>{{
                            ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <a href="{{ route('reports.user-performance') }}" class="btn btn-light btn-sm w-100"><i
                            class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Reported</th>
                            <th>Assigned</th>
                            <th>Resolved</th>
                            <th>Avg Response</th>
                            <th>Avg Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userStats as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $user['avatar_url'] }}" class="rounded-circle" width="28" height="28"
                                        style="object-fit:cover;">
                                    <span class="fw-medium small">{{ $user['name'] }}</span>
                                </div>
                            </td>
                            <td><small>{{ $user['department'] }}</small></td>
                            <td><span class="badge bg-light text-dark small">{{ $user['role'] }}</span></td>
                            <td>{{ $user['reported'] }}</td>
                            <td>{{ $user['assigned'] }}</td>
                            <td><span class="badge bg-success">{{ $user['resolved'] }}</span></td>
                            <td>{{ $user['avg_response'] }}m</td>
                            <td>{{ $user['avg_resolution'] }}m</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No user data found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection