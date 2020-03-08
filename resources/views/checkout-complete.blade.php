@extends('neutrino::templates.header-footer')
@section('title', 'Order Receipt | ')
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
                <h1>Order Receipt</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 pt-4 mb-4">

                <p>{{ $data->user->name }}, thanks for your purchase! Your order number is #<strong>{{ $data->id }}</strong></p>

                <p>Check your email, {{ $data->user->email }}, for updates.</p>

                <p>
                Subtotal: ${{ $data->items_total }}<br>
                Shipping: ${{ $data->shipping_amount }}<br>
                Taxes: ${{ $data->tax_amount }}<br>
                <strong>Total Charged:</strong> ${{ $data->linesTotal + $data->shipping_amount + $data->tax_tmount }}
                </p>

                <h3>You Ordered</h3>

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
                        @foreach($data->orderLines as $item)
                            <tr>
                                <td>{{ $item->product->title }}</td>
                                <td>${{ formatCurrency($item->price) }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>${{ formatCurrency($item->price * $item->qty) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>


            </div>

            <div class="col-md-4 pt-4">

                <h3>Shipping To</h3>
                <p>
                {{ $data->shippingAddress->name }}<br>
                @if($data->shippingAddress->company_name)
                {{ $data->shippingAddress->company_name }}<br>
                @endif
                {{ $data->shippingAddress->address }}<br>
                @if($data->shippingAddress->address2)
                {{ $data->shippingAddress->address2 }}<br>
                @endif
                {{ $data->shippingAddress->city }}, {{ $data->shippingAddress->state }} {{ $data->shippingAddress->zipcode }}<br>
                </p>

            </div>

        </div>
    </div>
@endsection
