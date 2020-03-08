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

                <ul class="customer-address-edit-list">
                @foreach( $data->addresses as $key => $address )
                <li>
                    <a href="#customer-address-item-{{ $key }}" class="customer-address-edit-toggle">{{ $address->name }} {{ $address->company_name }} {{ $address->address }} {{ $address->address2 }} {{ $address->city }} {{ $address->state }} {{ $address->zipcode }} {{ $address->country }}</a>
                    <div id="customer-address-item-{{ $key }}" class="edit-customer-address-fields hide">
                        <form action="{{ config('shoppe.slug.customer_account', 'customer-account') }}/addresses/{{ $address->id }}" method="post">
                            @method('put')
                            @csrf()
                            <button type="submit" class="btn btn-primary">Update Address</button>
                        </form>
                    </div>
                </li>
                @endforeach
                </ul>

                <h3>Add New Address</h3>


            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
    </div>
@endsection
