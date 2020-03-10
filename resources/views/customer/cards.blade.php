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
        <div class="row">
            <div class="col-md-9 pt-4">

                @if( $data->payment_types['success'] )
                <ul class="payment-types-list">
                @foreach( $data->payment_types['items'] as $key => $paymentType )
                    <li>
                        <a href="#customer-payment-item-{{ $key }}" class="customer-payment-edit-toggle">
                        {{ $paymentType['last_four'] }} / {{ $paymentType['card_brand'] }} {{ $paymentType['zip'] }} &mdash; Expires: {{ $paymentType['exp_month'] }}-{{ $paymentType['exp_year'] }}
                        @if( $paymentType['default'] )
                        <span class="default">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 464c-118.664 0-216-96.055-216-216 0-118.663 96.055-216 216-216 118.664 0 216 96.055 216 216 0 118.663-96.055 216-216 216zm141.63-274.961L217.15 376.071c-4.705 4.667-12.303 4.637-16.97-.068l-85.878-86.572c-4.667-4.705-4.637-12.303.068-16.97l8.52-8.451c4.705-4.667 12.303-4.637 16.97.068l68.976 69.533 163.441-162.13c4.705-4.667 12.303-4.637 16.97.068l8.451 8.52c4.668 4.705 4.637 12.303-.068 16.97z"></path></svg>
                        </span>
                        @endif
                        </a>
                        <div id="customer-payment-item-{{ $key }}" class="edit-customer-payment-fields hide">
                            <form action="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/cards/{{ $paymentType['id'] }}" method="post">
                                @csrf
                                @method('put')

                                <div class="form-group row">
                                    <label for="payment-month-{{ $key }}" class="col-sm-4 col-form-label">Expiration Month</label>
                                    <div class="form-input col-sm-8">
                                        <input type="number" id="payment-month-{{ $key }}" class="form-control" name="exp_month" value="{{ $paymentType['exp_month'] }}" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="payment-year-{{ $key }}" class="col-sm-4 col-form-label">Expiration Year</label>
                                    <div class="form-input col-sm-8">
                                        <input type="number" id="payment-year-{{ $key }}" class="form-control" name="exp_year" value="{{ $paymentType['exp_year'] }}" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="payment-zip-{{ $key }}" class="col-sm-4 col-form-label">Zip / Postal Code</label>
                                    <div class="form-input col-sm-8">
                                        <input type="text" id="payment-zip-{{ $key }}" class="form-control" name="zipcode" value="{{ $paymentType['zip'] }}" required>
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary">Update Payment</button> <a class="ml-3" href="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/cards/{{ $paymentType['id'] }}/default">Make default</a>
                                    <a class="float-right text-danger mt-1 btn btn-link " href="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/cards/{{ $paymentType['id'] }}/delete">Delete</a>
                                </div>

                            </form>
                        </div>
                    </li>
                @endforeach
                </ul>
                @endif

                <p>
                    <a class="toggle-customer-new-payment btn btn-link mb-2" role="button" href="#">Add New Payment Type &rarr;</a>
                </p>

                <div class="new-customer-payment hide">
                    <form id="new-payment-type-form" action="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/cards" method="post">
                        @csrf
                        <div class="form-group row">
                            <label for="card-element" class="col-sm-4 col-form-label">Card</label>
                            <div class="col-sm-8">
                                <div id="card-element"></div>
                            </div>
                        </div>
                        <div class="form-footer">
                            <div id="checkout-errors" class="checkout-messages"></div>
                            <button type="submit" class="btn btn-primary stripe-payment-type-btn">Add Payment Type</button>
                        </div>
                    </form>
                </div>

            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
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
