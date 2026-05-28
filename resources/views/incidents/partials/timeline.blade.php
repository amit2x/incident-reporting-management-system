{{-- resources/views/incidents/partials/timeline.blade.php --}}
<div class="card mb-3 shadow-sm">
    <div class="card-header bg-white">
        <strong><i class="fas fa-history text-info me-2"></i>Activity Timeline</strong>
        <small class="text-muted ms-auto">{{ count($incident->timeline) }} events</small>
    </div>
    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
        @php
            $timeline = $incident->timeline;
            // Add escalation history
            if ($incident->escalations && $incident->escalations->count() > 0) {
                foreach ($incident->escalations as $esc) {
                    $timeline[] = [
                        'action' => 'Escalated to Level ' . $esc->level,
                        'user_name' => $esc->escalatedBy?->name ?? 'System',
                        'timestamp' => $esc->created_at->format('d M Y, H:i'),
                        'icon' => 'fa-arrow-up',
                        'color' => $esc->level >= 3 ? '#DC2626' : ($esc->level == 2 ? '#F59E0B' : '#EF4444'),
                        'details' => 'To: ' . ($esc->escalatedTo?->name ?? 'N/A') . ' | Dept: ' . ($esc->toDepartment?->name ?? 'N/A') . ($esc->reason ? ' | Reason: ' . Str::limit($esc->reason, 50) : ''),
                    ];
                }
            }
            // Add reassignment history
            if ($incident->assignmentHistory && $incident->assignmentHistory()->count() > 1) {
                foreach ($incident->assignmentHistory as $assign) {
                    if ($assign->assignedBy) {
                        $timeline[] = [
                            'action' => 'Reassigned',
                            'user_name' => $assign->assignedBy?->name ?? 'System',
                            'timestamp' => $assign->created_at->format('d M Y, H:i'),
                            'icon' => 'fa-user-plus',
                            'color' => '#3B82F6',
                            'details' => 'To: ' . ($assign->assignedTo?->name ?? 'N/A') . ($assign->notes ? ' | ' . Str::limit($assign->notes, 50) : ''),
                        ];
                    }
                }
            }
            // Sort by timestamp
            usort($timeline, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
        @endphp

        @forelse($timeline as $index => $event)
            <div class="timeline-item {{ $index === 0 ? 'current-event' : '' }}" style="position: relative; padding-left: 32px; margin-bottom: 16px;">
                {{-- Connecting line --}}
                @if(!$loop->last)
                    <div style="position: absolute; left: 11px; top: 28px; bottom: -16px; width: 2px; background: #e5e7eb;"></div>
                @endif

                {{-- Dot with icon --}}
                <div class="timeline-dot {{ $index === 0 ? 'pulse-animation' : '' }}"
                     style="position: absolute; left: 0; top: 2px; width: 24px; height: 24px; border-radius: 50%;
                            display: flex; align-items: center; justify-content: center;
                            background: {{ $event['color'] }}15; color: {{ $event['color'] }};
                            {{ $index === 0 ? 'box-shadow: 0 0 0 4px ' . $event['color'] . '30;' : '' }}">
                    <i class="fas {{ $event['icon'] }}" style="font-size: 0.65rem;"></i>
                </div>

                {{-- Content --}}
                <div>
                    <div class="fw-medium small d-flex align-items-center gap-2">
                        {{ $event['action'] }}
                        @if($index === 0)
                            <span class="badge bg-success" style="font-size: 0.55rem;">LIVE</span>
                        @endif
                    </div>
                    <small class="text-muted d-block">{{ $event['timestamp'] }}</small>

                    @if(!empty($event['user_name']) && $event['user_name'] !== 'System')
                        <div><small class="text-muted">👤 {{ $event['user_name'] }}</small></div>
                    @elseif($event['user_name'] === 'System')
                        <div><small class="text-muted">🤖 System (Auto)</small></div>
                    @endif

                    @if(!empty($event['details']))
                        <div><small class="text-muted" style="font-size: 0.65rem;">{{ $event['details'] }}</small></div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted text-center small py-3">No timeline events yet</p>
        @endforelse
    </div>
</div>

@push('styles')
<style>
    .timeline-item:last-child { margin-bottom: 0 !important; }
    .current-event {
        background: linear-gradient(90deg, rgba(59,130,246,0.05) 0%, transparent 100%);
        border-radius: 8px;
        padding: 8px;
        margin-left: -8px;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 4px rgba(59,130,246,0.2); }
        50% { box-shadow: 0 0 0 8px rgba(59,130,246,0.1); }
    }
    .pulse-animation {
        animation: pulse 2s infinite;
    }
</style>
@endpush
