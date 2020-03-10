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
                <h3 class="mb-4">Shipping Address</h3>
                <ul class="customer-address-edit-list">
                @foreach( $data->addresses as $key => $address )
                <li>
                    <a href="#customer-address-item-{{ $key }}" class="customer-address-edit-toggle">
                        {{ $address->name }} {{ $address->company_name }} {{ $address->address }} {{ $address->address2 }} {{ $address->city }} {{ $address->state }} {{ $address->zipcode }} {{ $address->country }}
                        @if( $address->default )
                        <span class="default">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 464c-118.664 0-216-96.055-216-216 0-118.663 96.055-216 216-216 118.664 0 216 96.055 216 216 0 118.663-96.055 216-216 216zm141.63-274.961L217.15 376.071c-4.705 4.667-12.303 4.637-16.97-.068l-85.878-86.572c-4.667-4.705-4.637-12.303.068-16.97l8.52-8.451c4.705-4.667 12.303-4.637 16.97.068l68.976 69.533 163.441-162.13c4.705-4.667 12.303-4.637 16.97.068l8.451 8.52c4.668 4.705 4.637 12.303-.068 16.97z"></path></svg>
                        </span>
                        @endif
                    </a>
                    <div id="customer-address-item-{{ $key }}" class="edit-customer-address-fields hide">
                        <form action="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/addresses/{{ $address->id }}" method="post">
                            @method('put')
                            @csrf()
                            <div class="form-group row">
                                <label for="shipping-name-{{ $key }}" class="col-sm-4 col-form-label">Name</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-name-{{ $key }}" class="form-control" name="name" value="{{ $address->name }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-company-name-{{ $key }}" class="col-sm-4 col-form-label">Company Name <small>(optional)</small></label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-company-name-{{ $key }}" class="form-control" name="company_name" value="{{ $address->company_name }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-address-{{ $key }}" class="col-sm-4 col-form-label">Address</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-address-{{ $key }}" class="form-control" name="address" value="{{ $address->address }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-address2-{{ $key }}" class="col-sm-4 col-form-label">Address 2 <small>(optional)</small></label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-address2-{{ $key }}" class="form-control" name="address2" value="{{ $address->address2 }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-city-{{ $key }}" class="col-sm-4 col-form-label">City</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-city-{{ $key }}" class="form-control" name="city" value="{{ $address->city }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-state-{{ $key }}" class="col-sm-4 col-form-label">State</label>
                                <div class="form-input col-sm-8">
                                    <select id="shipping-state-{{ $key }}" class="form-control" name="state" required>
                                        <option value=""></option>
                                    @php
                                    $states = getStates();
                                    @endphp
                                    @foreach( $states as $key => $state )
                                        <option value="{{ $key }}" {{ $key === $address->state ? 'selected="selected"' : '' }}>{{ $state }}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-zip-{{ $key }}" class="col-sm-4 col-form-label">Zip / Postal Code</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-zip-{{ $key }}" class="form-control" name="zipcode" value="{{ $address->zipcode }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-country-{{ $key }}" class="col-sm-4 col-form-label">Country</label>
                                <div class="form-input col-sm-8">
                                    <select id="shipping-country-{{ $key }}" class="form-control" name="country" required>
                                        <option value=""></option>
                                        <option value="CA" {{ $address->country === 'CA'? 'selected="selected"' : '' }}>Canada</option>
                                        <option value="US" {{ $address->country === 'US'? 'selected="selected"' : '' }}>United States</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary">Update Address</button> <a class="ml-3" href="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/addresses/{{ $address->id }}/default">Make default</a>   <a class="float-right text-danger mt-1" href="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/addresses/{{ $address->id }}/delete">Delete address</a>
                            </div>
                        </form>
                    </div>
                </li>
                @endforeach
                </ul>

                <p>
                    <a class="toggle-customer-new-shipping-address btn btn-link mb-2" role="button" href="#">Add New Shipping Address &rarr;</a>
                </p>

                <div class="new-customer-shipping-address hide">
                    <form action="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/addresses" method="post">
                        @csrf()
                            <div class="form-group row">
                                <label for="shipping-name" class="col-sm-4 col-form-label">Name</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-name" class="form-control" name="name" value="{{ old('name') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-company-name" class="col-sm-4 col-form-label">Company Name <small>(optional)</small></label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-company-name" class="form-control" name="company_name" value="{{ old('company_name') }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-address" class="col-sm-4 col-form-label">Address</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-address" class="form-control" name="address" value="{{ old('address') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-address2" class="col-sm-4 col-form-label">Address 2 <small>(optional)</small></label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-address2" class="form-control" name="address2" value="{{ old('address2') }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-city" class="col-sm-4 col-form-label">City</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-city" class="form-control" name="city" value="{{ old('city') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-state" class="col-sm-4 col-form-label">State</label>
                                <div class="form-input col-sm-8">
                                    <select id="shipping-state" class="form-control" name="state" required>
                                        <option value=""></option>
                                    @php
                                    $states = getStates();
                                    @endphp
                                    @foreach( $states as $key => $state )
                                        <option value="{{ $key }}" {{ $key === old('state')? 'selected="selected"' : '' }}>{{ $state }}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-zip" class="col-sm-4 col-form-label">Zip / Postal Code</label>
                                <div class="form-input col-sm-8">
                                    <input type="text" id="shipping-zip" class="form-control" name="zipcode" value="{{ old('zipcode') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-country" class="col-sm-4 col-form-label">Country</label>
                                <div class="form-input col-sm-8">
                                    <select id="shipping-country" class="form-control" name="country" required>
                                        <option value=""></option>
                                        <option value="CA" {{ old('country') === 'CA'? 'selected="selected"' : '' }}>Canada</option>
                                        <option value="US" {{ old('country') === 'US'? 'selected="selected"' : '' }}>United States</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="shipping-default" class="col-sm-4 col-form-label">Make Default</label>
                                <div class="form-input col-sm-8">
                                    <input type="checkbox" id="shipping-default" name="default" value="1" required> Yes
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Add Shipping Address</button>
                    </form>
                </div>
            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
    </div>
@endsection
