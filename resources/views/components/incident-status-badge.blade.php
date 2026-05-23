@props(['status'])

<span {{ $attributes->merge(['class' => 'status-badge ' . str_replace('_', '-', $status)]) }}>
    {{ str_replace('_', ' ', ucfirst($status)) }}
</span>