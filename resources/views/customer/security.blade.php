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

                <div class="section-card">
                    <header>
                        <h3>Change Password</h3>
                    </header>
                    <div class="inner">
                    <form action="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/security/password" method="post">
                        @csrf()
                        <div class="form-group row">
                            <label for="current-password" class="col-sm-4 col-form-label">Current Password</label>
                            <div class="form-input col-sm-6">
                                <input type="password" id="current-password" class="form-control" name="current_password" value="" required>
                            </div>
                             <div class="form-input col-sm-2">

                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="password" class="col-sm-4 col-form-label">New Password</label>
                            <div class="form-input col-sm-6">
                                <input type="password" id="password" class="form-control" name="password" value="" required>
                            </div>
                            <div class="form-input col-sm-2">

                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="confirm-password" class="col-sm-4 col-form-label">Confirm Password</label>
                            <div class="form-input col-sm-6">
                                <input type="password" id="confirm-password" class="form-control" name="password_confirmation" value="" required>
                            </div>
                             <div class="form-input col-sm-2">

                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Change Password</button>

                    </form>
                    </div>
                </div>

            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
    </div>
@endsection
