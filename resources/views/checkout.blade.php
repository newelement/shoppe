@extends('neutrino::templates.header-footer')
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
                                <div class="checkout-step current"><span class="checkout-step-number">1</span> Shipping <span class="step-div"></span></div>
                                <div class="checkout-step"><span class="checkout-step-number">2</span> Payment <span class="step-div"></span></div>
                                <div class="checkout-step"><span class="checkout-step-number">3</span> Review</div>
                            </div>
                        </div>

                        <div class="checkout-steps-wrap">
                            <div id="checkout-step-1" class="checkout-form-section current shipping-form-section">
                                <div class="payment-section-header">
                                    <h2>Shipping</h2>
                                </div>
                                <div id="shipping-address-fields" class="payment-section-fields">

                                    @php
                                    $shippingAddresses = $data->shipping_addresses->count() ? true : false;
                                    @endphp

                                    @if($shippingAddresses)
                                    <div class="form-group row">
                                        <legend class="col-sm-3 col-form-label pt-0">Shipping Address</legend>
                                        <div class="col-sm-9">
                                            <!--
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="billing_address_option" id="gridRadios1" value="option1" checked>
                                                <label class="form-check-label" for="gridRadios1">
                                                First radio
                                                </label>
                                            </div>
                                            -->

                                            <div class="form-check">
                                                <hr>
                                                <input class="form-check-input shipping-address-option" type="radio" name="shipping_address_option" id="new-shipping-address" value="new_shipping_address">
                                                <label class="form-check-label" for="new-shipping-address">
                                                New Address
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <input class="form-check-input shipping-address-option" type="radio" name="shipping_address_option" id="new-shipping-address" value="new_shipping_address" checked style="display: none">
                                    @endif

                                    <div class="new-shipping-address" {{ $shippingAddresses? 'style="display: none"' : '' }}>

                                        <div class="form-group row">
                                            <label for="shipping-name" class="col-sm-3 col-form-label">Name</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="shipping-name" name="shipping_name" data-required data-required-message="Please enter your shipping name." required value="{{ old('shipping_name') }}">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="shipping-address" class="col-sm-3 col-form-label">Address</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control shipping-address-field shipping-address" id="shipping-address" name="shipping_address" value="{{ old('shipping_address') }}" data-required data-required-message="Please enter your shipping address." required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="shipping-address2" class="col-sm-3 col-form-label">Address 2</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="shipping-address2" name="shipping_address2" value="{{ old('shipping_address2') }}">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="shipping-city" class="col-sm-3 col-form-label">City</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control shipping-address-field" id="shipping-city" name="shipping_city" value="{{ old('shipping_city') }}" data-required data-required-message="Please enter your shipping city." required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="shipping-state" class="col-sm-3 col-form-label">State / Providence</label>
                                            <div class="col-sm-9">
                                                <select class="form-control shipping-address-field" id="shipping-state" name="shipping_state" data-required data-required-message="Please enter your shipping state." required>
                                                    <option value="">Choose state / providence ....</option>
                                                @php $states = getStates();  @endphp
                                                @foreach( $states as $key => $value )
                                                    <option value="{{ $key }}" {{ old('shipping_state') === $key? 'selected="selected"' : '' }}>{{ $value }}</option>
                                                @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="shipping-zip-code" class="col-sm-3 col-form-label">Zip / Postal Code</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control shipping-address-field" id="shipping-zip-code" name="shipping_zipcode" value="{{ old('shipping_zipcode') }}" data-required data-required-message="Please enter your shipping zip code." required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="shipping-country" class="col-sm-3 col-form-label">Country</label>
                                            <div class="col-sm-9">
                                                <select class="form-control shipping-address-field" id="shipping-country" name="shipping_country" data-required data-required-message="Please enter your shipping country." required>
                                                    <option value="">Choose country ....</option>
                                                    <option value="CA">Canada</option>
                                                    <option value="US">United States</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <legend class="col-sm-3 col-form-label pt-0">Save Shipping Address?</legend>
                                            <div class="col-sm-9">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="save_shipping" id="save-shipping-address" value="1">
                                                    <label class="form-check-label" for="save-shipping-address">
                                                    Yes
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <h4>Shipping Options</h4>
                                <ul class="shipping-rates-list">
                                    <li class="enter-shipping-address-for-rates">Enter your shipping address to see rates.</li>
                                </ul>

                            </div>

                            <div id="checkout-step-2" class="checkout-form-section billing-form-section">
                                <div class="payment-section-header">
                                    <h2>Billing</h2>
                                </div>
                                <div id="billing-address-fields" class="payment-section-fields">

                                    <div class="form-group row">
                                        <label for="email" class="col-sm-3 col-form-label">Email</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="email" name="email" value="{{ old('email', auth()->user()? auth()->user()->email : '' ) }}" data-required data-required-message="Please enter your email." required>
                                            <span class="form-text text-muted">We use your email for your order receipt.</span>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <legend class="col-sm-3 col-form-label pt-0">Billing Address</legend>
                                        <div class="col-sm-9">
                                            <div class="form-check">
                                                <input class="form-check-input billing-address-option" id="same-as-shipping" type="checkbox" name="billing_address_option" value="same_shipping">
                                                <label class="form-check-label" for="same-as-shipping">
                                                Same as shipping?
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!--
                                    <div class="new-billing-address">

                                        <div class="form-group row">
                                            <label for="billing-address" class="col-sm-3 col-form-label">Address</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="billing-address" name="billing_address" value="{{ old('billing_address') }}" data-required data-required-message="Please enter your billing address.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-address2" class="col-sm-3 col-form-label">Address 2</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="billing-address2" name="billing_address2" value="{{ old('billing_address2') }}">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-city" class="col-sm-3 col-form-label">City</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="billing-city" name="billing_city" value="{{ old('billing_city') }}" data-required data-required-message="Please enter your billing city.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-state" class="col-sm-3 col-form-label">State / Providence</label>
                                            <div class="col-sm-9">
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
                                            <label for="billing-zip-code" class="col-sm-3 col-form-label">Zip / Postal Code</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="billing-zip-code" name="billing_zipcode" value="{{ old('billing_zipcode') }}" data-required data-required-message="Please enter your billing zip code.">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="billing-country" class="col-sm-3 col-form-label">Country / Region</label>
                                            <div class="col-sm-9">
                                                <select class="form-control" id="billing-country" name="billing_country">
                                                    <option value="">Choose country ....</option>
                                                    <option value="CA">Canada</option>
                                                    <option value="US">United States</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                    -->

                                    <div class="payment-section-header">
                                        <h2>Payment</h2>
                                    </div>

                                    <div id="payment-fields" class="payment-section-fields">

                                        <div class="form-group row">
                                            <label for="cc-name" class="col-sm-3 col-form-label">Name on Card</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="cc-name" name="cc_name" value="{{ old('cc_name') }}" data-required data-required-message="Please enter the name on your card." required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="card-element" class="col-sm-3 col-form-label">Card</label>
                                            <div class="col-sm-9">
                                                <div id="card-element"></div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <legend class="col-sm-3 col-form-label pt-0">Save Card?</legend>
                                            <div class="col-sm-9">
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
                                            <label for="cc-number" class="col-sm-3 col-form-label">Card Number</label>
                                            <div class="col-sm-9">
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
                                <div class="checkout-set-actions">
                                    <a href="#checkout-step-1" class="text-secondary checkout-prev-btn" data-step="1" role="button">&larr; Back to Shipping</a>
                                </div>
                            </div>

                            <div id="checkout-step-3" class="checkout-form-section payment-section">
                                <div class="payment-section-header">
                                    <h2>Review</h2>
                                </div>
                                <div id="review-fields" class="payment-section-fields">
                                    @if( $data->items->count() )

                                    <div class="row">
                                        <div class="cell col-6">
                                            <h4>Shipping To:</h4>
                                            <p id="checkout-shipping-review" class="checkout-review-address">
                                            </p>
                                        </div>
                                        <div class="cell col-6">
                                            <h4>Billing To:</h4>
                                            <p id="checkout-billing-review" class="checkout-review-address">
                                            </p>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm cart-table" cellpadding="0" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Price</th>
                                                    <th>Qty</th>
                                                    <th>Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($data->items as $item)
                                                <tr>
                                                    <td><a href="/products/{{ $item->product->slug }}">{{ $item->product->title }}</a></td>
                                                    <td>${{ formatCurrency($item->price) }}</td>
                                                    <td>{{ $item->qty }}</td>
                                                    <td>${{ formatCurrency($item->price * $item->qty) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                        <div class="alert alert-info" role="alert">
                                            Your cart is currently empty.
                                        </div>
                                    @endif

                                </div>
                                <div class="checkout-set-actions">
                                    <a href="#checkout-step-2" class="text-secondary checkout-prev-btn" data-step="2" role="button">&larr; Back to Payment</a>
                                </div>
                            </div>
                        </div><!-- checkout-steps-wrap -->
                </div>

                <aside class="col-md-4 pt-4 pb-4 mb-5">
                    <div class="order-summary-block">
                        <h3>Order Summary</h3>

                        <dl class="shipping-taxes-loader">
                            <dt>Subtotal: </dt><dd>$<span class="sub-total">{{ formatCurrency($data->sub_total) }}</span></dd>
                            <dt>Shipping &amp; Handling: </dt><dd>$<span class="shipping">0.00</span></dd>
                            <dt>Est. Sales Taxes: </dt><dd>$<span class="taxes">0.00</span></dd>
                        </dl>

                        <dl class="checkout-total">
                            <dt>Total: </dt><dd>$<span class="total">{{ formatCurrency($data->sub_total) }}</span></dd>
                        </dl>

                        <ul class="checkout-messages" style="display: none"></ul>
                        <div id="card-errors" role="alert"></div>

                        <a href="#checkout-step-2" id="checkout-step-2-btn" class="btn btn-primary btn-block checkout-adv-btn" data-step="2" role="button">Continue to &rarr; Payment</a>
                        <a href="#checkout-step-3" id="checkout-step-3-btn" class="btn btn-primary btn-block checkout-adv-btn visible-hide" data-step="3" role="button">Continue to &rarr; Review</a>

                        <button type="submit" id="checkout-submit-btn" class="btn btn-primary btn-block visible-hide">Submit Order</button>

                    </div>
                </aside>

            </div>
        </form>
    </div>
@endsection

@prepend('headerscripts')
<script>
window.app.stripe_token = '@php echo config('shoppe.stripe_key'); @endphp';
</script>
@endprepend

@prepend('footerscripts')
<script src="https://js.stripe.com/v3/"></script>
@endprepend
