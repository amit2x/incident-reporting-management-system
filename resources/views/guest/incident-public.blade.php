{{-- resources/views/incident-public.blade.php --}}
@extends('layouts.app')

@section('title', '#' . $incident->incident_id . ' - ' . $incident->title)

@push('styles')
<style>
    .public-detail-container {
        max-width: 720px;
        margin: 0 auto;
    }
    .comment-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<div class="public-detail-container px-3 py-3">

    {{-- Back Button --}}
    <a href="{{ url('/') }}" class="text-muted text-decoration-none small mb-3 d-inline-block">
        <i class="fas fa-arrow-left me-1"></i> Back to Feed
    </a>

    {{-- Incident Card --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-4">
            {{-- Header --}}
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="{{ $incident->reporter?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&size=48' }}"
                     class="rounded-circle" width="48" height="48" style="object-fit: cover;">
                <div>
                    <div class="fw-semibold">{{ $incident->reporter?->name ?? 'Unknown' }}</div>
                    <small class="text-muted">{{ $incident->created_at->format('d M Y, H:i') }}</small>
                </div>
                <span class="badge status-{{ str_replace('_', '-', $incident->status) }} ms-auto">
                    {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                </span>
            </div>

            {{-- Badges --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-light text-dark">#{{ $incident->incident_id }}</span>
                <span class="badge priority-{{ $incident->priority }}">{{ ucfirst($incident->priority) }}</span>
                <span class="badge" style="background: {{ $incident->category?->color }}15; color: {{ $incident->category?->color }};">
                    {{ $incident->category?->name }}
                </span>
                <span class="badge" style="background: {{ $incident->department?->color }}15; color: {{ $incident->department?->color }};">
                    {{ $incident->department?->name }}
                </span>
                @if($incident->location)
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-map-marker-alt me-1"></i>{{ $incident->location }}
                    </span>
                @endif
            </div>

            {{-- Title & Description --}}
            <h4 class="fw-bold mb-3">{{ $incident->title }}</h4>
            <p class="text-muted mb-0" style="white-space: pre-wrap; line-height: 1.7;">{{ $incident->description }}</p>

            {{-- Tags --}}
            @if($incident->tags && count($incident->tags) > 0)
                <div class="mt-3">
                    @foreach($incident->tags as $tag)
                        <span class="badge bg-light text-secondary me-1">#{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            {{-- Media --}}
            @if($incident->media->count() > 0)
                <div class="mt-3">
                    <div class="row g-2">
                        @foreach($incident->media as $media)
                            <div class="col-6 col-md-4">
                                @if($media->isImage())
                                    <img src="{{ $media->url }}" class="img-fluid rounded"
                                         style="width:100%; height:200px; object-fit:cover; cursor:pointer;"
                                         onclick="window.open('{{ $media->url }}', '_blank')"
                                         loading="lazy">
                                @else
                                    <a href="{{ $media->url }}" target="_blank" class="text-decoration-none">
                                        <div class="border rounded p-3 text-center" style="height:200px;">
                                            <i class="fas fa-file fa-3x text-muted mt-4"></i>
                                            <small class="d-block mt-2">{{ $media->original_name }}</small>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Details Card --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white"><strong>Incident Details</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <small class="text-muted">Status</small>
                    <div class="fw-medium">{{ str_replace('_', ' ', ucfirst($incident->status)) }}</div>
                </div>
                <div class="col-sm-6">
                    <small class="text-muted">Assigned To</small>
                    <div class="fw-medium">{{ $incident->assignedTo?->name ?? 'Unassigned' }}</div>
                </div>
                <div class="col-sm-6">
                    <small class="text-muted">Reported On</small>
                    <div class="fw-medium">{{ $incident->created_at->format('d M Y, H:i') }}</div>
                </div>
                <div class="col-sm-6">
                    <small class="text-muted">Last Updated</small>
                    <div class="fw-medium">{{ $incident->updated_at->format('d M Y, H:i') }}</div>
                </div>
                @if($incident->resolution_notes)
                    <div class="col-12">
                        <small class="text-muted">Resolution Notes</small>
                        <div class="fw-medium">{{ $incident->resolution_notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Comments Section --}}
    <div class="card shadow-sm" id="comments">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>
                <i class="fas fa-comments me-2"></i>Comments ({{ $incident->comments_count }})
            </strong>
        </div>
        <div class="card-body">
            @auth
                <form action="{{ route('incidents.comments.store', $incident) }}" method="POST" class="mb-4">
                    @csrf
                    <textarea name="content" class="form-control mb-2" rows="2"
                              placeholder="Write a comment..."></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-paper-plane me-1"></i> Post Comment
                    </button>
                </form>
            @else
                <div class="text-center py-3 mb-3 bg-light rounded-3">
                    <p class="mb-2 text-muted small">Sign in to add a comment</p>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> Login to Comment
                    </a>
                </div>
            @endauth

            @forelse($incident->comments as $comment)
                <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                    <img src="{{ $comment->user?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&size=36' }}"
                         class="comment-avatar flex-shrink-0">
                    <div>
                        <div class="d-flex gap-2 align-items-center mb-1">
                            <strong style="font-size: 0.8125rem;">{{ $comment->user?->name ?? 'Unknown' }}</strong>
                            <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-0" style="font-size: 0.8125rem;">{{ $comment->content }}</p>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-3">No comments yet. Be the first to comment!</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
