<div class="card">
    <div class="card-title">📋 Incident Details</div>
    
    @foreach($component->getFields() as $field => $label)
        @php
            $value = $component->getFieldValue($field);
            $isPriority = $field === 'priority';
            $isStatus = $field === 'status';
        @endphp
        
        <div class="info-row">
            <span class="info-label">{{ $label }}</span>
            <span class="info-value">
                @if($isPriority)
                    <span class="badge {{ $component->getPriorityBadge() }}">
                        {{ $value }}
                    </span>
                @elseif($isStatus)
                    <span class="badge {{ $component->getStatusBadge() }}">
                        {{ $value }}
                    </span>
                @else
                    {{ $value }}
                @endif
            </span>
        </div>
    @endforeach
    
    {{-- Extra Fields --}}
    @if(!empty($extraFields))
        @foreach($extraFields as $label => $value)
            <div class="info-row">
                <span class="info-label">{{ $label }}</span>
                <span class="info-value">{{ $value }}</span>
            </div>
        @endforeach
    @endif
</div>

{{-- Description --}}
@if($component->showDescription && $incident->description)
    <div style="margin: 8px 0 12px;">
        <div style="font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 4px;">📝 Incident Summary</div>
        <div class="description-box">
            {{ Str::limit($incident->description, 300) }}
            @if(Str::length($incident->description) > 300)
                <span style="color: #0d47a1;">...</span>
            @endif
        </div>
    </div>
@endif