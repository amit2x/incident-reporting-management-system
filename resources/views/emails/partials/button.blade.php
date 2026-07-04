<div style="text-align: center; margin: 24px 0 16px;">
    <a href="{{ $url }}" class="btn btn-{{ $type }}" style="
        display: inline-block;
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        text-align: center;
        background-color: {{ $type === 'primary' ? '#0d47a1' : ($type === 'secondary' ? '#e2e8f0' : '#0d47a1') }};
        color: {{ $type === 'primary' ? '#ffffff' : ($type === 'secondary' ? '#475569' : '#ffffff') }};
        {{ $type === 'secondary' ? 'border: 1px solid #cbd5e1;' : '' }}
    ">
        @if($icon)
            <span style="margin-right: 8px;">{{ $icon }}</span>
        @endif
        {{ $text }}
    </a>
</div>