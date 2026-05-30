{{-- Header Actions Section --}}

@php
$user = Auth::user();
$canTakeAction = $incident->canTakeAction($user);
$isEscalatedToMe = $incident->status === 'escalated' && $user->id === $incident->escalated_to;
$isAssignedToMe = $incident->assigned_to === $user->id;
$isAdmin = $user->isAdmin();
$isHOD = $user->isHOD() && $user->department_id === $incident->department_id;
$hasFullAccess = $isAdmin || $isHOD || $isAssignedToMe || $isEscalatedToMe;
@endphp

<div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">

    {{-- ========================================== --}}
    {{-- SHARE BUTTON (Always visible) --}}
    {{-- ========================================== --}}
    <button class="btn btn-outline-info btn-sm border-info border-1" onclick="shareIncident()" title="Share"
        data-bs-placement="top">
        <i class="fas fa-share-alt"></i> <span class="d-none d-md-inline ms-1">Share</span>
    </button>

    {{-- ========================================== --}}
    {{-- ESCALATION RESPONSE BUTTONS (Only for escalated user) --}}
    {{-- ========================================== --}}
    @if($isEscalatedToMe)
    <button class="btn btn-outline-success btn-sm border-success border-1" data-bs-toggle="modal"
        data-bs-target="#acceptEscalationModal" title="Accept" data-bs-placement="top">
        <i class="fas fa-check-circle"></i> <span class="d-none d-md-inline ms-1">Accept</span>
    </button>
    <button class="btn btn-outline-warning btn-sm border-warning border-1" data-bs-toggle="modal"
        data-bs-target="#returnEscalationModal" title="Return" data-bs-placement="top">
        <i class="fas fa-undo"></i> <span class="d-none d-md-inline ms-1">Return</span>
    </button>
    <button class="btn btn-outline-danger btn-sm border-danger border-1" data-bs-toggle="modal"
        data-bs-target="#rejectEscalationModal" title="Reject" data-bs-placement="top">
        <i class="fas fa-times-circle"></i> <span class="d-none d-md-inline ms-1">Reject</span>
    </button>
    @endif

    {{-- ========================================== --}}
    {{-- ACTION BUTTONS (For authorized users) --}}
    {{-- ========================================== --}}
    @if($hasFullAccess && !$isEscalatedToMe)

    {{-- Edit --}}
    @can('edit-incident')
    <a href="{{ route('incidents.edit', $incident) }}" class="btn btn-outline-secondary btn-sm" title="Edit"
        data-bs-placement="top">
        <i class="fas fa-edit"></i> <span class="d-none d-md-inline ms-1">Edit</span>
    </a>
    @endcan

    {{-- Reopen (only if resolved/closed) --}}
    @if(in_array($incident->status, ['resolved', 'closed']))
    @can('reopen-incident')
    <button class="btn btn-outline-primary btn-sm" title="Re-open" data-bs-placement="top" onclick="reopenIncident()">
        <i class="fas fa-redo"></i> <span class="d-none d-md-inline ms-1">Reopen</span>
    </button>
    @endcan
    @endif

    {{-- Assign / Reassign --}}
    @can('assign-incident')
    @if(!in_array($incident->status, ['resolved', 'closed']))
    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal"
        title="{{ $incident->assignedTo ? 'Reassign Incident' : 'Assign Incident' }}" data-bs-placement="top">
        <i class="fas fa-user-plus" aria-hidden="true"></i>
        <span class="d-none d-md-inline ms-1">{{ $incident->assignedTo ? 'Reassign' : 'Assign' }}</span>
    </button>

    @endif
    @endcan

    {{-- Reject (only if open/acknowledged and not already rejected) --}}
    @if(in_array($incident->status, ['open', 'acknowledged']))
    @can('close-incident')
    <button class="btn btn-outline-danger btn-sm border-danger border-1" data-bs-toggle="modal"
        data-bs-target="#rejectModal" title="Reject" data-bs-placement="top">

        <i class="fas fa-times-circle"></i> <span class="d-none d-md-inline ms-1">Reject</span>
    </button>
    @endcan
    @endif

    {{-- Escalate (not if already escalated or resolved/closed) --}}
    @can('escalate-incident')
    @if(!in_array($incident->status, ['resolved', 'closed', 'rejected']))
    <button class="btn btn-outline-warning btn-sm border-warning border-1" data-bs-toggle="modal"
        data-bs-target="#escalateModal" title="Escalate" data-bs-placement="top">
        <i class="fas fa-arrow-up"></i> <span class="d-none d-md-inline ms-1">Escalate</span>
    </button>
    @endif
    @endcan

    {{-- Resolve (not if already resolved/closed/rejected) --}}
    @can('resolve-incident')
    @if(!in_array($incident->status, ['resolved', 'closed', 'rejected']))
    <button class="btn btn-outline-success btn-sm border-success border-1" data-bs-toggle="modal"
        data-bs-target="#resolveModal" title="Resolve" data-bs-placement="top">
        <i class="fas fa-check-circle"></i> <span class="d-none d-md-inline ms-1">Resolve</span>
    </button>
    @endif
    @endcan

    {{-- Close (not if already closed/rejected) --}}
    @can('close-incident')
    @if(!in_array($incident->status, ['closed', 'rejected']))
    <button class="btn btn-outline-dark btn-sm border-dark border-1" data-bs-toggle="modal" data-bs-target="#closeModal"
        title="Close" data-bs-placement="top">
        <i class="fas fa-lock"></i> <span class="d-none d-md-inline ms-1">Close</span>
    </button>
    @endif
    @endcan

    @endif

    {{-- ========================================== --}}
    {{-- VIEW ONLY BADGE (For users without action permissions) --}}
    {{-- ========================================== --}}
    @if(!$hasFullAccess && !$isEscalatedToMe)
    <span class="badge bg-info d-flex align-items-center gap-1" style="font-size:0.75rem; padding:8px 12px;">
        <i class="fas fa-eye"></i> View Only
    </span>

    {{-- Still allow reporting user to edit if they reported it --}}
    @if($incident->reported_by === $user->id && in_array($incident->status, ['open', 'acknowledged']))
    @can('edit-incident')
    <a href="{{ route('incidents.edit', $incident) }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-edit"></i> <span class="d-none d-md-inline ms-1">Edit</span>
    </a>
    @endcan
    @endif
    @endif

</div>