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
                    <li><a href="#tab-0" class="{{ !request('tab') || request('tab') === 'cart' ? 'active' : '' }}">Cart</a></li>
                    <li><a href="#tab-1" class="{{ request('tab') === 'shipping' ? 'active' : '' }}">Shipping</a></li>
                </ul>
                <div class="tabs-content">

                    <div id="tab-0" class="tab-content {{ !request('tab') || request('tab') === 'cart' ? 'active' : '' }}">

                        <div class="form-row">
                            <label class="label-col" for="add-cart-action">Add to cart action</label>
                            <div class="input-col">
                                <div class="select-wrapper">
                                    <select id="add-cart-action" name="add_cart_action" >
                                        <option value="ajax">Keep user on product page and show slide-in cart</option>
                                        <option value="redirect">Redirect user to the cart page</option>
                                    </select>
                                </div>
                            </div>
                            <span class="input-notes">
                                <span class="note">What happens when a user adds something to the cart?</span>
                            </span>
                        </div>

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

                </div>
            </div>
        </div>
    </div>
@endsection
