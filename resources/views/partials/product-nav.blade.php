<aside class="col-md-3 pt-2 pb-4 products-nav-sidebar">
    <form action="{{ url()->current() }}" method="get">

    <button type="submit" class="btn btn-secondary btn-block mb-3">Apply Filters</button>

    @php
    $currentFilters = getProductFilters();
    @endphp
    @if( $currentFilters )
    <ul class="current-filters">
    @foreach( $currentFilters as $name => $filterTypes )
        @foreach( $filterTypes as $filter  )
        <li>
            <a href="/del-filter/{{$name}}/{{$filter}}{{ productQueryString() }}">{{ $name }}: {{ $filter }} <span>&times;</span></a>
        </li>
        @endforeach
    @endforeach
    </ul>
    @endif

    @foreach( $data->menu_categories as $title => $terms )

        @if( $terms['type'] === "tag" && count($terms['items']) > 0 )
        <h3>{{ \Str::plural($title) }}</h3>
        @endif

        @if( $terms['type'] === "hierarchical" && count($terms['items']) > 0 )
        <h3>{{ \Str::plural($title) }}</h3>
        @endif

        @if( $terms['type'] === "hierarchical" )
        <ul class="product-term-list pr-3">
            @each( 'shoppe::partials.product-category', $terms['items'], 'menuItem')
        </ul>
        @endif
        @if( $terms['type'] === "tag" )
        <ul class="product-tag-list pr-3">
            @foreach( $terms['items'] as $menuItem )
            @if( !isEmptyProductTag($menuItem) )
            <li data-slug="{{ $menuItem['slug'] }}">
                <input
                    type="checkbox"
                    id="{{ $menuItem['slug'] }}"
                    name="filters[{{$menuItem['taxonomy_slug']}}][]" value="{{ $menuItem['slug'] }}"
                    {{ isCurrentFilter( $menuItem['taxonomy_slug'], $menuItem['slug'])? 'checked="checked"' : '' }}
                    >
                <label for="{{ $menuItem['slug'] }}"> {{ $menuItem['title'] }} </label>
            </li>
            @endif
            @endforeach
        </ul>
        @endif
    @endforeach

        <button type="submit" class="btn btn-secondary btn-block">Apply Filters</button>

    </form>
</aside>
