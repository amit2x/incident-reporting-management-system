@props(['priority'])

<span {{ $attributes->merge(['class' => 'priority-badge ' . $priority]) }}>
    <i class="fas fa-flag me-1"></i>
    {{ ucfirst($priority) }}
</span>