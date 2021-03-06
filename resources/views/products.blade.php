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
            <div class="col-md-12">
                @include('shoppe::partials.breadcrumbs')
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row">

            @include('shoppe::partials.product-nav')

            <div class="col-md-9 pt-2 pb-4">
                <div class="row row-cols-4">
                @foreach( $data->categories as $term )
                    @if( !isEmptyProductCategory($term) )
                    <div class="col category-col mb-3">
                        <div class="inner" style="background-image: url('{{ $term->featuredImage? $term->featuredImage->file_path: ''}}')">
                            <a href="{{ $term->url() }}{{ productQueryString() }}">{{ $term->title }}</a>
                        </div>
                    </div>
                    @endif
                @endforeach
                </div>

                @if( $data->products )
                <div class="row row-cols-4">
                    @foreach( $data->products as $product )
                    <div class="col product-col mb-3">
                        <div class="inner">
                            <a href="{{ url()->current() }}{{ $product->url() }}">
                                <img src="{{ $product->featuredImage->file_path }}" alt="{{ $product->title }}">
                            </a>
                            <a href="{{ url()->current() }}{{ $product->url() }}">{{ $product->title }}</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if( $data->products )
                {{ $data->products->appends(request()->input())->links() }}
                @endif

            </div>

        </div>
    </div>
@endsection
