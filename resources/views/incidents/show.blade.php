{{-- resources/views/incidents/show.blade.php --}}
@extends('layouts.app')

@section('title', $incident->incident_id . ' - ' . $incident->title)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('incidents.index') }}">Incidents</a></li>
    <li class="breadcrumb-item active">{{ $incident->incident_id }}</li>
@endsection

@section('content')
<div class="page-enter">
    <div class="row g-3">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Incident Details Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark">#{{ $incident->incident_id }}</span>
                        <span class="badge status-{{ str_replace('_', '-', $incident->status) }}">
                            {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                        </span>
                        <span class="badge priority-{{ $incident->priority }}">
                            {{ ucfirst($incident->priority) }}
                        </span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('edit-incident')
                            <a href="{{ route('incidents.edit', $incident) }}" class="dropdown-item">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                            @endcan
                            @can('assign-incident')
                            <button class="dropdown-item" onclick="openAssignModal()">
                                <i class="fas fa-user-plus me-2"></i>Assign
                            </button>
                            @endcan
                            @can('escalate-incident')
                            <button class="dropdown-item" onclick="openEscalateModal()">
                                <i class="fas fa-arrow-up me-2"></i>Escalate
                            </button>
                            @endcan
                            @can('resolve-incident')
                            <button class="dropdown-item" onclick="openResolveModal()">
                                <i class="fas fa-check-circle me-2"></i>Resolve
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h4 class="mb-3">{{ $incident->title }}</h4>
                    <p class="text-muted mb-4">{{ $incident->description }}</p>

                    {{-- Details Grid --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Category</small>
                            <span class="badge" style="background: {{ $incident->category?->color ?? '#6B7280' }}20; color: {{ $incident->category?->color ?? '#6B7280' }}">
                                <i class="{{ $incident->category?->icon ?? 'fas fa-tag' }} me-1"></i>
                                {{ $incident->category?->name ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Department</small>
                            <span class="badge" style="background: {{ $incident->department?->color ?? '#6B7280' }}20; color: {{ $incident->department?->color ?? '#6B7280' }}">
                                {{ $incident->department?->name ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Reported By</small>
                            <span>{{ $incident->is_anonymous ? 'Anonymous' : ($incident->reporter?->name ?? 'N/A') }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Assigned To</small>
                            <span>{{ $incident->assignedTo?->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Location</small>
                            <span>{{ $incident->location ?? 'Not specified' }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">SLA Due</small>
                            <span class="{{ $incident->is_overdue ? 'text-danger' : '' }}">
                                {{ $incident->sla_due_at?->format('d M Y, H:i') ?? 'N/A' }}
                                @if($incident->is_overdue)
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comments Section --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-comments me-2"></i>Comments
                        ({{ $incident->comments_count ?? 0 }})
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Comment Form --}}
                    <form action="{{ route('incidents.comments.store', $incident) }}" method="POST" class="mb-4">
                        @csrf
                        <textarea name="content" class="form-control mb-2" rows="2"
                                  placeholder="Add a comment..."></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i>Post Comment
                        </button>
                    </form>

                    {{-- Comments List --}}
                    @forelse($incident->comments ?? [] as $comment)
                        <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                            <img src="{{ $comment->user?->avatar_url ?? asset('images/default-avatar.png') }}"
                                 class="rounded-circle" width="36" height="36">
                            <div>
                                <div class="d-flex gap-2 align-items-center mb-1">
                                    <strong style="font-size: 0.8125rem;">{{ $comment->user?->name ?? 'Unknown' }}</strong>
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-0" style="font-size: 0.8125rem;">{{ $comment->content }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-3">No comments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Timeline --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Timeline</h6>
                </div>
                <div class="card-body">
                    @foreach($incident->timeline as $event)
                        <div class="d-flex gap-3 mb-3">
                            <div class="text-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 28px; height: 28px; background: {{ $event['color'] }}20; color: {{ $event['color'] }};">
                                    <i class="fas {{ $event['icon'] }}" style="font-size: 0.75rem;"></i>
                                </div>
                                @if(!$loop->last)
                                    <div style="width: 2px; height: 20px; background: #e5e7eb; margin: 0 auto;"></div>
                                @endif
                            </div>
                            <div>
                                <div class="fw-medium" style="font-size: 0.8125rem;">{{ $event['action'] }}</div>
                                <small class="text-muted">{{ $event['timestamp'] }}</small>
                                @if($event['user_name'])
                                    <div><small>{{ $event['user_name'] }}</small></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Media --}}
            @if($incident->media->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-images me-2"></i>Attachments</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($incident->media as $media)
                            <div class="col-6">
                                @if($media->isImage())
                                    <img src="{{ $media->url }}" class="img-fluid rounded" style="width:100%;height:120px;object-fit:cover;">
                                @else
                                    <a href="{{ $media->url }}" class="btn btn-light w-100 py-3" target="_blank">
                                        <i class="fas fa-file fa-2x text-muted"></i>
                                        <small class="d-block mt-1">{{ $media->original_name }}</small>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
