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

            @if( $data->items->count() )
            <div class="table-responsive">
                <table class="table cart-table" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="100"></th>
                            <th>Item</th>
                            <th class="text-center">Price</th>
                            <th class="text-center" width="130">Qty</th>
                            <th class="text-center">Line Total</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($data->items as $item)
                        <tr>
                            <td>
                                @if( $item->image )
                                <img src="{{ $item->image }}" alt="{{ $item->product->title }}" class="mw-100">
                                @endif
                            </td>
                            <td class="align-middle">
                                <a href="/products/{{ $item->product->slug }}">{{ $item->product->title }}</a>
                                @if( $item->variation_set )
                                <br><small class="form-text text-muted">{{$item->variationFormatted }}</small>
                                @endif
                            </td>
                            <td class="text-center align-middle">${{ formatCurrency($item->price) }}</td>
                            <td class="qty-cell text-center align-middle">
                                @if( $item->product->product_type === 'physical' )
                                <form action="/cart" method="post">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                    <input type="number" name="qty" class="form-control form-control-sm text-center cart-qty" value="{{ $item->qty }}">
                                    <button class="update-qty-btn" type="submit">
                                        <svg class="bi bi-arrow-repeat" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                          <path fill-rule="evenodd" d="M4 9.5a.5.5 0 00-.5.5 6.5 6.5 0 0012.13 3.25.5.5 0 00-.866-.5A5.5 5.5 0 014.5 10a.5.5 0 00-.5-.5z" clip-rule="evenodd"></path>
                                          <path fill-rule="evenodd" d="M4.354 9.146a.5.5 0 00-.708 0l-2 2a.5.5 0 00.708.708L4 10.207l1.646 1.647a.5.5 0 00.708-.708l-2-2zM15.947 10.5a.5.5 0 00.5-.5 6.5 6.5 0 00-12.13-3.25.5.5 0 10.866.5A5.5 5.5 0 0115.448 10a.5.5 0 00.5.5z" clip-rule="evenodd"></path>
                                          <path fill-rule="evenodd" d="M18.354 8.146a.5.5 0 00-.708 0L16 9.793l-1.646-1.647a.5.5 0 00-.708.708l2 2a.5.5 0 00.708 0l2-2a.5.5 0 000-.708z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                {{ $item->qty }}
                                @endif
                            </td>
                            <td class="align-middle text-center">${{ formatCurrency($item->price * $item->qty) }}</td>
                            <td class="align-middle">
                                <form action="/cart" method="post">
                                    @method('delete')
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                    <button type="submit" class="close" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right">Sub-total:</td>
                            <td class="text-center">${{ formatCurrency($data->sub_total) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
                @else

                    <div class="alert alert-info" role="alert">
                      Your cart is currently empty.
                    </div>

                @endif

            </div>

            <div class="col-md-3 pt-4">
            @if( $data->items->count() )
                <div class="text-center">
                    <p><a href="/{{ config('shoppe.slugs.store_landing') }}">Continue shopping</a></p>
                    <p>or</p>
                    <a href="/checkout" class="btn btn-primary btn-lg btn-block">Checkout &rarr;</a>
                </div>
            @endif
            </div>

        </div>
    </div>
@endsection
