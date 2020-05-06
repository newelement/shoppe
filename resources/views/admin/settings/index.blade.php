@extends('neutrino::admin.template.header-footer')
@section('title', 'Shoppe Settings | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Shoppe Settings</h2>
            </div>

            <div class="settings-container">
                <ul class="form-tabs">
                    <li><a href="#tab-00" class="{{ !request('tab') || request('tab') === 'products' ? 'active' : '' }}">Products</a></li>
                    <li><a href="#tab-0" class="{{ request('tab') === 'cart' ? 'active' : '' }}">Cart</a></li>
                    <li><a href="#tab-checkout" class="{{ request('tab') === 'checkout' ? 'active' : '' }}">Checkout</a></li>
                    <li><a href="#tab-1" class="{{ request('tab') === 'shipping' ? 'active' : '' }}">Shipping</a></li>
                    <li><a href="#tab-2" class="{{ request('tab') === 'taxes' ? 'active' : '' }}">Taxes</a></li>
                </ul>
                <div class="tabs-content">

                    <div id="tab-00" class="tab-content {{ !request('tab') || request('tab') === 'products' ? 'active' : '' }}">
                        @include('shoppe::admin.partials.products-settings')
                    </div>
                    <div id="tab-0" class="tab-content {{ request('tab') === 'cart' ? 'active' : '' }}">
                        @include('shoppe::admin.partials.cart-settings')
                    </div>
                    <div id="tab-checkout" class="tab-content {{ request('tab') === 'checkout' ? 'active' : '' }}">
                        @include('shoppe::admin.partials.checkout-settings')
                    </div>
                    <div id="tab-1" class="tab-content {{ request('tab') === 'shipping' ? 'active' : '' }}">

                        <p class="setttings-section-links">
                        <a href="/admin/shoppe-settings?tab=shipping&section=shipping_settings" class="{{ request('section') === 'shipping_settings' || !request('section')? 'active' : '' }}">Shipping Settings</a> - <a href="/admin/shoppe-settings?tab=shipping&section=shipping_classes" class="{{ request('section') === 'shipping_classes'? 'active' : '' }}">Shipping Classes</a> - <a href="/admin/shoppe-settings?tab=shipping&section=shipping_methods" class="{{ request('section') === 'shipping_methods'? 'active' : '' }}">Shipping Methods</a>
                        </p>

                        @if( request('section') === 'shipping_settings' || !request('section') )
                            @include('shoppe::admin.partials.shipping-settings')
                        @endif

                        @if( request('tab') === 'shipping' && request('section') === 'shipping_methods' )
                            @include('shoppe::admin.partials.shipping-methods-form')
                        @endif

                        @if( request('tab') === 'shipping' && request('section') === 'shipping_classes' )
                            @include('shoppe::admin.partials.shipping-classes-form')
                        @endif
                    </div>
                    <div id="tab-2" class="tab-content {{ request('tab') === 'taxes' ? 'active' : '' }}">
                        @include('shoppe::admin.partials.tax-settings')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
