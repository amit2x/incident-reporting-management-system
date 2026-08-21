{{-- resources/views/incidents/partials/action-history.blade.php --}}
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-history text-primary me-2"></i>
            Action History
            <span class="badge bg-light text-dark ms-2">{{ count($incident->getCompleteActionHistory()) }}</span>
        </h6>
        <small class="text-muted">All actions with reasons</small>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($incident->getCompleteActionHistory() as $action)
            <div class="list-group-item">
                <div class="d-flex align-items-start gap-3">
                    {{-- Icon --}}
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 36px; height: 36px; background: {{ $action['color'] }}20; color: {{ $action['color'] }}">
                        <i class="fas {{ $action['icon'] }}"></i>
                    </div>
                    
                    {{-- Content --}}
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start">
                            <div>
                                <strong>{{ $action['action'] }}</strong>
                                @if($action['from_status'] && $action['to_status'])
                                    <small class="text-muted">
                                        ({{ $action['from_status'] }} → {{ $action['to_status'] }})
                                    </small>
                                @endif
                            </div>
                            <small class="text-muted flex-shrink-0 ms-2">{{ $action['acted_at'] }}</small>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <img src="{{ $action['user_avatar'] ?? asset('images/default-avatar.png') }}" 
                                 class="rounded-circle" width="20" height="20">
                            <span class="small">{{ $action['user'] }}</span>
                        </div>
                        
                        {{-- Reason with rejection highlight --}}
                        @if($action['reason'])
                            <div class="mt-1 small {{ $action['action'] === 'Rejected' ? 'text-danger' : 'text-muted' }}">
                                <i class="fas fa-quote-left me-1" style="font-size: 0.6rem;"></i>
                                {{ Str::limit($action['reason'], 150) }}
                            </div>
                        @endif
                        
                        {{-- Rejection details - if action is rejected and has metadata --}}
                        @if($action['action'] === 'Rejected' && isset($action['metadata']))
                            @php
                                $metadata = $action['metadata'] ?? [];
                                $rejectionReason = $metadata['rejection_reason'] ?? $action['reason'] ?? null;
                                $rejectedBy = $metadata['rejected_by_name'] ?? $action['user'] ?? 'Unknown';
                            @endphp
                            @if($rejectionReason)
                                <div class="mt-1 text-danger small">
                                    <i class="fas fa-times-circle me-1"></i>
                                    <strong>Rejection Reason:</strong> {{ $rejectionReason }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="fas fa-history fa-2x mb-2"></i>
                <p>No actions recorded yet</p>
            </div>
            @endforelse
        </div>
    </div>
</div>