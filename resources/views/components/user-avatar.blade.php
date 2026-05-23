@props(['user', 'size' => 32])

<img src="{{ $user->avatar_url }}" 
     alt="{{ $user->name }}" 
     class="rounded-circle"
     width="{{ $size }}" 
     height="{{ $size }}"
     {{ $attributes }}>