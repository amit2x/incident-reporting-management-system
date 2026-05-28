{{-- resources/views/incidents/partials/escalation-history.blade.php --}}
@if($incident->escalations && $incident->escalations->count() > 0)
<div class="card mb-3 shadow-sm">
    <div class="card-header bg-white">
        <strong><i class="fas fa-arrow-up-right-dots text-warning me-2"></i>Escalation History</strong>
        <small class="text-muted ms-auto">{{ $incident->escalations->count() }} escalation(s)</small>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @foreach($incident->escalations as $escalation)
                <div class="list-group-item">
                    <div class="d-flex align-items-start gap-2">
                        {{-- Level Badge --}}
                        <span class="badge rounded-pill flex-shrink-0" style="
                            background: {{ $escalation->level >= 3 ? '#DC2626' : ($escalation->level == 2 ? '#F59E0B' : '#EF4444') }}15;
                            color: {{ $escalation->level >= 3 ? '#DC2626' : ($escalation->level == 2 ? '#F59E0B' : '#EF4444') }};
                            font-size: 0.65rem; padding: 4px 10px;
                        ">
                            Level {{ $escalation->level }}
                        </span>

                        <div class="flex-grow-1 min-width-0">
                            {{-- Escalation Flow --}}
                            <div class="d-flex align-items-center gap-2 flex-wrap small">
                                <span class="text-muted">From:</span>
                                <strong>{{ $escalation->fromDepartment?->name ?? 'N/A' }}</strong>
                                <span class="text-muted">({{ $escalation->escalatedBy?->name ?? 'System' }})</span>
                                <i class="fas fa-arrow-right text-muted"></i>
                                <span class="text-muted">To:</span>
                                <strong>{{ $escalation->toDepartment?->name ?? 'N/A' }}</strong>
                                <span class="text-muted">({{ $escalation->escalatedTo?->name ?? 'N/A' }})</span>
                            </div>

                            {{-- Reason --}}
                            @if($escalation->reason)
                                <div class="mt-1 small">
                                    <span class="text-muted">📝 Reason:</span>
                                    <span>{{ $escalation->reason }}</span>
                                </div>
                            @endif

                            {{-- Status & Date --}}
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="badge bg-{{ $escalation->status === 'accepted' ? 'success' : ($escalation->status === 'rejected' ? 'danger' : 'warning') }}"
                                      style="font-size: 0.6rem;">
                                    {{ ucfirst($escalation->status) }}
                                </span>
                                <small class="text-muted">{{ $escalation->created_at->format('d M Y, H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
