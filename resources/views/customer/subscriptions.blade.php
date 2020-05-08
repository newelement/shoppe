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

                <table class="table" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th class="text-left">Subscription</th>
                            <th width="120">Status</th>
                            <th width="120"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @if( !$data->subscriptions )
                        <tr><td colspan="3">You do not have any subscriptions at this time.</td></tr>
                    @endif
                    @foreach( $data->subscriptions as $sub )
                        <tr class="stripe-status-{{ $sub->stripe_status }}">
                            <td>
                            {{ $sub->name }}
                            @if( $sub->stripe_status === 'trialing' )
                            - Trial ends on {{ \Carbon::create($sub->trial_ends_at)->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}
                            @endif
                            </td>
                            <td>{{ $sub->stripe_status }}</td>
                            <td class="text-right">
                                @if(
                                    $sub->stripe_status === 'trialing' ||
                                    $sub->stripe_status === 'active' ||
                                    $sub->stripe_status === 'incomplete_expired' ||
                                    $sub->stripe_status === 'incomplete' ||
                                    $sub->stripe_status === 'past_due' ||
                                    $sub->stripe_status === 'unpaid'
                                    )
                                <form action="/{{ config('shoppe.slug.customer_account', 'customer-account') }}/subscriptions/{{ $sub->stripe_id }}/cancel" method="post">
                                    @csrf
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>

            <aside class="col-md-3 pt-4">
                @include('shoppe::customer.partials.customer-nav')
            </aside>

        </div>
    </div>
@endsection
