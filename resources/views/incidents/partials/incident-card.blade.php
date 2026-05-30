<div class="card shadow-sm mb-2 incident-card-mobile" style="border-radius: 12px; cursor: pointer;"
    onclick="window.location='{{ route('incidents.show', $incident) }}'">
    <div class="card-body p-3">
        {{-- Header --}}
        <div class="d-flex align-items-start gap-2 mb-2">
            @if(!$incident->is_anonymous && $incident->reporter)
            <img src="{{ $incident->reporter->avatar_url }}" alt="Reporter" class="rounded-circle flex-shrink-0"
                width="36" height="36" style="object-fit: cover;">
            @else
            <div class="rounded-circle bg-secondary flex-shrink-0 d-flex align-items-center justify-content-center"
                style="width: 36px; height: 36px;">
                <i class="fas fa-user-secret text-white" style="font-size: 0.75rem;"></i>
            </div>
            @endif
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold" style="font-size: 0.8125rem;">
                            {{ $incident->is_anonymous ? 'Anonymous' : ($incident->reporter?->name ?? 'Unknown') }}
                        </div>
                        <small class="text-muted" style="font-size: 0.6875rem;">
                            {{ $incident->created_at->diffForHumans() }}
                            @if($incident->location)
                            <span class="ms-1"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($incident->location,
                                20) }}</span>
                            @endif
                        </small>
                    </div>
                    <span class="badge status-{{ str_replace('_', '-', $incident->status) }}"
                        style="font-size: 0.6rem;">
                        {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <h6 class="mb-1" style="font-size: 0.875rem;">
            <span class="text-muted small">#{{ $incident->incident_id }}</span>
            {{ Str::limit($incident->title, 60) }}
        </h6>

        {{-- Optimized Your Role Section --}}
        @if(in_array($activeTab, ['history', 'reported']))
        @php
        $roles = [];
        if ($incident->reported_by === $user->id) $roles[] = ['icon' => '📝', 'label' => 'Reporter'];
        if ($incident->assigned_to === $user->id) $roles[] = ['icon' => '👤', 'label' => 'Assigned'];
        if ($incident->escalated_to === $user->id) $roles[] = ['icon' => '⬆️', 'label' => 'Escalated'];
        if ($incident->escalations()->where('escalated_by', $user->id)->exists()) $roles[] = ['icon' => '📤', 'label' =>
        'Escalated Out'];
        if ($incident->assignments()->where('assigned_by', $user->id)->exists()) $roles[] = ['icon' => '📋', 'label' =>
        'Assigned Out'];
        if ($incident->comments()->where('user_id', $user->id)->exists()) $roles[] = ['icon' => '💬', 'label' =>
        'Commented'];
        @endphp

        @if(!empty($roles))
        <div class="your-role d-flex flex-wrap gap-1 mb-2">
            @foreach($roles as $role)
            <span class="badge bg-light text-dark fw-normal border" style="font-size: 0.65rem; padding: 3px 6px;">
                {{ $role['icon'] }} {{ $role['label'] }}
            </span>
            @endforeach
        </div>
        @endif
        @endif

        {{-- Description --}}
        <p class="text-muted mb-2"
            style="font-size: 0.75rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
            {{ $incident->description }}
        </p>

        {{-- Badges Row --}}
        <div class="d-flex flex-wrap align-items-center gap-1">
            <span class="badge priority-{{ $incident->priority }}" style="font-size: 0.6rem;">
                {{ ucfirst($incident->priority) }}
            </span>
            @if($incident->category)
            <span class="badge"
                style="background: {{ $incident->category->color }}15; color: {{ $incident->category->color }}; font-size: 0.6rem;">
                {{ $incident->category->name }}
            </span>
            @endif
            @if($incident->department)
            <span class="badge"
                style="background: {{ $incident->department->color }}15; color: {{ $incident->department->color }}; font-size: 0.6rem;">
                {{ $incident->department->code }}
            </span>
            @endif
            @if($incident->is_overdue)
            <span class="badge bg-danger" style="font-size: 0.6rem;">Overdue</span>
            @endif
            @if($incident->assignedTo)
            <span class="badge bg-light text-dark ms-auto" style="font-size: 0.6rem;"
                onclick="event.stopPropagation();">
                <i class="fas fa-user me-1"></i>{{ $incident->assignedTo->name }}
            </span>
            @endif
        </div>

        {{-- Media Thumbnails --}}
        @if($incident->media->count() > 0)
        <div class="d-flex gap-1 mt-2" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            @foreach($incident->media->take(3) as $media)
            @if($media->isImage())
            <img src="{{ $media->url }}" alt="Media"
                style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0;"
                onclick="event.stopPropagation(); window.open('{{ $media->url }}', '_blank')">
            @endif
            @endforeach
            @if($incident->media->count() > 3)
            <div class="d-flex align-items-center justify-content-center bg-light rounded"
                style="width: 60px; height: 60px; flex-shrink: 0; font-size: 0.75rem; color: #6b7280; border: 1px solid #e5e7eb;">
                +{{ $incident->media->count() - 3 }}
            </div>
            @endif
        </div>
        @endif
    </div>
</div>