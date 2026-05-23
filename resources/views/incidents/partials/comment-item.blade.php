{{-- resources/views/incidents/partials/comment-item.blade.php --}}
<div class="d-flex gap-3 mb-3 pb-3 border-bottom comment-item">
    <img src="{{ $comment->user?->avatar_url ?? asset('images/default-avatar.png') }}"
         class="rounded-circle flex-shrink-0" width="36" height="36" style="object-fit: cover;">
    <div class="flex-grow-1 min-width-0">
        <div class="d-flex gap-2 align-items-center mb-1">
            <strong style="font-size: 0.8125rem;">{{ $comment->user?->name ?? 'Unknown' }}</strong>
            <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
            @if($comment->is_internal)
                <span class="badge bg-warning text-dark">Internal</span>
            @endif
        </div>
        <p class="mb-0" style="font-size: 0.8125rem; white-space: pre-wrap;">{{ $comment->content }}</p>

        {{-- Replies --}}
        @if($comment->replies->count() > 0)
            <div class="mt-2 ms-2 ps-3 border-start">
                @foreach($comment->replies as $reply)
                    <div class="mb-2">
                        <div class="d-flex gap-2 align-items-center mb-1">
                            <strong style="font-size: 0.75rem;">{{ $reply->user?->name ?? 'Unknown' }}</strong>
                            <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-0 small">{{ $reply->content }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
