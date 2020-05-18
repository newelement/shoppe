@extends('neutrino::layouts.header-footer')
@section('title', $data->title.' | ')
@section('meta_keywords', $data->keywords)
@section('meta_description', $data->meta_description)
@section('og')
<meta property="og:title" content="{{ $data->title }}" />
<meta property="og:description" content="{{ $data->meta_description }}" />
@if( isset($data->social_image) && strlen($data->social_image) )
@php
$socialImages = getImageSizes($data->social_image);
@endphp
<meta property="og:image" content="{{ env('APP_URL') }}{{ $socialImages['original'] }}"/>
@endif
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6 pt-4">
                <h1>{{ $data->title }}</h1>
            </div>
        </div>
        <form id="checkout-form" class="checkout-form" action="/checkout" method="post">
            <div class="row">
                <div class="col-md-8 pt-2">
                       @csrf

                        <div class="checkout-steps">
                            <div class="inner">
                                <div class="checkout-step current"><span class="checkout-step-number">1</span> Shipping</div>
                                <span class="step-div"></span>
                                <div class="checkout-step"><span class="checkout-step-number">2</span> Payment</div>
                            </div>
                        </div>

                        <div class="checkout-steps-wrap">
                            <div id="checkout-step-1" class="checkout-form-section current shipping-form-section">

                                <section class="section-card">
                                    <header>
                                        <h3>Shipping Address</h3>
                                    </header>
                                    <div class="inner">
                                        @php
                                        $shippingAddresses = $data->shipping_addresses->count() ? true : false;
                                        @endphp

                                        @if($shippingAddresses)
                                        <div class="row">
                                            <div class="col-sm-12">
                                                @foreach( $data->shipping_addresses as $key => $address )
                                                <div class="block-check-label">
                                                    <input class="form-check-input shipping-address-option" type="radio" name="shipping_address_option" data-name="{{ $address->name }}" data-company-name="{{ $address->company_name }}" data-address="{{ $address->address }}" data-address2="{{ $address->address2 }}" data-city="{{ $address->city }}" data-state="{{ $address->state }}" data-zipcode="{{ $address->zipcode }}" data-country="{{ $address->country }}" id="gridRadios{{ $key }}" value="{{ $address->id }}">
                                                    <label for="gridRadios{{ $key }}">
                                                        <span>
                                                        {{ $address->name }}, @if($address->company_name) {{ $address->company_name }} @endif {{ $address->address }} {{ $address->address2 }} {{ $address->city }} {{ $address->state }} {{ $address->zipcode }} {{ $address->country }}
                                                        </span>
                                                    </label>
                                                </div>
                                                @endforeach

                                                <div class="block-check-label">
                                                    <input class="form-check-input shipping-address-option" type="radio" name="shipping_address_option" id="new-shipping-address" value="new_shipping_address">
                                                    <label for="new-shipping-address">
                                                        <span>
                                                        ENTER NEW ADDRESS &rarr;
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <input class="form-check-input shipping-address-option" type="radio" name="shipping_address_option" id="new-shipping-address" value="new_shipping_address" checked style="display: none">
                                        @endif

                                        <div class="new-shipping-address" @if( $shippingAddresses ) style="display: none;" @endif>

                                            <div class="form-group row">
                                                <label for="shipping-name" class="col-sm-4 col-form-label">Name</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" id="shipping-name" name="shipping_name" data-required data-required-message="Please enter your shipping name." value="{{ old('shipping_name') }}">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-name" class="col-sm-4 col-form-label">Company <small>(optional)</small></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" id="shipping-company_name" name="shipping_company_name" value="{{ old('shipping_company_name') }}">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-address" class="col-sm-4 col-form-label">Address</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control shipping-address-field shipping-address" id="shipping-address" name="shipping_address" value="{{ old('shipping_address') }}" data-required data-required-message="Please enter your shipping address.">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-address2" class="col-sm-4 col-form-label">Address 2 <small>(optional)</small></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" id="shipping-address2" name="shipping_address2" value="{{ old('shipping_address2') }}">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-city" class="col-sm-4 col-form-label">City</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control shipping-address-field" id="shipping-city" name="shipping_city" value="{{ old('shipping_city') }}" data-required data-required-message="Please enter your shipping city.">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-state" class="col-sm-4 col-form-label">State / Province</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control shipping-address-field" id="shipping-state" name="shipping_state" data-required data-required-message="Please enter your shipping state.">
                                                        <option value="">Choose state / province ....</option>
                                                        @php $states = getStates();  @endphp
                                                        @foreach( $states as $key => $value )
                                                        <option value="{{ $key }}" {{ old('shipping_state') === $key? 'selected="selected"' : '' }}>{{ $value }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-zip-code" class="col-sm-4 col-form-label">Zip / Postal Code</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control shipping-address-field shipping-zip-code" id="shipping-zip-code" name="shipping_zipcode" value="{{ old('shipping_zipcode') }}" data-required data-required-message="Please enter your shipping zip code.">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label for="shipping-country" class="col-sm-4 col-form-label">Country</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control shipping-address-field" id="shipping-country" name="shipping_country" data-required data-required-message="Please enter your shipping country.">
                                                        <option value="">Choose country ....</option>
                                                        <option value="CA">Canada</option>
                                                        <option value="US">United States</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                            @php
                            //dump($data)
                            @endphp

                            @if( $data->shipping_type === 'free' )
                                <input type="hidden" name="shipping_rate" value="{{ $data->shipping_rates->id }}">
                            @endif

                            @if( $data->shipping_type === 'estimated' || $data->shipping_type === 'flat' )
                            <section class="section-card">
                                <header>
                                    <h3>Shipping Method</h3>
                                </header>
                                <div class="inner">
                                    <ul class="shipping-rates-list" data-shipping-type="{{ $data['shipping_type'] }}">
                                        @if( $data->shipping_type === 'estimated' || $data->shipping_type === 'flat')
                                        <li class="enter-shipping-address-for-rates">Enter / choose your shipping address to see rates.</li>
                                        @endif
                                    </ul>
                                </div>
                            </section>
                            @endif

                        </div>


                        <div id="checkout-step-2" class="checkout-form-section billing-form-section">
                            <section class="section-card">
                                <header>
                                    <h3>Payment</h3>

                                    <a href="#checkout-step-1" class="text-secondary checkout-prev-btn" data-step="1" role="button">&larr; Back to Shipping</a>
                                </header>
                                <div class="inner">

                                    <div class="form-group row">
                                        <label for="email" class="col-sm-4 col-form-label">Email</label>
                                        <div class="col-sm-8">
                                            @if( Auth::check() )
                                            {{ auth()->user()->email }}
                                            @endif
                                            <input type="text" class="form-control @if(Auth::check() ) {{ 'hide' }}  @endif" id="email" name="email" value="{{ old('email', auth()->user()? auth()->user()->email : '' ) }}" data-required data-required-message="Please enter your email." required >
                                            <span class="form-text text-muted">We'll use your email for your order receipt.</span>
                                        </div>
                                    </div>

                                    @if( !auth()->check() && getShoppeSetting('create_account_checkout') )
                                    <div class="form-group row">
                                        <label for="create-account-checkout" class="col-sm-4 col-form-label">Create Password</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="create-account-checkout" name="create_account_checkout">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <a href="#" class="password-toggle" data-toggle-target="create-account-checkout">
                                                            <svg class="bi bi-eye-slash" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                              <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 00-2.79.588l.77.771A5.944 5.944 0 018 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0114.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
                                                              <path d="M11.297 9.176a3.5 3.5 0 00-4.474-4.474l.823.823a2.5 2.5 0 012.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 01-4.474-4.474l.823.823a2.5 2.5 0 002.829 2.829z"/>
                                                              <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 001.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 018 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709z"/>
                                                              <path fill-rule="evenodd" d="M13.646 14.354l-12-12 .708-.708 12 12-.708.708z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </a>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="form-text text-muted">Enter a password if you would like to create an account. (optional)</span>
                                        </div>
                                    </div>
                                    @endif

                                    @if( $data->payment_types )
                                        <ul class="choose-stored-payment-list">
                                        @foreach( $data->payment_types as $key => $paymentType )
                                            <li>
                                                <input id="payment-type-{{ $key }}" type="radio" name="saved_payment" value="{{ $paymentType['id'] }}" {{ $paymentType['default']? 'checked' : '' }} >
                                                <label for="payment-type-{{ $key }}">{{ $paymentType['card_brand'] }} / {{ $paymentType['last_four'] }} &mdash; Expires: {{ $paymentType['exp_month'] }}-{{$paymentType['exp_year']}}</label>
                                            </li>
                                        @endforeach
                                            <li>
                                                <input id="payment-type-new" type="radio" name="saved_payment" value="new_payment_type">
                                                <label for="payment-type-new">ENTER NEW PAYMENT TYPE &rarr;</label>
                                            </li>
                                        </ul>
                                    @endif



                                    <!--
                                    <div class="form-group row">
                                        <legend class="col-sm-4 col-form-label pt-0">Billing Address</legend>
                                        <div class="col-sm-8">
                                            <div class="form-check">
                                                <input class="form-check-input billing-address-option" id="same-as-shipping" type="checkbox" name="billing_address_option" value="same_shipping">
                                                <label class="form-check-label" for="same-as-shipping">
                                                Same as shipping?
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="billing-address">

                                        <div class="form-group row">
                                            <label for="billing-address2" class="col-sm-4 col-form-label">Address 2 <small>(optional)</small></label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="billing-address2" name="billing_address2" value="{{ old('billing_address2') }}">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-city" class="col-sm-4 col-form-label">City</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="billing-city" name="billing_city" value="{{ old('billing_city') }}" data-required data-required-message="Please enter your billing city.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-state" class="col-sm-4 col-form-label">State / Providence</label>
                                            <div class="col-sm-8">
                                                <select class="form-control" id="billing-state" name="billing_state" required>
                                                    <option value="">Choose state ....</option>
                                                    @php $states = getStates();  @endphp
                                                    @foreach( $states as $key => $value )
                                                    <option value="{{ $key }}" {{ old('billing_state') === $key? 'selected="selected"' : '' }}>{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-zip-code" class="col-sm-4 col-form-label">Zip / Postal Code</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="billing-zip-code" name="billing_zipcode" value="{{ old('billing_zipcode') }}" data-required data-required-message="Please enter your billing zip code.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-country" class="col-sm-4 col-form-label">Country / Region</label>
                                            <div class="col-sm-8">
                                                <select class="form-control" id="billing-country" name="billing_country">
                                                    <option value="">Choose country ....</option>
                                                    <option value="CA">Canada</option>
                                                    <option value="US">United States</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                    -->

                                    <div id="payment-fields" class="payment-section-fields @if($data->payment_types) hide @endif ">

                                        <div class="form-group row">
                                            <label for="cc-name" class="col-sm-4 col-form-label">Name on card</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="cc-name" name="cc_name" value="{{ old('cc_name') }}" data-required data-required-message="Please enter the name on your card.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-address" class="col-sm-4 col-form-label">Address</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="billing-address" name="billing_address" value="{{ old('billing_address') }}" data-required data-required-message="Please enter your billing address.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="card-element" class="col-sm-4 col-form-label">Card</label>
                                            <div class="col-sm-8">
                                                <div id="card-element"></div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <legend class="col-sm-4 col-form-label pt-0">Save payment information?</legend>
                                            <div class="col-sm-8">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="save_card" id="save-card" value="1">
                                                    <label class="form-check-label" for="save-card">
                                                    Yes
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!--
                                        <div class="form-group row">
                                            <label for="cc-number" class="col-sm-4 col-form-label">Card Number</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="cc-number" name="cc_number" value="{{ old('cc_number') }}" maxlength="16" data-required data-required-message="Please enter your card number." required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="exp-month" class="col-sm-3 col-form-label">Expiration Date</label>
                                            <div class="col-sm-3">
                                                <label for="exp-month">
                                                    <select id="exp-month" name="exp_month" class="form-control" data-required data-required-message="Please enter your card expiration month." required>
                                                        <option value="">Month</option>
                                                        <option value="01">01 - January</option>
                                                        <option value="02">02 - February</option>
                                                        <option value="03">03 - March</option>
                                                        <option value="04">04 - April</option>
                                                        <option value="05">05 - May</option>
                                                        <option value="06">06 - June</option>
                                                        <option value="07">07 - July</option>
                                                        <option value="08">08 - August</option>
                                                        <option value="09">09 - September</option>
                                                        <option value="10">10 - October</option>
                                                        <option value="11">11 - November</option>
                                                        <option value="12">12 - December</option>
                                                    </select>
                                                </label>
                                            </div>
                                            <div class="col-sm-3">
                                                <label for="exp-year">
                                                    <select id="exp-year" name="exp_month" class="form-control" data-required data-required-message="Please enter your card expiration year." required>
                                                        <option value="">Year</option>
                                                        <?php
                                                            $curyear = date('y');
                                                            $endyear = $curyear+15;
                                                            for( $i = $curyear; $i <= $endyear; $i++ ){
                                                                echo '<option value="'.$i.'"';
                                                                if(substr(old('exp_month'), 2, 2) === $i){ echo ' selected="selected"'; }
                                                                echo '>20'.$i.'</option>';
                                                            }
                                                        ?>
                                                    </select>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="ccv" class="col-sm-3 col-form-label">Security Code</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="ccv" name="ccv" value="{{ old('ccv') }}" maxlength="4" data-required data-required-message="Please enter your card security code." required>
                                            </div>
                                        </div>
                                        -->
                                    </div>
                                </div>
                            </section>
                        </div>

                        </div><!-- checkout-steps-wrap -->
                </div>

                <aside class="col-md-4 pt-4 pb-4 mb-5">
                    <div class="sticky-card">
                        <div class="section-card side-card">
                            <header>
                                <h3>Order Summary</h3>
                            </header>
                            <div class="inner">
                                <dl class="cart-summary">
                                    @foreach( $data->items as $item )
                                    <dt>
                                        <span>
                                        {{ $item->product->title }}
                                        @if( $item->variation_set )
                                        <br><small class="form-text text-muted">{{$item->variationFormatted }}</small>
                                        @endif
                                        </span>
                                        <span>x {{ $item->qty }}</span></dt><dd>${{ formatCurrency($item->price * $item->qty) }}</dd>
                                    @endforeach
                                </dl>

                                <dl class="shipping-taxes-loader">
                                    <dt>Subtotal</dt><dd>$<span class="sub-total">{{ formatCurrency($data->sub_total) }}</span></dd>
                                    <dt>
                                        Shipping &amp; Handling
                                        <span class="shipping-service-summary">
                                        @if( $data->shipping_type === 'free' && $data->minimum_order_amount <= $data->sub_total )
                                        Free Shipping
                                        @endif
                                        </span>
                                    </dt>
                                    @if( $data->shipping_type === 'free' && $data->minimum_order_amount <= $data->sub_total )
                                        <dd>$<span class="shipping">{{ $data->shipping_rates[0]->amount }}</span></dd>
                                    @else
                                        <dd>$<span class="shipping">0.00</span></dd>
                                    @endif
                                    <dt>Est. Sales Tax</dt><dd>$<span class="taxes">0.00</span></dd>
                                </dl>

                                @if( getShoppeSetting('allow_discount_codes') )
                                <div class="coupon-code-wrap">
                                    <form action="/checkout/apply-discount-code" method="post">
                                        @csrf
                                        <label for="discount-code">Coupon Code</label>
                                        <div class="discount-code-inputs">
                                            <input type="text" name="discount_code" class="text-center">
                                            <button type="submit" class="btn btn-secondary btn-sm">Apply Code</button>
                                        </div>
                                    </form>
                                </div>
                                @endif

                                <div class="shipping-address-summary hide-me">
                                </div>

                                <dl class="checkout-total">
                                    <dt>Total</dt><dd>$<span class="total">{{ formatCurrency($data->sub_total) }}</span></dd>
                                </dl>
                            </div>
                        </div>

                        <div id="checkout-errors" class="checkout-messages"></div>

                        <a href="#checkout-step-2" id="checkout-step-2-btn" class="btn btn-primary btn-block btn-lg checkout-adv-btn" data-step="2" role="button">Continue</a>
                        <button type="submit" id="checkout-submit-btn" class="btn btn-primary btn-block btn-lg visible-hide">
                        <span class="spinner-border spinner-border-sm hide" role="status" aria-hidden="true"></span>
                        Place Order
                        </button>
                    </div>
                </aside>

            </div>
        </form>
    </div>
@endsection

@prepend('headerscripts')
<script>
window.app.stripe_token = '@php echo config('shoppe.stripe_key'); @endphp';
window.app.payment_connector = '{{ $data->payment_connector }}';
window.app.tax_connector = '{{ $data->tax_connector }}';
window.app.shipping_connector = '{{ $data->shipping_connector }}';
</script>
@endprepend

@prepend('footerscripts')
<script src="https://js.stripe.com/v3/"></script>
@endprepend
