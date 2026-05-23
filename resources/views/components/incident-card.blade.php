@props(['incident'])

<div class="incident-card" id="incident-{{ $incident->id }}">
    <div class="incident-header">
        @if(!$incident->is_anonymous)
            <x-user-avatar :user="$incident->reporter" />
        @else
            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                 style="width: 40px; height: 40px;">
                <i class="fas fa-user-secret text-white"></i>
            </div>
        @endif
        
        <div class="incident-meta">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold">
                    {{ $incident->is_anonymous ? 'Anonymous' : $incident->reporter->name }}
                </span>
                <span class="badge" style="background: {{ $incident->department->color }}20; color: {{ $incident->department->color }}">
                    {{ $incident->department->code }}
                </span>
            </div>
            <div class="text-muted small">
                {{ $incident->created_at->diffForHumans() }}
                @if($incident->location)
                    <i class="fas fa-map-marker-alt ms-2 me-1"></i>
                    {{ $incident->location }}
                @endif
            </div>
        </div>
    </div>
    
    <div class="incident-body">
        <div class="mb-2">
            <span class="badge bg-light text-dark me-2">#{{ $incident->incident_id }}</span>
            <x-incident-status-badge :status="$incident->status" />
            <x-priority-badge :priority="$incident->priority" class="ms-1" />
        </div>
        
        <h5 class="incident-title">{{ $incident->title }}</h5>
        <p class="incident-description">{{ Str::limit($incident->description, 150) }}</p>
        
        @if($incident->media->count() > 0)
            <div class="media-preview">
                @foreach($incident->media->take(3) as $media)
                    @if($media->isImage())
                        <img src="{{ $media->thumbnail_url }}" alt="Media" loading="lazy">
                    @endif
                @endforeach
                @if($incident->media->count() > 3)
                    <div class="more-media">+{{ $incident->media->count() - 3 }}</div>
                @endif
            </div>
        @endif
    </div>
    
    <div class="incident-footer">
        <button class="action-btn" onclick="toggleLike({{ $incident->id }})">
            <i class="far fa-thumbs-up"></i> {{ $incident->likes_count }}
        </button>
        <button class="action-btn" onclick="focusComment({{ $incident->id }})">
            <i class="far fa-comment"></i> {{ $incident->comments_count }}
        </button>
        <a href="{{ route('incidents.show', $incident) }}" class="action-btn ms-auto">
            View Details <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>