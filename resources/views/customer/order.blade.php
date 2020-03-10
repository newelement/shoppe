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
                <h1>{{ $data->title }} #{{ $data->order->id }}</h1>
                <p>
                    <a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}">&larr; Back to orders</a>
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-9 pt-4">

                <div class="section-card">
                    <header>
                        <h3>Order Details</h3>
                    </header>
                    <div class="inner">

                        <div class="order-header-top mb-4">
                            <div class="order-header-top-item">
                                <p>
                                <strong>Order Total:</strong> ${{ getOrderTotal( $data->order ) }}
                                <hr>
                                <strong>Items Total:</strong> ${{ $data->order->items_total }}<br>
                                <strong>Shipping:</strong> ${{ $data->order->shipping_amount }}<br>
                                <strong>Sales Tax:</strong> ${{ $data->order->tax_amount }}
                                </p>
                            </div>
                            <div class="order-header-top-item">
                                <strong>Ordered By:</strong> {{ $data->order->user->name }}<br>{{ $data->order->user->email }}
                            </div>
                            <div class="order-header-top-item">
                                <strong>Order Date:</strong> {{ $data->order->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}
                            </div>
                        </div>

                        <div class="order-shipping-details mb-4">
                            <h4>Shipping To</h4>
                            <address class="shipping-address">
                                {{ $data->order->shippingAddress->name }}<br>
                                @if( $data->order->shippingAddress->company_name )
                                {{ $data->order->shippingAddress->company_name }}<br>
                                @endif
                                {{ $data->order->shippingAddress->address }}<br>
                                @if( $data->order->shippingAddress->address2 )
                                {{ $data->order->shippingAddress->address2 }}<br>
                                @endif
                                {{ $data->order->shippingAddress->city }}, {{ $data->order->shippingAddress->state }} {{ $data->order->shippingAddress->zipcode }}<br>
                            </address>

                            <div class="order-payment-details mb-4">
                                <h4>Payment Details</h4>
                                <div class="payment-details">
                                &mdash; {{ $data->order->last_four }} / {{ $data->order->card_brand }}
                                </div>
                            </div>

                            @if( !$data->order->disabled() )

                                <h4>Tracking</h4>

                                <div class="tracking-label-info">
                                    @if( !$data->order->tracking_number )
                                    <p>No tracking information available.</p>
                                    @endif
                                    @if( $data->order->tracking_number )
                                        <strong>Carrier:</strong> {{ $data->order->carrier }} {{ $data->order->shipping_service }}<br>
                                        <strong>Tracking #:</strong> <a href="{{ $data->order->tracking_url }}" target="_blank">{{$data->order->tracking_number}}</a><br>
                                    @endif
                                </div>

                            @endif

                        </div>

                    </div>
                </div>

                <div class="section-card">
                    <header>
                        <h3>Items</h3>
                    </header>
                    <div class="inner">
                        <div class="table-responsive">
                            <table class="table table-borderless cart-table" cellpadding="0" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="40">#</th>
                                        <th width="60"></th>
                                        <th class="text-left">
                                            Item
                                        </th>
                                        <th width="120">Price</th>
                                        <th class="text-center">QTY</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach( $data->order->orderLines as $key => $line )
                                        <tr>
                                            <td class="text-center">{{ $key+1 }}</td>
                                            <td>
                                            @if( $line->image )
                                                <img src="{{ $line->image }}" alt="{{ $line->product->title }}" class="mw-100">
                                            @endif
                                            </td>
                                            <td>
                                                <a href="/products/{{ $line->product->slug }}">{{ $line->product->title }}</a>
                                                @if( $line->variation )
                                                <br>{{ $line->variation }}
                                                @endif
                                            </td>
                                            <td class="text-right"> ${{ $line->price }} </td>
                                            <td class="text-center">{{ $line->qty }}</td>
                                            <td class="text-center">
                                            @if( $line->status === 4 )
                                                Refunded
                                            @endif
                                            </td>
                                        </tr>
                                        @foreach( $line->credits as $credit )
                                        <tr class="tr-line-credit">
                                            <td></td>
                                            <td></td>
                                            <td>{{ $credit->notes }} </td>
                                            <td class="text-right">
                                                - ${{ $credit->amount }}<br>
                                                <small>
                                                <strong>Includes</strong>
                                                Taxes: {{ $credit->tax_amount }} /
                                                Shipping: {{ $credit->shipping_amount }}
                                                </small>
                                            </td>
                                            <td></td>
                                            <td class="text-center"></td>
                                        </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                                <tfoot>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <header>
                        <h3>Transactions</h3>
                    </header>
                    <div class="inner">
                    <div class="responsive-table">
                        <table cellpadding="0" cellspacing="0" class="table table-borderless">
                            <thead>
                                <tr>
                                    <th class="text-center" width="40">#</th>
                                    <th class="text-left">ID</th>
                                    <th>Credit</th>
                                    <th>Debit</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach( $data->order->transactions as $key => $trans )
                                <tr>
                                    <td class="text-center">{{ $key+1 }}</td>
                                    <td>
                                    {{ $trans->transaction_ref_id }}
                                    </td>
                                    <td class="text-right">
                                        @if( $trans->transaction_type === 'credit' )
                                        <span class="credit-tag">${{ $trans->amount }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if( $trans->transaction_type === 'debit' )
                                        <span class="debit-tag">${{ $trans->amount }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                    <small>{{ $trans->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}</small>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>

            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
    </div>
@endsection
