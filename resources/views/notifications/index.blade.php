@extends('layouts.app')

@section('title', 'Notifications - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Notifications</li>
@endsection

@section('content')
<div class="container-fluid p-3">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </h5>
                    <button class="btn btn-light btn-sm" onclick="markAllAsRead()">
                        <i class="fas fa-check-double me-1"></i>Mark All as Read
                    </button>
                </div>
                
                <div class="list-group list-group-flush notification-list">
                    @forelse($notifications as $notification)
                        <a href="{{ $notification->data['url'] ?? '#' }}" 
                           class="list-group-item list-group-item-action notification-item {{ $notification->read_at ? '' : 'unread' }}"
                           data-notification-id="{{ $notification->id }}">
                            <div class="d-flex align-items-center">
                                <div class="notification-icon me-3">
                                    @switch($notification->data['type'] ?? '')
                                        @case('new_incident')
                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                            @break
                                        @case('incident_assigned')
                                            <i class="fas fa-user-plus text-primary"></i>
                                            @break
                                        @case('incident_escalated')
                                            <i class="fas fa-arrow-up text-warning"></i>
                                            @break
                                        @case('incident_resolved')
                                            <i class="fas fa-check-circle text-success"></i>
                                            @break
                                        @case('new_comment')
                                            <i class="fas fa-comment text-info"></i>
                                            @break
                                        @default
                                            <i class="fas fa-bell text-muted"></i>
                                    @endswitch
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong class="notification-title">{{ $notification->data['title'] ?? 'Notification' }}</strong>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-0 small text-muted">{{ $notification->data['message'] ?? '' }}</p>
                                </div>
                                @if(!$notification->read_at)
                                    <span class="unread-dot ms-2"></span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No notifications yet</p>
                            <small class="text-muted">You'll be notified about incidents, assignments, and updates</small>
                        </div>
                    @endforelse
                </div>
            </div>
            
            {{-- Pagination --}}
            @if($notifications->hasPages())
                <div class="mt-3">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .notification-item.unread {
        background: #eff6ff;
        border-left: 3px solid #2563eb;
    }
    .unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #2563eb;
    }
    .notification-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        border-radius: 50%;
    }
</style>
@endpush

@push('scripts')
<script>
    function markAllAsRead() {
        fetch('{{ route("notifications.readAll") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                    item.querySelector('.unread-dot')?.remove();
                });
                toastr.success('All notifications marked as read');
            }
        });
    }
</script>
@endpush