<div class="feed-card bg-white rounded-4 shadow-sm mb-3 overflow-hidden">

    {{-- Card Header --}}
    <div class="p-3">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ $incident->reporter?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&size=44&background=random' }}"
                 alt="{{ $incident->reporter?->name ?? 'User' }}"
                 class="rounded-circle flex-shrink-0"
                 width="44" height="44"
                 style="object-fit: cover; border: 2px solid #f3f4f6;"
                 loading="lazy">

            <div class="flex-grow-1 min-width-0">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                        {{ $incident->reporter?->name ?? 'Unknown' }}
                    </span>
                    @if($incident->department)
                        <span class="badge" style="background: {{ $incident->department->color }}15; color: {{ $incident->department->color }}; font-size: 0.625rem;">
                            {{ $incident->department->code }}
                        </span>
                    @endif
                </div>
                <small class="text-muted" style="font-size: 0.6875rem;">
                    {{ $incident->created_at->diffForHumans() }}
                    @if($incident->location)
                        <span class="ms-2"><i class="fas fa-map-marker-alt me-1"></i>{{ $incident->location }}</span>
                    @endif
                </small>
            </div>

            <span class="badge status-{{ str_replace('_', '-', $incident->status) }} flex-shrink-0"
                  style="font-size: 0.625rem; padding: 5px 10px;">
                {{ str_replace('_', ' ', ucfirst($incident->status)) }}
            </span>
        </div>
    </div>

    {{-- Card Body --}}
    <div class="px-3">
        <div class="d-flex gap-2 mb-2 flex-wrap">
            <span class="badge bg-light text-dark" style="font-size: 0.625rem;">#{{ $incident->incident_id }}</span>
            <span class="badge priority-{{ $incident->priority }}" style="font-size: 0.625rem;">
                {{ ucfirst($incident->priority) }}
            </span>
            @if($incident->category)
                <span class="badge" style="background: {{ $incident->category->color }}15; color: {{ $incident->category->color }}; font-size: 0.625rem;">
                    {{ $incident->category->name }}
                </span>
            @endif
            @if($incident->is_overdue)
                <span class="badge bg-danger" style="font-size: 0.625rem;">Overdue</span>
            @endif
        </div>

        <a href="{{ auth()->check() ? route('incidents.show', $incident) : route('incident.public', $incident) }}"
           class="text-decoration-none">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.9375rem;">{{ $incident->title }}</h6>
        </a>
        <p class="text-muted mb-3" style="font-size: 0.8125rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
            {{ $incident->description }}
        </p>

        {{-- Media --}}
        @if($incident->media->count() > 0)
            <div class="mb-3">
                <div class="row g-1" style="border-radius: 12px; overflow: hidden;">
                    @foreach($incident->media->take(3) as $media)
                        <div class="col-{{ $incident->media->count() === 1 ? '12' : ($incident->media->count() === 2 ? '6' : '4') }}">
                            @if($media->isImage())
                                <img src="{{ $media->url }}" alt="Image"
                                     style="width: 100%; height: {{ $incident->media->count() === 1 ? '300px' : '180px' }}; object-fit: cover; cursor: pointer;"
                                     loading="lazy"
                                     onclick="window.open('{{ $media->url }}', '_blank')">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center"
                                     style="width: 100%; height: {{ $incident->media->count() === 1 ? '300px' : '180px' }};">
                                    <i class="fas fa-file fa-2x text-muted"></i>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($incident->media->count() > 3)
                    <small class="text-muted">+{{ $incident->media->count() - 3 }} more</small>
                @endif
            </div>
        @endif
    </div>

    {{-- Card Footer --}}
    <div class="border-top px-3 py-2 d-flex align-items-center gap-3">
        <button class="btn btn-light btn-sm rounded-pill border-0 d-flex align-items-center gap-1"
                onclick="handleLike({{ $incident->id }}, this)">
            <i class="far fa-thumbs-up"></i>
            <span style="font-size: 0.75rem;">{{ $incident->likes_count ?? 0 }}</span>
        </button>

        <a href="{{ auth()->check() ? route('incidents.show', $incident) . '#comments' : route('incident.public', $incident) . '#comments' }}"
           class="btn btn-light btn-sm rounded-pill border-0 d-flex align-items-center gap-1 text-decoration-none">
            <i class="far fa-comment"></i>
            <span style="font-size: 0.75rem;">{{ $incident->comments_count ?? 0 }}</span>
        </a>

        <a href="{{ auth()->check() ? route('incidents.show', $incident) : route('incident.public', $incident) }}"
           class="btn btn-light btn-sm rounded-pill border-0 d-flex align-items-center gap-1 text-decoration-none ms-auto">
            <i class="fas fa-arrow-right"></i>
            <span style="font-size: 0.75rem;" class="d-none d-sm-inline">View</span>
        </a>
    </div>
</div>
