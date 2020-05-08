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
        <div class="row">
            <div class="col-md-9 pt-4">

                @if( $data->orders->count() )
                <div class="table-responsive">
                    <table class="table cart-table" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="100">Order ID</th>
                                <th>Order Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th width="60">View</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($data->orders as $order)
                            <tr>
                                <td><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}/orders/{{ $order->id }}">{{ $order->id }}</a></td>
                                <td>{{ $order->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}</td>
                                <td>${{ getOrderTotal( $order ) }}</td>
                                <td>{{ $order->status_formatted }}</td>
                                <td><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}/orders/{{ $order->id }}">View</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination-links">
                    {{ $data->orders->appends($_GET)->links() }}
                </div>

                @else

                <div class="alert alert-info" role="alert">
                    You do not have any orders at this time.
                </div>

                @endif

            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
    </div>
@endsection
