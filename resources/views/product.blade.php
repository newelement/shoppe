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
            <div class="col-md-6 pt-4 pb-4">

                <div class="product-images">
                    <div class="product-image-selected">
                    @if( $data->featuredImage )
                    @php
                    $featuredImageSizes = getImageSizes($data->featuredImage->file_path);
                    @endphp
                    <a href="{{$featuredImageSizes['large']}}"><img src="{{$featuredImageSizes['medium']}}" alt="{{ $data->title }}"></a>
                    @endif
                    </div>
                    <ul class="product-images-thumbs">
                        @if( $data->featuredImage )
                        <li>
                            <a class="product-image-thumb active" href="{{$featuredImageSizes['large']}}" data-medium="{{ $featuredImageSizes['medium'] }}">
                                <img src="{{$data->featuredImage->file_path}}" alt="{{ $data->title }}">
                            </a>
                        </li>
                        @endif
                        @php

                        @endphp
                        @foreach( $data->variations as $variation )
                        @php
                        $imageSizes = getImageSizes($variation->image);
                        @endphp
                        @if( $imageSizes )
                        <li id="variation-image-{{ $variation->id }}">
                            <a class="product-image-thumb" href="{{$imageSizes['large']}}" data-medium="{{ $imageSizes['medium'] }}">
                                <img src="{{ $variation->image }}" alt="{{ $data->title }}">
                            </a>
                        </li>
                        @endif
                        @endforeach
                    </ul>
                </div>

            </div>

            <div class="col-md-6">
                <div class="product-details pt-4">
                    <h1 class="product-title">{{ $data->title }}</h1>

                    <p id="mfg-part-number">{{ $data->mfg_part_number }}</p>

                    {!! getShortContent() !!}

                    <form action="/cart" method="post">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $data->id }}">
                        <input id="variation-id" type="hidden" name="variation_id" value="">

                        @php
                        $productAttributes = getProductAttributes();
                        @endphp

                        @foreach( $productAttributes as $attr )
                        <p>
                            <label for="attr-{{ $attr->id }}">{{ $attr->getProductAttribute->name }}</label>
                            <input type="hidden" name="product_attributes[{{$attr->attribute_id}}]" value="{{ $attr->getProductAttribute->name }}">
                            <select name="variations[{{$attr->attribute_id}}]" id="attr-{{ $attr->id }}" class="form-control product-attribute-list">
                                <option value="">Choose ...</option>
                                @foreach( $attr->jsonValues() as $value )
                                <option value="{{ $value }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </p>
                        @endforeach

                        <p class="product-price">Price: <span id="price">{{ productPrice($data->id) }}</span></p>
                        @if( $data->product_type === 'physical' )
                        <p class="product-stock">Stock: <span id="stock">{{ productStock($data->id) }}</span></p>
                        @endif
                        <div id="shoppe-product-alert" class="alert d-none mb-3" role="alert"></div>

                        @if( $data->product_type === 'physical' )
                        <p>
                        <input type="number" name="qty" value="1" class="form-control">
                        </p>
                        @endif

                        <p>
                            <button type="submit" class="btn btn-primary btn-lg add-to-cart-btn" {{ $productAttributes->count() ? 'data-has-attributes="true"' : '' }}>Add to cart</button>
                        </p>
                    </form>

                    {!! getContent() !!}

                    {!! getSpecsContent() !!}
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
    var variations = {!! $data->variations->toJson() !!};
    </script>
@endsection
