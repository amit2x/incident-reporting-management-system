<div class="incident-card" data-incident-id="{{ $incident->id }}">
    {{-- Header --}}
    <div class="incident-header">
        @if(!$incident->is_anonymous)
            <img src="{{ $incident->reporter->avatar_url }}" alt="{{ $incident->reporter->name }}" class="user-avatar">
        @else
            <div class="user-avatar bg-secondary d-flex align-items-center justify-content-center">
                <i class="fas fa-user-secret text-white"></i>
            </div>
        @endif
        
        <div class="incident-meta">
            <div class="d-flex align-items-center gap-2">
                <span class="user-name">
                    {{ $incident->is_anonymous ? 'Anonymous' : $incident->reporter->name }}
                </span>
                <span class="badge" style="background: {{ $incident->department->color }}20; color: {{ $incident->department->color }}; font-size: 0.625rem;">
                    {{ $incident->department->code }}
                </span>
            </div>
            <div class="incident-time">
                <i class="far fa-clock me-1"></i>
                {{ $incident->created_at->diffForHumans() }}
                @if($incident->location)
                    <i class="fas fa-map-marker-alt ms-2 me-1"></i>
                    {{ $incident->location }}
                @endif
            </div>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="{{ route('incidents.show', $incident) }}" class="dropdown-item">
                    <i class="fas fa-eye me-2"></i>View Details
                </a>
                @can('edit-incident')
                <a href="{{ route('incidents.edit', $incident) }}" class="dropdown-item">
                    <i class="fas fa-edit me-2"></i>Edit
                </a>
                @endcan
                @can('delete-incident')
                <div class="dropdown-divider"></div>
                <button class="dropdown-item text-danger" onclick="deleteIncident({{ $incident->id }})">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
                @endcan
            </div>
        </div>
    </div>
    
    {{-- Body --}}
    <div class="incident-body">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="incident-id-badge badge bg-light text-dark">
                #{{ $incident->incident_id }}
            </span>
            <span class="status-badge {{ str_replace('_', '-', $incident->status) }}">
                {{ str_replace('_', ' ', ucfirst($incident->status)) }}
            </span>
            <span class="priority-badge {{ $incident->priority }}">
                <i class="fas fa-flag me-1"></i>{{ ucfirst($incident->priority) }}
            </span>
        </div>
        
        <h5 class="incident-title">{{ $incident->title }}</h5>
        <p class="incident-description">
            {{ Str::limit($incident->description, 200) }}
        </p>
        
        {{-- Category & Tags --}}
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="badge" style="background: {{ $incident->category->color }}20; color: {{ $incident->category->color }}">
                <i class="{{ $incident->category->icon }} me-1"></i>
                {{ $incident->category->name }}
            </span>
            @if($incident->tags)
                @foreach($incident->tags as $tag)
                    <span class="badge bg-light text-dark">{{ $tag }}</span>
                @endforeach
            @endif
        </div>
        
        {{-- Media Preview --}}
        @if($incident->media->count() > 0)
            <div class="media-preview">
                @foreach($incident->media->take(4) as $media)
                    @if($media->isImage())
                        <img src="{{ $media->thumbnail_url }}" alt="Incident media" 
                             loading="lazy" onclick="openImagePreview('{{ $media->url }}')">
                    @elseif($media->isVideo())
                        <video src="{{ $media->url }}" preload="metadata"></video>
                    @else
                        <div class="document-preview d-flex align-items-center justify-content-center">
                            <i class="fas fa-file-pdf fa-2x text-danger"></i>
                        </div>
                    @endif
                @endforeach
                @if($incident->media->count() > 4)
                    <div class="more-media d-flex align-items-center justify-content-center">
                        <span>+{{ $incident->media->count() - 4 }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>
    
    {{-- Footer --}}
    <div class="incident-footer">
        <button class="action-btn like-btn {{ $incident->likes_count > 0 ? 'text-primary' : '' }}" 
                onclick="toggleLike({{ $incident->id }})">
            <i class="far fa-thumbs-up"></i>
            <span>{{ $incident->likes_count }}</span>
        </button>
        
        <button class="action-btn" onclick="expandComments({{ $incident->id }})">
            <i class="far fa-comment"></i>
            <span>{{ $incident->comments_count }}</span>
        </button>
        
        <button class="action-btn" onclick="shareIncident({{ $incident->id }})">
            <i class="far fa-share-square"></i>
            Share
        </button>
        
        @if($incident->assignedTo)
            <div class="ms-auto d-flex align-items-center">
                <small class="text-muted me-2">Assigned to:</small>
                <img src="{{ $incident->assignedTo->avatar_url }}" alt="Assignee" 
                     class="rounded-circle" width="24" height="24"
                     data-bs-toggle="tooltip" title="{{ $incident->assignedTo->name }}">
            </div>
        @endif
    </div>
</div>