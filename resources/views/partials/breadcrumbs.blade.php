<nav class="shoppe-breadcrumbs mt-4 mb-3">
    <ul>
    @foreach ($breadcrumbs as $title => $url)
        <li class="{{ $loop->last ? 'is-active' : '' }}">
            @if (! $loop->last)
            <a href="{{ url($url) }}">{{ $title }}</a> <span class="crumb-div">&rsaquo;</span>
            @else
            <span class="current-crumb">{{ $title }}</span>
            @endif
        </li>
    @endforeach
    </ul>
</nav>
