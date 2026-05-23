{{-- resources/views/admin/users/activity.blade.php --}}
@extends('layouts.app')

@section('title', 'Activity Log - ' . $user->name . ' - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="mb-3">
        <a href="{{ route('admin.users.show', $user) }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to User
        </a>
        <h4 class="fw-bold mt-1 mb-0">Activity Log: {{ $user->name }}</h4>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Activity History ({{ $activities->total() }})</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Action</th>
                            <th>Model</th>
                            <th>IP Address</th>
                            <th>URL</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>
                                    <span class="badge" style="background: {{ $activity->action_color }}20; color: {{ $activity->action_color }};">
                                        {{ $activity->action_label }}
                                    </span>
                                </td>
                                <td class="small">
                                    @if($activity->model_type)
                                        {{ class_basename($activity->model_type) }} #{{ $activity->model_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><small>{{ $activity->ip_address }}</small></td>
                                <td><small class="text-muted">{{ Str::limit($activity->url, 50) }}</small></td>
                                <td><small class="text-muted">{{ $activity->created_at->format('d M Y, H:i') }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No activity recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($activities->hasPages())
            <div class="card-footer bg-white">
                {{ $activities->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
