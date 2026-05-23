{{-- resources/views/notifications/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Notifications - IRMS')

@push('styles')
<style>
    .notification-item {
        transition: all 0.15s ease;
        border-left: 3px solid transparent;
        cursor: pointer;
    }
    .notification-item:hover {
        background: #f8fafc;
    }
    .notification-item.unread {
        border-left-color: #3B82F6;
        background: #f0f5ff;
    }
    .notification-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fas fa-bell text-primary me-2"></i>Notifications
                @if($unreadCount > 0)
                    <span class="badge bg-danger ms-2 rounded-pill">{{ $unreadCount }}</span>
                @endif
            </h4>
            <p class="text-muted small mb-0">Stay updated with incident activities</p>
        </div>
        @if($unreadCount > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-check-double me-1"></i> Mark All as Read
                </button>
            </form>
        @endif
    </div>

    {{-- Notifications List --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <strong>All Notifications ({{ $notifications->total() }})</strong>
            <small class="text-muted">{{ $unreadCount }} unread</small>
        </div>
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                @php
                    $type = $notification->data['type'] ?? 'general';
                    $icon = match($type) {
                        'new_incident' => 'fa-exclamation-triangle',
                        'incident_assigned' => 'fa-user-plus',
                        'incident_escalated' => 'fa-arrow-up',
                        'incident_resolved' => 'fa-check-circle',
                        'incident_closed' => 'fa-lock',
                        'incident_reopened' => 'fa-redo',
                        'new_comment' => 'fa-comment',
                        'mentioned' => 'fa-at',
                        default => 'fa-bell',
                    };
                    $color = match($type) {
                        'new_incident', 'incident_escalated' => '#EF4444',
                        'incident_assigned' => '#3B82F6',
                        'incident_resolved' => '#10B981',
                        'incident_closed' => '#6B7280',
                        'incident_reopened' => '#F59E0B',
                        'new_comment', 'mentioned' => '#8B5CF6',
                        default => '#6B7280',
                    };
                @endphp
                <a href="{{ route('notifications.handle-click', $notification->id) }}"
                   class="list-group-item list-group-item-action notification-item {{ $notification->read_at ? '' : 'unread' }}"
                   data-notification-id="{{ $notification->id }}">
                    <div class="d-flex align-items-start gap-3 py-2">
                        {{-- Icon --}}
                        <div class="notification-icon-wrapper" style="background: {{ $color }}15; color: {{ $color }};">
                            <i class="fas {{ $icon }}"></i>
                        </div>

                        {{-- Content --}}
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1" style="font-size: 0.875rem;">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </h6>
                                <small class="text-muted ms-2 flex-shrink-0" style="font-size: 0.6875rem;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <p class="mb-1 text-muted" style="font-size: 0.8125rem;">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            @if(isset($notification->data['incident_id']))
                                <span class="badge bg-light text-dark" style="font-size: 0.65rem;">
                                    <i class="fas fa-hashtag me-1"></i>
                                    {{ $notification->data['incident_number'] ?? 'INC-' . $notification->data['incident_id'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Unread dot + Delete --}}
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            @if(!$notification->read_at)
                                <span class="unread-dot"></span>
                            @endif
                            <form action="{{ route('notifications.delete', $notification->id) }}" method="POST"
                                  onclick="event.stopPropagation();" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-light btn-sm rounded-circle"
                                        style="width: 28px; height: 28px; padding: 0;"
                                        title="Delete"
                                        onclick="return confirm('Delete this notification?');">
                                    <i class="fas fa-times text-muted" style="font-size: 0.625rem;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted">No notifications</h6>
                    <p class="text-muted small mb-0">You'll be notified about incidents, assignments, and updates</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh unread count every 30 seconds
    function updateUnreadCount() {
        fetch('{{ route("notifications.unread-count") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var badge = document.querySelector('.badge.bg-danger');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                }
            } else {
                if (badge) badge.remove();
            }
        })
        .catch(function(error) {
            console.log('Error fetching notification count:', error);
        });
    }

    setInterval(updateUnreadCount, 30000);
});
</script>
@endpush
