<div class="status-banner" style="background-color: {{ $color }};">
    <span class="icon">
        @if($type === 'assigned') 📋
        @elseif($type === 'escalated') ⚠️
        @elseif($type === 'resolved') ✅
        @elseif($type === 'closed') 🔒
        @elseif($type === 'rejected') ❌
        @elseif($type === 'mention') 💬
        @elseif($type === 'comment') 💬
        @elseif($type === 'created') 🆕
        @else 📌
        @endif
    </span>
    <span>
        {{ $label }}
        @if($incidentId)
            <span style="font-weight: 300; opacity: 0.8;"> – {{ $incidentId }}</span>
        @endif
    </span>
</div>