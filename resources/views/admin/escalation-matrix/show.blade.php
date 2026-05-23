{{-- resources/views/admin/escalation-matrix/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Escalation Entry #' . $escalationMatrix->id . ' - IRMS')

@push('styles')
<style>
    .detail-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #6b7280; font-size: 0.8125rem; }
    .detail-value { font-weight: 500; font-size: 0.875rem; text-align: right; }
    .level-badge-lg {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.25rem;
    }
    .level-1 { background: #DBEAFE; color: #1E40AF; }
    .level-2 { background: #FEF3C7; color: #92400E; }
    .level-3 { background: #FEE2E2; color: #991B1B; }
    .level-4 { background: #EDE9FE; color: #5B21B6; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="mb-3">
        <a href="{{ route('admin.escalation-matrix.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Escalation Matrix
        </a>
        <h4 class="fw-bold mt-1 mb-0">Escalation Entry Details</h4>
    </div>

    <div class="row g-3">
        {{-- Main Details --}}
        <div class="col-lg-8">
            <div class="detail-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="level-badge-lg level-{{ $escalationMatrix->level }}">L{{ $escalationMatrix->level }}</span>
                    <div>
                        <h5 class="mb-0">{{ $escalationMatrix->level_label }}</h5>
                        <small class="text-muted">Entry #{{ $escalationMatrix->id }}</small>
                    </div>
                    <span class="badge {{ $escalationMatrix->is_active ? 'bg-success' : 'bg-danger' }} ms-auto">
                        {{ $escalationMatrix->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Department</span>
                    <span class="detail-value">
                        <span style="color: {{ $escalationMatrix->department?->color }};">
                            <i class="fas fa-building me-1"></i>
                            {{ $escalationMatrix->department?->name ?? 'N/A' }}
                        </span>
                        <small class="text-muted d-block">({{ $escalationMatrix->department?->code ?? '' }})</small>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">
                        @if($escalationMatrix->category)
                            <span style="color: {{ $escalationMatrix->category->color }};">
                                <i class="{{ $escalationMatrix->category->icon ?? 'fas fa-tag' }} me-1"></i>
                                {{ $escalationMatrix->category->name }}
                            </span>
                        @else
                            <span class="text-muted fst-italic">All Categories (Default)</span>
                        @endif
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Timeout</span>
                    <span class="detail-value">
                        <i class="fas fa-clock text-warning me-1"></i>
                        {{ $escalationMatrix->timeout_formatted }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Escalate To (User)</span>
                    <span class="detail-value">
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <img src="{{ $escalationMatrix->escalateToUser?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&size=32' }}"
                                 class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                            <div class="text-end">
                                <div class="fw-medium">{{ $escalationMatrix->escalateToUser?->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $escalationMatrix->escalateToUser?->role_name ?? '' }}</small>
                            </div>
                        </div>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Target Department</span>
                    <span class="detail-value">
                        <span class="badge" style="background: {{ $escalationMatrix->escalateToDepartment?->color ?? '#6B7280' }}20; color: {{ $escalationMatrix->escalateToDepartment?->color ?? '#6B7280' }};">
                            {{ $escalationMatrix->escalateToDepartment?->name ?? 'N/A' }}
                        </span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Notifications</span>
                    <span class="detail-value">
                        @if($escalationMatrix->notify_via_email)
                            <span class="badge bg-info me-1"><i class="fas fa-envelope me-1"></i>Email</span>
                        @endif
                        @if($escalationMatrix->notify_via_push)
                            <span class="badge bg-primary"><i class="fas fa-bell me-1"></i>Push</span>
                        @endif
                        @if(!$escalationMatrix->notify_via_email && !$escalationMatrix->notify_via_push)
                            <span class="text-muted">None</span>
                        @endif
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Created</span>
                    <span class="detail-value small">{{ $escalationMatrix->created_at->format('d M Y, H:i') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Last Updated</span>
                    <span class="detail-value small">{{ $escalationMatrix->updated_at->format('d M Y, H:i') }}</span>
                </div>

                <div class="d-flex gap-2 mt-3 pt-3 border-top">
                    <a href="{{ route('admin.escalation-matrix.edit', $escalationMatrix) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Entry
                    </a>
                    <form action="{{ route('admin.escalation-matrix.destroy', $escalationMatrix) }}" method="POST"
                          onsubmit="return confirm('Delete this escalation entry?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Related Entries --}}
            @if($relatedEntries->count() > 0)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-list me-2"></i>Other Levels for this Department</strong>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($relatedEntries as $related)
                            <a href="{{ route('admin.escalation-matrix.show', $related) }}"
                               class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                                <span class="level-badge level-{{ $related->level }}" style="width:24px;height:24px;font-size:0.6rem;">L{{ $related->level }}</span>
                                <div class="flex-grow-1">
                                    <small>
                                        @if($related->category)
                                            {{ $related->category->name }}
                                        @else
                                            All Categories
                                        @endif
                                    </small>
                                </div>
                                <small class="text-muted">{{ $related->timeout_formatted }}</small>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Level Info --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-info-circle me-2"></i>Escalation Flow</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @for($i = 1; $i <= 4; $i++)
                            <div class="d-flex align-items-center gap-3">
                                <span class="level-badge level-{{ $i }}" style="width:28px;height:28px;font-size:0.65rem;">L{{ $i }}</span>
                                <small>
                                    @if($i == 1) Supervisor - First responder
                                    @elseif($i == 2) HOD - Department head
                                    @elseif($i == 3) Admin - Administration
                                    @else Director - Top management @endif
                                </small>
                                @if($escalationMatrix->level == $i)
                                    <span class="badge bg-primary ms-auto">Current</span>
                                @endif
                            </div>
                            @if($i < 4)
                                <div class="text-center text-muted">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
