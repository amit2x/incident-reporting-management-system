{{-- resources/views/incidents/show.blade.php --}}
@extends('layouts.app')

@section('title', $incident->incident_id . ' - ' . $incident->title)

@push('styles')
<style>
    .timeline-item {
        position: relative;
        padding-left: 32px;
        margin-bottom: 16px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 28px;
        bottom: -16px;
        width: 2px;
        background: #e5e7eb;
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-dot {
        position: absolute;
        left: 0;
        top: 2px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .comment-item {
        transition: background 0.2s;
    }

    .comment-item:hover {
        background: #f9fafb;
    }

    .media-thumb {
        cursor: pointer;
        transition: transform 0.2s;
        border-radius: 8px;
    }

    .media-thumb:hover {
        transform: scale(1.02);
    }

    .comment-attachment {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: #f3f4f6;
        border-radius: 8px;
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    {{-- Header with Actions --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
        <div>
            <a href="{{ route('incidents.index') }}" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Incidents
            </a>
            <h4 class="fw-bold mt-1 mb-0">
                <span class="badge bg-light text-dark me-2">#{{ $incident->incident_id }}</span>
                {{ $incident->title }}
            </h4>
        </div>

        {{-- Header Actions Section --}}
        {{-- Header Actions Section --}}

        @php
        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        $isSuperAdmin = $user->isSuperAdmin();
        $isHOD = $user->isHOD() && $user->department_id === $incident->department_id;
        $isSupervisor = $user->isSupervisor() && $user->department_id === $incident->department_id;
        $isReporter = $user->id === $incident->reported_by;
        $isAssignedToMe = $incident->assigned_to === $user->id;
        $isEscalatedToMe = $incident->status === 'escalated' && $user->id === $incident->escalated_to;

        // Permission hierarchy
        $canEdit = $isReporter || $isAdmin || $isHOD; // Only reporter, admin, HOD can edit
        $canAssign = $isAdmin || $isHOD || $isSupervisor; // Admin, HOD, Supervisor can assign
        $canEscalate = $isAssignedToMe || $isAdmin || $isHOD || $isSupervisor; // Assigned person or higher
        $canResolve = $isAssignedToMe || $isAdmin || $isHOD; // Only assigned person can resolve
        $canClose = $isAdmin || $isHOD || ($isAssignedToMe && $incident->status === 'resolved'); // Only if resolved
        $canReopen = $isReporter || $isAdmin || $isHOD; // Reporter or higher can reopen
        $canReject = $isAdmin || $isHOD; // Only admin/HOD can reject
        $canDelete = $isAdmin || $isSuperAdmin; // Only admin/super admin can delete

        $hasFullAccess = $isAdmin || $isHOD || $isAssignedToMe || $isEscalatedToMe;
        $isViewOnly = !$hasFullAccess && !$isEscalatedToMe;
        @endphp

        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">

            {{-- ========================================== --}}
            {{-- SHARE BUTTON (Always visible to everyone) --}}
            {{-- ========================================== --}}
            <button class="btn btn-outline-info btn-sm border-info border-1" onclick="shareIncident()"
                title="Share this incident">
                <i class="fas fa-share-alt"></i> <span class="d-none d-md-inline ms-1">Share</span>
            </button>

            {{-- ========================================== --}}
            {{-- ESCALATION RESPONSE BUTTONS (Only for escalated user) --}}
            {{-- ========================================== --}}
            @if($isEscalatedToMe)
            <div class="d-flex gap-1 p-1 bg-warning bg-opacity-10 rounded-2 border border-warning">
                <span class="badge bg-warning text-dark d-flex align-items-center me-1" style="font-size:0.6rem;">
                    <i class="fas fa-exclamation-triangle me-1"></i>Action Required
                </span>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#acceptEscalationModal"
                    title="Accept responsibility">
                    <i class="fas fa-check-circle"></i> Accept
                </button>
                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal"
                    data-bs-target="#returnEscalationModal" title="Return to previous level">
                    <i class="fas fa-undo"></i> Return
                </button>
                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                    data-bs-target="#rejectEscalationModal" title="Reject escalation">
                    <i class="fas fa-times-circle"></i> Reject
                </button>
            </div>
            @endif

            {{-- ========================================== --}}
            {{-- EDIT - Only Reporter, Admin, HOD --}}
            {{-- ========================================== --}}
            @if($canEdit && !in_array($incident->status, ['resolved', 'closed', 'rejected']))
            @can('edit-incident')
            <a href="{{ route('incidents.edit', $incident) }}" class="btn btn-outline-secondary btn-sm"
                title="{{ $isReporter ? 'Edit your incident' : 'Edit incident' }}">
                <i class="fas fa-edit"></i> <span class="d-none d-md-inline ms-1">Edit</span>
            </a>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- REOPEN - Reporter, Admin, HOD (only if resolved/closed) --}}
            {{-- ========================================== --}}
            @if($canReopen && in_array($incident->status, ['resolved', 'closed']))
            @can('reopen-incident')
            <button class="btn btn-outline-primary btn-sm" onclick="reopenIncident()"
                title="{{ $isReporter ? 'Reopen your incident' : 'Reopen incident' }}">
                <i class="fas fa-redo"></i> <span class="d-none d-md-inline ms-1">Reopen</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- ASSIGN / REASSIGN - Admin, HOD, Supervisor --}}
            {{-- ========================================== --}}
            @if($canAssign && !in_array($incident->status, ['resolved', 'closed']))
            @can('assign-incident')
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal"
                title="{{ $incident->assignedTo ? 'Reassign to another person' : 'Assign to someone' }}">
                <i class="fas fa-user-plus"></i>
                <span class="d-none d-md-inline ms-1">{{ $incident->assignedTo ? 'Reassign' : 'Assign' }}</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- REJECT - Only Admin or HOD (open/acknowledged only) --}}
            {{-- ========================================== --}}
            @if($canReject && in_array($incident->status, ['open', 'acknowledged']))
            @can('close-incident')
            <button class="btn btn-outline-danger btn-sm border-danger border-1" data-bs-toggle="modal"
                data-bs-target="#rejectModal" title="Reject this incident">
                <i class="fas fa-times-circle"></i> <span class="d-none d-md-inline ms-1">Reject</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- ESCALATE - Assigned person, Admin, HOD, Supervisor --}}
            {{-- ========================================== --}}
            @if($canEscalate && !in_array($incident->status, ['resolved', 'closed', 'rejected', 'escalated']))
            @can('escalate-incident')
            <button class="btn btn-outline-warning btn-sm border-warning border-1" data-bs-toggle="modal"
                data-bs-target="#escalateModal" title="Escalate to higher authority">
                <i class="fas fa-arrow-up"></i> <span class="d-none d-md-inline ms-1">Escalate</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- RESOLVE - Only assigned person, Admin, HOD --}}
            {{-- ========================================== --}}
            @if($canResolve && !in_array($incident->status, ['resolved', 'closed', 'rejected']))
            @can('resolve-incident')
            <button class="btn btn-outline-success btn-sm border-success border-1" data-bs-toggle="modal"
                data-bs-target="#resolveModal"
                title="{{ $isAssignedToMe ? 'Resolve this incident' : 'Resolve incident as ' . $user->getFirstRoleName() }}">
                <i class="fas fa-check-circle"></i> <span class="d-none d-md-inline ms-1">Resolve</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- CLOSE - Admin, HOD, or assigned person (only if resolved) --}}
            {{-- ========================================== --}}
            @if($canClose && !in_array($incident->status, ['closed', 'rejected']))
            @can('close-incident')
            <button class="btn btn-outline-dark btn-sm border-dark border-1" data-bs-toggle="modal"
                data-bs-target="#closeModal"
                title="{{ $incident->status === 'resolved' ? 'Close this resolved incident' : 'Close incident' }}">
                <i class="fas fa-lock"></i> <span class="d-none d-md-inline ms-1">Close</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- DELETE - Only Admin/Super Admin (with caution) --}}
            {{-- ========================================== --}}
            @if($canDelete)
            @can('delete-incident')
            <button class="btn btn-outline-danger btn-sm border-danger border-1" onclick="confirmDeleteIncident()"
                title="⚠️ Permanent delete">
                <i class="fas fa-trash"></i> <span class="d-none d-md-inline ms-1">Delete</span>
            </button>
            @endcan
            @endif

            {{-- ========================================== --}}
            {{-- VIEW ONLY BADGE --}}
            {{-- ========================================== --}}
            @if($isViewOnly)
            <span class="badge bg-light text-dark border d-flex align-items-center gap-1"
                style="font-size:0.75rem; padding:6px 12px;">
                <i class="fas fa-eye text-muted"></i> Read Only
            </span>
            @endif

        </div>

        {{-- Permission Info Tooltip (shows who can do what) --}}
        @if($isViewOnly)
        <div class="mt-1">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Actions are restricted to:
                <strong>Assigned User</strong>, <strong>Department HOD</strong>, <strong>Supervisor</strong>, or
                <strong>Admin</strong>.
                @if($isReporter)
                <span class="text-primary">You can edit this incident as the reporter.</span>
                @endif
            </small>
        </div>
        @endif


        {{--
        ## Permission Matrix Summary

        | Action | Reporter | Assigned User | Supervisor | HOD | Admin |
        |-------- |----------|---------------|------------|-----|-------|
        | **Edit | ✅ (own | ❌ | ❌ | ✅ | ✅ |
        | Assign | ❌ | ❌ | ✅ | ✅ | ✅ |
        | Escalate | ❌ | ✅ | ✅ | ✅ | ✅ |
        | Resolve | ❌ | ✅ (assigned) | ❌ | ✅ | ✅ |
        | **Close* | ❌ | ✅ (if resolved) | ❌ | ✅ | ✅ |
        | **Reopen | ✅ (own only) | ❌ | ❌ | ✅ | ✅ |
        | **Reject | ❌ | ❌ | ❌ | ✅ | ✅ |
        | **Delete | ❌ | ❌ | ❌ | ❌ | ✅ |
        | **Share* | ✅ | ✅ | ✅ | ✅ | ✅ |

        This ensures:
        - ✅ **Reporter can only edit** their own incident while it's still open
        - ✅ **Assigned person can escalate, resolve, and close** (if resolved)
        - ✅ **Supervisor can assign and escalate** within their department
        - ✅ **HOD has full control** over their department incidents
        - ✅ **Admin has complete access** including delete
        - ✅ **View-only users** see a clear "Read Only" badge with explanation
        - ✅ **Escalated users** get highlighted action buttons with warning badge
        - ✅ **Delete** has double confirmation to prevent accidents

        --}} {{-- ========================================== --}}
        {{-- STATUS INDICATOR (Shows who can act) --}}
        {{-- ========================================== --}}
        <div class="mt-2">
            @if($isEscalatedToMe)
            <div class="alert alert-warning py-2 px-3 mb-0 d-flex align-items-center gap-2"
                style="font-size:0.8125rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Action Required:</strong> This incident has been escalated to you. Please accept, return, or
                reject.
            </div>
            @elseif($isAssignedToMe && in_array($incident->status, ['open', 'acknowledged', 'in_progress']))
            <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size:0.8125rem;">
                <i class="fas fa-info-circle"></i>
                <strong>Assigned to You:</strong> You are responsible for resolving this incident.
            </div>
            @elseif(!$hasFullAccess && !$isEscalatedToMe)
            <div class="alert alert-light py-2 px-3 mb-0 d-flex align-items-center gap-2 border"
                style="font-size:0.75rem;">
                <i class="fas fa-lock text-muted"></i>
                <span class="text-muted">You have read-only access to this incident. Actions are restricted to assigned
                    users, escalated users, HODs, and admins.</span>
            </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Status Badges --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge status-{{ str_replace('_', '-', $incident->status) }}">{{ str_replace('_', ' ',
                    ucfirst($incident->status)) }}</span>
                <span class="badge priority-{{ $incident->priority }}"><i class="fas fa-flag me-1"></i>{{
                    ucfirst($incident->priority) }}</span>
                <span class="badge"
                    style="background:{{ $incident->severity_color }}15;color:{{ $incident->severity_color }}"><i
                        class="fas fa-exclamation-circle me-1"></i>{{ ucfirst($incident->severity) }}</span>
                @if($incident->is_overdue)<span class="badge bg-danger"><i
                        class="fas fa-clock me-1"></i>Overdue</span>@endif
            </div>

            {{-- Description --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white"><strong><i
                            class="fas fa-align-left text-primary me-2"></i>Description</strong></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $incident->description }}</p>
                    @if($incident->tags)
                    <div class="mt-3">@foreach($incident->tags as $tag)<span class="badge bg-light text-dark me-1">#{{
                            $tag }}</span>@endforeach</div>
                    @endif
                </div>
            </div>

            {{-- Details Card --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-info-circle text-info me-2"></i>Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Basic Info - Each item takes full width on mobile, half on desktop --}}
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Category</small>
                                <span class="fw-medium text-break">{{ $incident->category?->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Department</small>
                                <span class="fw-medium text-break">{{ $incident->department?->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Reported By</small>
                                <span class="fw-medium text-break">
                                    @if($incident->is_anonymous)
                                    <i class="fas fa-user-secret me-1"></i>Anonymous
                                    @else
                                    {{ $incident->reporter?->name ?? 'N/A' }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Assigned To</small>
                                <span class="fw-medium text-break">
                                    @if($incident->assignedTo)
                                    <img src="{{ $incident->assignedTo->avatar_url }}" class="rounded-circle me-1"
                                        width="20" height="20">
                                    {{ $incident->assignedTo->name }}
                                    @else
                                    <span class="text-warning">Unassigned</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Location</small>
                                <span class="fw-medium text-break">{{ $incident->location ?? 'Not specified' }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Reported On</small>
                                <span class="fw-medium">{{ $incident->created_at->format('d M Y, H:i') }}</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">SLA Due</small>
                                <span class="fw-medium {{ $incident->is_overdue ? 'text-danger' : '' }}">
                                    {{ $incident->sla_due_at?->format('d M Y, H:i') ?? 'N/A' }}
                                    @if($incident->is_overdue)
                                    <span class="badge bg-danger ms-1" style="font-size:0.6rem;">Overdue</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                <small class="text-muted flex-shrink-0" style="min-width:80px;">Last Updated</small>
                                <span class="fw-medium">{{ $incident->updated_at->format('d M Y, H:i') }}</span>
                            </div>
                        </div>

                        {{-- Resolution Notes - Full width always --}}
                        @if($incident->resolution_notes)
                        <div class="col-12 border-top pt-3 mt-1">
                            <small class="text-muted d-block mb-2 fw-semibold">
                                <i class="fas fa-check-circle text-success me-1"></i>Resolution Notes
                            </small>
                            <div class="bg-light rounded p-3">
                                <span class="fw-medium" style="white-space: pre-wrap;">{{ $incident->resolution_notes
                                    }}</span>

                                {{-- Resolution Attachments --}}
                                @php
                                $resolutionMedia = $incident->media()
                                ->whereBetween('created_at', [
                                $incident->resolved_at?->copy()->subMinutes(5) ?? now()->subHour(),
                                $incident->resolved_at?->copy()->addMinutes(5) ?? now()
                                ])
                                ->get();
                                @endphp
                                @if($resolutionMedia->count() > 0)
                                <div class="mt-3">
                                    <small class="text-muted d-block mb-2">📎 Resolution Attachments</small>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($resolutionMedia as $media)
                                        @if($media->isImage())
                                        <a href="{{ $media->url }}" target="_blank" class="d-inline-block">
                                            <img src="{{ $media->url }}"
                                                style="max-width:100%;height:auto;max-height:120px;object-fit:cover;border-radius:8px;cursor:pointer;"
                                                class="border shadow-sm">
                                        </a>
                                        @else
                                        <a href="{{ $media->url }}" target="_blank"
                                            class="badge bg-white border text-dark text-decoration-none d-inline-flex align-items-center gap-1"
                                            style="padding:8px 12px;font-size:0.75rem;">
                                            <i class="fas fa-file text-muted"></i>
                                            {{ Str::limit($media->original_name, 25) }}
                                            <small class="text-muted">({{ round($media->file_size/1024, 1) }}KB)</small>
                                        </a>
                                        @endif
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Rejection Reason - Full width always --}}
                        @if($incident->rejection_reason)
                        <div class="col-12 border-top pt-3 mt-1">
                            <small class="text-muted d-block mb-2 fw-semibold">
                                <i class="fas fa-times-circle text-danger me-1"></i>Rejection Reason
                            </small>
                            <div class="bg-danger bg-opacity-10 rounded p-3 border border-danger border-opacity-25">
                                <span class="fw-medium text-danger">{{ $incident->rejection_reason }}</span>
                            </div>
                        </div>
                        @endif

                        {{-- Closing Information - Full width always --}}
                        @if($incident->status === 'closed')
                        @php
                        $closingComment = $incident->comments()
                        ->where('content', 'like', '🔒%')
                        ->latest()
                        ->first();
                        $closingMedia = $incident->media()
                        ->whereBetween('created_at', [
                        $incident->closed_at?->copy()->subMinutes(5) ?? now()->subHour(),
                        $incident->closed_at?->copy()->addMinutes(5) ?? now()
                        ])
                        ->get();
                        @endphp
                        @if($closingComment || $closingMedia->count() > 0)
                        <div class="col-12 border-top pt-3 mt-1">
                            <small class="text-muted d-block mb-2 fw-semibold">
                                <i class="fas fa-lock text-dark me-1"></i>Closing Information
                            </small>
                            <div class="bg-light rounded p-3">
                                @if($closingComment)
                                <div class="small mb-2">
                                    {{ trim(str_replace('🔒 **Incident Closed**', '', $closingComment->content)) }}
                                </div>
                                @endif
                                @if($closingMedia->count() > 0)
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach($closingMedia as $media)
                                    @if($media->isImage())
                                    <a href="{{ $media->url }}" target="_blank">
                                        <img src="{{ $media->url }}"
                                            style="max-width:100%;height:auto;max-height:120px;object-fit:cover;border-radius:8px;"
                                            class="border shadow-sm">
                                    </a>
                                    @else
                                    <a href="{{ $media->url }}" target="_blank"
                                        class="badge bg-white border text-dark text-decoration-none d-inline-flex align-items-center gap-1"
                                        style="padding:8px 12px;">
                                        <i class="fas fa-file"></i> {{ Str::limit($media->original_name, 25) }}
                                    </a>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>


            {{-- Media --}}
            @if($incident->media->count() > 0)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white"><strong><i class="fas fa-paperclip text-success me-2"></i>Attachments
                        ({{ $incident->media->count() }})</strong></div>
                <div class="card-body">
                    <div class="row g-2">@foreach($incident->media as $media)<div class="col-4 col-md-3 col-lg-2">
                            @if($media->isImage())<img src="{{ $media->url }}" class="img-fluid rounded media-thumb"
                                style="width:100%;height:100px;object-fit:cover;"
                                onclick="window.open('{{ $media->url }}','_blank')">@else<a href="{{ $media->url }}"
                                target="_blank" class="text-decoration-none">
                                <div class="border rounded p-2 text-center" style="height:100px;"><i
                                        class="fas fa-file fa-2x text-muted mt-2"></i><small
                                        class="d-block text-truncate mt-1">{{ $media->original_name }}</small></div>
                            </a>@endif</div>@endforeach</div>
                </div>
            </div>
            @endif

            {{-- Comments --}}
            <div class="card shadow-sm" id="commentsSection">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-comments text-primary me-2"></i>Comments (<span id="commentCount">{{
                            $incident->comments_count ?? 0 }}</span>)</strong>
                </div>
                <div class="card-body">
                    {{-- Comment Form --}}
                    <form id="commentForm" class="mb-4">
                        @csrf
                        <div class="d-flex gap-2 mb-2">
                            <img src="{{ Auth::user()->avatar_url }}" class="rounded-circle flex-shrink-0" width="36"
                                height="36" style="object-fit:cover;">
                            <div class="flex-grow-1">
                                <textarea id="commentContent" class="form-control" rows="2"
                                    placeholder="Write a comment... Use @username to mention, #tag for tags"></textarea>
                                <div id="commentFilePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-light btn-sm attach-comment-btn"
                                            id="attachCommentBtn" title="Attach files">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <small class="text-muted d-none d-md-inline">@ to mention | # for tags</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" id="commentSubmitBtn">
                                        <i class="fas fa-paper-plane me-1"></i> Post
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Comments List --}}
                    <div id="commentsList">
                        @forelse($incident->comments ?? [] as $comment)
                        @include('incidents.partials.comment-item', ['comment' => $comment])
                        @empty
                        <div id="noComments" class="text-center py-4 text-muted">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p class="mb-0">No comments yet. Be the first to comment!</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Timeline --}}
            @include('incidents.partials.timeline')

            {{-- Escalation History --}}
            @include('incidents.partials.escalation-history')

            {{-- Assignment History --}}
            @if($incident->assignmentHistory()->count() > 1)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-exchange-alt text-primary me-2"></i>Assignment History</strong>
                    <small class="text-muted ms-auto">{{ $incident->assignmentHistory()->count() }} assignments</small>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($incident->assignmentHistory as $assignment)
                        <div class="list-group-item small">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <img src="{{ $assignment->assignedTo?->avatar_url ?? '/images/default-avatar.png' }}"
                                            class="rounded-circle" width="20" height="20">
                                        <strong>{{ $assignment->assignedTo?->name ?? 'N/A' }}</strong>
                                    </div>
                                    @if($assignment->notes)
                                    <small class="text-muted d-block">{{ Str::limit($assignment->notes, 60) }}</small>
                                    @endif
                                    <small class="text-muted">By: {{ $assignment->assignedBy?->name ?? 'System'
                                        }}</small>
                                </div>
                                <small class="text-muted flex-shrink-0">{{ $assignment->created_at->format('d M H:i')
                                    }}</small>
                            </div>
                            @if($assignment->unassigned_at)
                            <small class="text-danger">Unassigned: {{ $assignment->unassigned_at->format('d M H:i')
                                }}</small>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Quick Info --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong><i class="fas fa-info-circle me-2"></i>Quick Info</strong>
                </div>
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-6"><strong>Response:</strong><br><span class="text-muted">{{
                                $incident->response_time ?? 'N/A' }}</span></div>
                        <div class="col-6"><strong>Resolution:</strong><br><span class="text-muted">{{
                                $incident->resolution_time ?? 'N/A' }}</span></div>
                        <div class="col-6"><strong>SLA Breaches:</strong><br><span
                                class="{{ $incident->sla_breach_count > 0 ? 'text-danger' : 'text-muted' }}">{{
                                $incident->sla_breach_count }}</span></div>
                        <div class="col-6"><strong>Views:</strong><br><span class="text-muted">{{ $incident->views_count
                                }}</span></div>
                        <div class="col-6"><strong>Escalations:</strong><br><span
                                class="{{ $incident->escalations->count() > 0 ? 'text-warning' : 'text-muted' }}">{{
                                $incident->escalations->count() }}</span></div>
                        <div class="col-6"><strong>Reassignments:</strong><br><span class="text-muted">{{ max(0,
                                $incident->assignmentHistory()->count() - 1) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals: Assign, Reject, Escalate, Resolve --}}
@include('incidents.partials.modals')

@endsection
{{-- Java Scripts --}}
@include('incidents.partials.core_show_script')