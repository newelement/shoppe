@if( !isEmptyProductTerm($menuItem) )
<li class="{{ $menuItem['children']? 'has-children' : '' }} {{ isInRouteSegment($menuItem['slug'])? 'open' : '' }}" data-slug="{{ $menuItem['slug'] }}">
    <a href="{{ $menuItem['url'] }}">{{ $menuItem['title'] }}</a>
    @if( $menuItem['children'] )
    <ul>
        @each( 'shoppe::partials.product-category', $menuItem['children'] ,'menuItem')
    </ul>
    @endif
</li>
@endif
