<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0" style="font-size: 0.8125rem;">
        @if(View::hasSection('breadcrumbs'))
            @yield('breadcrumbs')
        @elseif(isset($breadcrumbs))
            @foreach($breadcrumbs as $label => $url)
                @if(is_int($label))
                    <li class="breadcrumb-item">
                        @if(!$loop->last)
                            <a href="{{ $url }}">{{ $url }}</a>
                        @else
                            {{ $url }}
                        @endif
                    </li>
                @else
                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                        @if(!$loop->last)
                            <a href="{{ $url }}">{{ $label }}</a>
                        @else
                            {{ $label }}
                        @endif
                    </li>
                @endif
            @endforeach
        @endif
    </ol>
</nav>
