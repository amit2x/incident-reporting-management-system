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
    }
    .media-thumb:hover {
        transform: scale(1.02);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
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
        <div class="d-flex gap-2 mt-2 mt-md-0">
            @can('edit-incident')
                <a href="{{ route('incidents.edit', $incident) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endcan
            @can('assign-incident')
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="fas fa-user-plus"></i> Assign
                </button>
            @endcan
            @can('escalate-incident')
                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#escalateModal">
                    <i class="fas fa-arrow-up"></i> Escalate
                </button>
            @endcan
            @can('resolve-incident')
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#resolveModal">
                    <i class="fas fa-check-circle"></i> Resolve
                </button>
            @endcan
            @can('close-incident')
                <button class="btn btn-outline-dark btn-sm" onclick="closeIncident()">
                    <i class="fas fa-lock"></i> Close
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Status Badges --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge status-{{ str_replace('_', '-', $incident->status) }} fs-6">
                    {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                </span>
                <span class="badge priority-{{ $incident->priority }}">
                    <i class="fas fa-flag me-1"></i> {{ ucfirst($incident->priority) }} Priority
                </span>
                <span class="badge" style="background: {{ $incident->severity_color }}20; color: {{ $incident->severity_color }};">
                    <i class="fas fa-exclamation-circle me-1"></i> {{ ucfirst($incident->severity) }} Severity
                </span>
                @if($incident->is_overdue)
                    <span class="badge bg-danger">
                        <i class="fas fa-clock me-1"></i> Overdue
                    </span>
                @endif
            </div>

            {{-- Description Card --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-align-left text-primary me-2"></i>Description</strong>
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $incident->description }}</p>

                    @if($incident->tags)
                        <div class="mt-3">
                            @foreach($incident->tags as $tag)
                                <span class="badge bg-light text-dark me-1">#{{ $tag }}</span>
                            @endforeach
                        </div>
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
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Category</small>
                            <span class="fw-medium">{{ $incident->category?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Department</small>
                            <span class="fw-medium">{{ $incident->department?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Reported By</small>
                            <span class="fw-medium">
                                @if($incident->is_anonymous)
                                    <i class="fas fa-user-secret me-1"></i> Anonymous
                                @else
                                    {{ $incident->reporter?->name ?? 'N/A' }}
                                @endif
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Assigned To</small>
                            <span class="fw-medium">
                                @if($incident->assignedTo)
                                    <img src="{{ $incident->assignedTo->avatar_url }}" class="rounded-circle me-1" width="20" height="20">
                                    {{ $incident->assignedTo->name }}
                                @else
                                    <span class="text-warning">Unassigned</span>
                                @endif
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Location</small>
                            <span class="fw-medium">{{ $incident->location ?? 'Not specified' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Reported On</small>
                            <span class="fw-medium">{{ $incident->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">SLA Due</small>
                            <span class="fw-medium {{ $incident->is_overdue ? 'text-danger' : '' }}">
                                {{ $incident->sla_due_at?->format('d M Y, H:i') ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Last Updated</small>
                            <span class="fw-medium">{{ $incident->updated_at->format('d M Y, H:i') }}</span>
                        </div>
                        @if($incident->resolution_notes)
                            <div class="col-12">
                                <small class="text-muted d-block">Resolution Notes</small>
                                <span class="fw-medium">{{ $incident->resolution_notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Media Attachments --}}
            @if($incident->media->count() > 0)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-paperclip text-success me-2"></i>Attachments ({{ $incident->media->count() }})</strong>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($incident->media as $media)
                            <div class="col-4 col-md-3 col-lg-2">
                                @if($media->isImage())
                                    <img src="{{ $media->url }}"
                                         class="img-fluid rounded media-thumb"
                                         style="width:100%;height:100px;object-fit:cover;"
                                         onclick="window.open('{{ $media->url }}', '_blank')"
                                         alt="Attachment">
                                @else
                                    <a href="{{ $media->url }}" target="_blank"
                                       class="text-decoration-none">
                                        <div class="border rounded p-2 text-center" style="height:100px;">
                                            <i class="fas fa-file fa-2x text-muted mt-2"></i>
                                            <small class="d-block text-truncate mt-1">{{ $media->original_name }}</small>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Comments Section --}}
            <div class="card shadow-sm" id="commentsSection">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>
                        <i class="fas fa-comments text-primary me-2"></i>
                        Comments (<span id="commentCount">{{ $incident->comments_count ?? 0 }}</span>)
                    </strong>
                </div>
                <div class="card-body">
                    {{-- Comment Form --}}
                    <form id="commentForm" class="mb-4">
                        @csrf
                        <textarea id="commentContent" class="form-control mb-2" rows="2"
                                  placeholder="Write a comment..."></textarea>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Press Enter to submit</small>
                            <button type="submit" class="btn btn-primary btn-sm" id="commentSubmitBtn">
                                <i class="fas fa-paper-plane me-1"></i> Post Comment
                            </button>
                        </div>
                    </form>

                    {{-- Comments List --}}
                    <div id="commentsList">
                        @forelse($incident->comments ?? [] as $comment)
                            @include('incidents.partials.comment-item', ['comment' => $comment])
                        @empty
                            <div id="noComments" class="text-center py-4 text-muted">
                                <i class="fas fa-comments fa-2x mb-2"></i>
                                <p>No comments yet. Be the first to comment!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Timeline --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-history text-info me-2"></i>Timeline</strong>
                </div>
                <div class="card-body">
                    @forelse($incident->timeline as $event)
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background: {{ $event['color'] }}20; color: {{ $event['color'] }};">
                                <i class="fas {{ $event['icon'] }}" style="font-size: 0.65rem;"></i>
                            </div>
                            <div class="fw-medium small">{{ $event['action'] }}</div>
                            <small class="text-muted">{{ $event['timestamp'] }}</small>
                            @if($event['user_name'] && $event['user_name'] !== 'System')
                                <div><small class="text-muted">by {{ $event['user_name'] }}</small></div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted text-center small">No timeline events</p>
                    @endforelse
                </div>
            </div>

            {{-- Quick Info --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-info-circle me-2"></i>Quick Info</strong>
                </div>
                <div class="card-body small">
                    <p><strong>Response Time:</strong> {{ $incident->response_time ?? 'N/A' }}</p>
                    <p><strong>Resolution Time:</strong> {{ $incident->resolution_time ?? 'N/A' }}</p>
                    <p><strong>SLA Breaches:</strong> {{ $incident->sla_breach_count }}</p>
                    <p><strong>Views:</strong> {{ $incident->views_count }}</p>
                    <p><strong>Escalations:</strong> {{ $incident->escalations->count() }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- ASSIGN MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select name="assigned_to" class="form-select select2" required>
                            <option value="">Select User</option>
                            @foreach(\App\Models\User::where('department_id', $incident->department_id)->active()->get() as $user)
                                <option value="{{ $user->id }}" {{ $incident->assigned_to == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->role_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional assignment notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- ESCALATE MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="escalateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="escalateForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-arrow-up text-warning me-2"></i>Escalate Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Escalate To <span class="text-danger">*</span></label>
                        <select name="escalated_to" class="form-select select2" required>
                            <option value="">Select User</option>
                            @foreach(\App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['hod', 'admin', 'super-admin']))->active()->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Department <span class="text-danger">*</span></label>
                        <select name="to_department_id" class="form-select select2" required>
                            <option value="">Select Department</option>
                            @foreach(\App\Models\Department::active()->get() as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Why are you escalating this incident?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-arrow-up me-1"></i> Escalate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- RESOLVE MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resolveForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle text-success me-2"></i>Resolve Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label">Resolution Notes <span class="text-danger">*</span></label>
                        <textarea name="resolution_notes" class="form-control" rows="4"
                                  placeholder="Describe how the incident was resolved..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Resolve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Wait for everything to be loaded
document.addEventListener('DOMContentLoaded', function() {
    // Now jQuery should be available
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    // Use jQuery's ready function as well for extra safety
    jQuery(function($) {
        // Now $ is guaranteed to be jQuery

        // ==========================================
        // COMMENT FORM - AJAX Submission
        // ==========================================
        var commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var contentEl = document.getElementById('commentContent');
                var content = contentEl ? contentEl.value.trim() : '';

                if (!content) {
                    if (typeof toastr !== 'undefined') toastr.warning('Please enter a comment.');
                    return;
                }

                var btn = document.getElementById('commentSubmitBtn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Posting...';
                }

                fetch('{{ route("incidents.comments.store", $incident) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ content: content })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (contentEl) contentEl.value = '';

                        var noComments = document.getElementById('noComments');
                        if (noComments) noComments.remove();

                        // Add comment to list
                        var commentHtml =
                            '<div class="d-flex gap-3 mb-3 pb-3 border-bottom comment-item">' +
                                '<img src="' + (data.data.comment.user.avatar_url || '/images/default-avatar.png') + '" class="rounded-circle" width="36" height="36">' +
                                '<div class="flex-grow-1">' +
                                    '<div class="d-flex gap-2 align-items-center mb-1">' +
                                        '<strong style="font-size: 0.8125rem;">' + (data.data.comment.user.name || 'Unknown') + '</strong>' +
                                        '<small class="text-muted">' + (data.data.comment.created_at_diff || '') + '</small>' +
                                    '</div>' +
                                    '<p class="mb-0" style="font-size: 0.8125rem;">' + data.data.comment.content + '</p>' +
                                '</div>' +
                            '</div>';

                        var list = document.getElementById('commentsList');
                        if (list) {
                            list.insertAdjacentHTML('afterbegin', commentHtml);
                        }

                        // Update count
                        var countEl = document.getElementById('commentCount');
                        if (countEl) countEl.textContent = data.data.comments_count;

                        if (typeof toastr !== 'undefined') toastr.success('Comment posted!');
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    if (typeof toastr !== 'undefined') toastr.error('Failed to post comment.');
                })
                .finally(function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Post Comment';
                    }
                });

                return false;
            });
        }

        // ==========================================
        // ASSIGN FORM - AJAX Submission
        // ==========================================
        var assignForm = document.getElementById('assignForm');
        if (assignForm) {
            assignForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var assignedTo = this.querySelector('[name="assigned_to"]');
                var notes = this.querySelector('[name="notes"]');
                var assignToVal = assignedTo ? assignedTo.value : '';

                if (!assignToVal) {
                    if (typeof toastr !== 'undefined') toastr.warning('Please select a user to assign.');
                    return;
                }

                var btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Assigning...';
                }

                fetch('{{ route("incidents.assign", $incident) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        assigned_to: assignToVal,
                        notes: notes ? notes.value : ''
                    })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        var modal = document.getElementById('assignModal');
                        if (modal) {
                            var bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }
                        if (typeof toastr !== 'undefined') toastr.success(data.message || 'Assigned successfully!');
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    if (typeof toastr !== 'undefined') toastr.error('Failed to assign incident.');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check me-1"></i> Assign';
                    }
                });

                return false;
            });
        }

        // ==========================================
        // ESCALATE FORM - AJAX Submission
        // ==========================================
        var escalateForm = document.getElementById('escalateForm');
        if (escalateForm) {
            escalateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var escalatedTo = this.querySelector('[name="escalated_to"]');
                var deptId = this.querySelector('[name="to_department_id"]');
                var reason = this.querySelector('[name="reason"]');

                var escalatedToVal = escalatedTo ? escalatedTo.value : '';
                var deptIdVal = deptId ? deptId.value : '';
                var reasonVal = reason ? reason.value.trim() : '';

                if (!escalatedToVal || !deptIdVal || !reasonVal) {
                    if (typeof toastr !== 'undefined') toastr.warning('Please fill all required fields.');
                    return;
                }

                var btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Escalating...';
                }

                fetch('{{ route("incidents.escalate", $incident) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        escalated_to: escalatedToVal,
                        to_department_id: deptIdVal,
                        reason: reasonVal
                    })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        var modal = document.getElementById('escalateModal');
                        if (modal) {
                            var bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }
                        if (typeof toastr !== 'undefined') toastr.success(data.message || 'Escalated successfully!');
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    if (typeof toastr !== 'undefined') toastr.error('Failed to escalate incident.');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-arrow-up me-1"></i> Escalate';
                    }
                });

                return false;
            });
        }

        // ==========================================
        // RESOLVE FORM - AJAX Submission
        // ==========================================
        var resolveForm = document.getElementById('resolveForm');
        if (resolveForm) {
            resolveForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var notes = this.querySelector('[name="resolution_notes"]');
                var notesVal = notes ? notes.value.trim() : '';

                if (!notesVal) {
                    if (typeof toastr !== 'undefined') toastr.warning('Please provide resolution notes.');
                    return;
                }

                var btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Resolving...';
                }

                fetch('{{ route("incidents.resolve", $incident) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        resolution_notes: notesVal
                    })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        var modal = document.getElementById('resolveModal');
                        if (modal) {
                            var bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }
                        if (typeof toastr !== 'undefined') toastr.success(data.message || 'Resolved successfully!');
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    if (typeof toastr !== 'undefined') toastr.error('Failed to resolve incident.');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check me-1"></i> Resolve';
                    }
                });

                return false;
            });
        }

        // ==========================================
        // CLOSE INCIDENT
        // ==========================================
        window.closeIncident = function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Close Incident?',
                    text: 'Are you sure you want to close this incident?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, close it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        doCloseIncident();
                    }
                });
            } else {
                if (confirm('Are you sure you want to close this incident?')) {
                    doCloseIncident();
                }
            }
        };

        function doCloseIncident() {
            fetch('{{ route("incidents.close", $incident) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof toastr !== 'undefined') toastr.success(data.message || 'Closed successfully!');
                    setTimeout(function() { location.reload(); }, 1000);
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                if (typeof toastr !== 'undefined') toastr.error('Failed to close incident.');
            });
        }

        // ==========================================
        // INITIALIZE SELECT2 IN MODALS
        // ==========================================
        var assignModal = document.getElementById('assignModal');
        var escalateModal = document.getElementById('escalateModal');

        function initModalSelect2(modal) {
            if (!modal) return;
            modal.addEventListener('shown.bs.modal', function() {
                var selects = modal.querySelectorAll('.select2');
                selects.forEach(function(select) {
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        jQuery(select).select2({
                            dropdownParent: jQuery(modal),
                            placeholder: 'Search...',
                            allowClear: true,
                            width: '100%'
                        });
                    }
                });
            });
        }

        initModalSelect2(assignModal);
        initModalSelect2(escalateModal);

        console.log('Incident show page scripts initialized');
    });
});
</script>
@endpush
