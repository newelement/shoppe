@extends('neutrino::admin.template.header-footer')
@section('title', 'Edit Product | ')
@section('content')
<form action="/admin/product/{{ $product->id }}" method="post" enctype="multipart/form-data">

    <div class="container">
        <div class="content">
            <h2>Edit Product <a class="headline-btn" href="/admin/product" role="button">Create New Product</a></h2>
            @csrf
            <div class="form-row">
                <label class="label-col" for="title">Title</label>
                <div class="input-col">
                    <input id="title" class="to-slug" type="text" name="title" value="{{ old('title', $product->title) }}">
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="slug">Slug</label>
                <div class="input-col">
                    <input id="slug" class="slug-input" type="text" name="slug" value="{{ old('slug', $product->slug) }}">
                </div>
            </div>

            <div class="form-row">
                <ul class="form-tabs">
                    <li><a href="#taba" class="active">Short Description</a></li>
                    <li><a href="#tabb">Full Description</a></li>
                    <li><a href="#tabc">Specs</a></li>
                </ul>
                <div class="tabs-content">
                    <div id="taba" class="tab-content active">
                        <textarea id="short-content" class="editor" name="short_content">{{ old('short_content', html_entity_decode($product->short_content) ) }}</textarea>
                    </div>
                    <div id="tabb" class="tab-content">
                        <textarea id="content" class="editor" name="content">{{ old('content', html_entity_decode($product->content) ) }}</textarea>
                    </div>
                    <div id="tabc" class="tab-content">
                        <textarea id="specs" class="editor" name="specs">{{ old('specs', html_entity_decode($product->specs) ) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="product-type">Product Type</label>
                <div class="input-col">
                    <div class="select-wrapper">
                        <select id="product-type" name="product_type">
                            <option value="physical" {{ old('product_type', $product->product_type) === 'physical' ? 'selected="selected"' : '' }}>Physical</option>
                            @if( $subscriptions )
                            <option value="subscription" {{ old('product_type', $product->product_type) === 'subscription' ? 'selected="selected"' : '' }}>Subscription</option>
                            @endif
                            <option value="role" {{ old('product_type', $product->product_type) === 'role' ? 'selected="selected"' : '' }}>User Role</option>
                            <option value="download" {{ old('product_type', $product->product_type) === 'download' ? 'selected="selected"' : '' }}>Downloadable</option>
                        </select>
                    </div>
                </div>
            </div>

            @if( $subscriptions )
            <div id="product-subscription-row" @if( $product->product_type !== 'subscription' ) style="display: none;" @endif>
                <div class="form-row">
                    <label class="label-col">Subscription</label>
                    <div class="input-col">
                        <div class="select-wrapper">
                            <select id="subscription-id" name="subscription_id">
                                <option value="">Choose...</option>
                                @foreach( $subscriptions['plans'] as $subscription )
                                <option value="{{$subscription['id']}}" data-amount="{{$subscription['amount']}}" {{ $product->subscription_id === $subscription['id'] ? 'selected="selected"' : '' }}>{{$subscription['name']}} - {{$subscription['amount']}} {{$subscription['interval']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <label class="label-col" for="tax-inclusive">Tax Inclusive</label>
                    <div class="input-col has-checkbox">
                        <label><input type="checkbox" id="tax-inclusive" name="tax_inclusive" value="1" {{ $product->tax_inclusive ? 'checked' : '' }}> Yes</label>
                    </div>
                    <span class="input-notes"><span class="note">Will the subscription plan price include taxes?</span></span>
                </div>
            </div>
            @endif

            <div id="product-file-row" class="form-row" @if( $product->product_type !== 'download' ) style="display: none;" @endif>
                <label class="label-col">File</label>
                <div class="input-col row">
                    <div class="input-group">
                        <span class="input-group-btn">
                            <a class="lfm-file" data-lfm data-input="product-file" data-preview="lfm-product">
                                <i class="fas fa-file"></i> Choose file
                            </a>
                        </span>
                    </div>
                    <input id="product-file" class="file-list-input" value="" type="hidden" name="product_file">
                </div>
            </div>

            <div id="product-role-row" class="form-row" @if( $product->product_type !== 'rolw' ) style="display: none;" @endif>
                <label class="label-col" for="role-id">Role</label>
                <div class="input-col">
                    <div class="select-wrapper">
                        <select id="role-id" name="role_id">
                            <option value="">Choose...</option>
                            @foreach( $roles as $role )
                                <option value="{{ $role->id }}" {{ $product->role_id === $role->id ? 'selected="selected"' : '' }}>{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="cost">Cost</label>
                <div class="input-col">
                    <input type="number" id="cost" name="cost" value="{{ old('cost', $product->cost) }}" step="0.01">
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="price">Price</label>
                <div class="input-col">
                    <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01">
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="sale-price">Sale Price</label>
                <div class="input-col">
                    <input type="number" id="sale-price" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" step="0.01">
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="taxable">Taxable</label>
                <div class="input-col has-checkbox">
                    <label><input type="checkbox" id="taxable" name="is_taxable" value="1" {{ $product->is_taxable ? 'checked' : '' }}> Yes</label>
                </div>
            </div>

            @if( $tax_codes )
                <div class="form-row">
                    <label class="label-col" for="tax-code">Tax Code</label>
                    <div class="input-col has-choices">
                        <select id="tax-code" class="js-choice" name="tax_code">
                            <option value="">Choose...</option>

                            @foreach( $tax_codes as $tax_code )
                            <option value="{{$tax_code['tax_code']}}" {{ $product->tax_code === $tax_code['tax_code'] ? 'selected="selected"' : '' }}>{{$tax_code['name']}}: {{$tax_code['description']}}</option>
                            @endforeach
                        </select>

                    </div>
                </div>
                @endif

            <div class="form-row">
                <label class="label-col" for="contact-price">Contact for Pricing</label>
                <div class="input-col has-checkbox">
                    <label><input type="checkbox" id="contact-price" name="contact_price" value="1" {{ $product->contact_price ? 'checked' : '' }}> Yes</label>
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="sku">SKU</label>
                <div class="input-col">
                    <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="mfg-part-number">MFG Part Number</label>
                <div class="input-col">
                    <input type="text" id="mfg-part-number" name="mfg_part_number" value="{{ old('mfg_part_number', $product->mfg_part_number) }}">
                </div>
            </div>

            <div class="from-tab-row">
                <ul class="form-tabs">
                    <li><a href="#tab1" class="active">Stock</a></li>
                    <li><a href="#tab2">Shipping</a></li>
                    <li><a href="#tab3">Attributes</a></li>
                    <li><a href="#tab4">Variations</a></li>
                </ul>
                <div class="tabs-content">
                    <div id="tab1" class="tab-content active">

                        <div class="form-row">
                            <label class="label-col" for="monitor-stock">Monitor Stock</label>
                            <div class="input-col has-checkbox">
                                <label><input type="checkbox" id="monitor-stock" name="monitor_stock" value="1" {{ $product->monitor_stock ? 'checked' : '' }}> Yes</label>
                            </div>
                            <span class="input-notes"><span class="note">Get notification when stock level gets low.</span></span>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="stock">Stock</label>
                                    <div class="input-col">
                                        <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock) }}">
                                    </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="min-stock">Min Stock</label>
                                    <div class="input-col">
                                        <input type="number" id="min-stock" name="min_stock" value="{{ old('min_stock', $product->min_stock) }}">
                                    </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="contact-avail">Contact for Availability</label>
                                    <div class="input-col has-checkbox">
                                        <label><input type="checkbox" id="contact-avail" name="contact_avail" value="1" {{ $product->contact_avail ? 'checked' : '' }}> Yes</label>
                                    </div>
                        </div>

                    </div>
                    <div id="tab2" class="tab-content">

                        <div class="form-row">
                            <label class="label-col" for="weight">Weight</label>
                            <div class="input-col input-col-group">
                                <div><input type="number" id="weight" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01"> lbs</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="dim">Dimensions</label>
                            <div class="input-col input-col-group">
                                <div><input type="number" id="width" name="width" value="{{ old('width', $product->width) }}" placeholder="Width" step="0.01"> in.</div>
                                <div><input type="number" id="height" name="height" value="{{ old('height', $product->height) }}" placeholder="Height" step="0.01"> in.</div>
                                <div><input type="number" id="depth" name="depth" value="{{ old('depth', $product->depth) }}" placeholder="Depth" step="0.01"> in.</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="product-shipping-rate-type">Shipping Class</label>
                            <div class="input-col">
                                <div class="select-wrapper">
                                    <select id="product-shipping-rate-type" name="shipping_class_id">
                                        <option value=""></option>
                                    @foreach( $shipping_classes as $shippingClass )
                                        <option value="{{$shippingClass->id}}" {{ $product->shipping_class_id === $shippingClass->id ? 'selected="selected"' : '' }}>{{$shippingClass->title}}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div id="tab3" class="tab-content">

                        <h4 style="margin-bottom: 6px;">Choose Your Attribute(s)</h4>

                                <span class="note" style="padding-left: 0; margin-bottom: 12px;">
                                    <strong>Note:</strong> Modifying attributes will alter any variations you have in place.
                                </span>

                                <span class="note" style="padding-left: 0; margin-bottom: 12px;">Create new attributes <a href="/admin/product-attributes">here</a></span>

                                <div class="attributes-chooser">
                                    @foreach( $attributes as $attr )
                                    @php
                                    $currAttrs = $product->variationAttributes;
                                    $attrChecked = '';
                                    $set = [];
                                    foreach($currAttrs as $currAttr){
                                        if( $attr->id === $currAttr->attribute_id ){
                                            $attrChecked = 'checked' ;
                                            $set = json_decode($currAttr->values);
                                        }
                                    }
                                    @endphp
                                    <div class="attr-row">
                                        <label id="attr-label-{{ $attr->id }}"><input type="checkbox" id="product-attr-{{ $attr->id }}" class="product-attr" name="attribute_ids[{{ $attr->id }}]" data-id="{{ $attr->id }}" value="{{ $attr->id }}" {{ $attrChecked }}> <span>{{ $attr->name }}</span></label>
                                        <div id="attr-values-{{ $attr->id }}" class="attr-values {{ $attrChecked === 'checked'? 'open' : '' }}">
                                            @php
                                            $attrValues = array_map('trim', explode(',',$attr->values));
                                            @endphp
                                            @foreach( (array) $attrValues as $value )
                                                <label><input type="checkbox" class="attr-value attr-value-{{ $attr->id }}" data-id="{{ $attr->id }}" name="attribute_values[{{ $attr->id }}][{{ $value }}]" value="{{ $value }}" {{ in_array($value, $set) ? 'checked' : '' }}> <span>{{ $value }}</span></label>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                    </div>
                    <div id="tab4" class="tab-content variation-content">

                        <div class="variation-header">
                            <h4 style="margin-bottom: 0">Add a Variation for Each Attribute Set</h4>
                            <span class="note" style="padding-left: 0">Add if you need to override things like stock and price from values above.</span>
                            <a href="#" class="add-variation-btn" role="button"><i class="fal fa-plus"></i> Add Variation</a>
                        </div>

                        <div class="variations-list">
                        @foreach( $product->variations as $variation )

                            <div class="variation-item" data-variation-id="{{ $variation->id }}" data-id="{{ $variation->id }}">
                                <a href="#" class="delete-variation-btn" data-id="{{ $variation->id }}">&times;</a>
                                    <div class="variation-options" data-variation-options-id="{{ $variation->id }}">
                                        <div class="inner">
                                        @if( $variation->image )
                                            <img class="variation-image" src="{{ $variation->image }}" alt="Variation image">
                                        @endif
                                            @php
                                            $attrSets = $variation->attribute_set;
                                            @endphp
                                            @foreach( $attrSets as $key => $attrSet )
                                                @php
                                                $productAttribute = getProductAttribute($key);
                                                $values = array_map('trim', explode(',', $productAttribute->values));
                                                @endphp
                                                <div id="product-variation-dropdown-{{ $variation->id }}-{{ $key }}" class="product-variation-dropdown">
                                                    <label for="var-select-{{ $variation->id }}-{{ $key }}">{{ $attrSet->attribute }}</label>
                                                    <input type="hidden" name="variation_attributes[{{$variation->id}}][{{ $key }}][attribute_id]" value="{{ $key }}">
                                                    <input type="hidden" name="variation_attributes[{{$variation->id}}][{{ $key }}][attribute]" value="{{ $attrSet->attribute }}">
                                                    <div class="select-wrapper">
                                                        <select id="var-select-{{ $variation->id }}-{{ $key }}" data-select-id="{{ $key }}" name="variation_attributes[{{ $variation->id }}][{{ $key }}][value]">
                                                            @foreach($values as $value)
                                                            <option value="{{ $value }}" {{ $value === $attrSet->value ? 'selected' : '' }}>{{ $value }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                               </div>
                                            @endforeach

                                            <div class="variation-collapse">
                                                <a href="#variation-fields{{ $variation->id }}" class="variation-collapse-btn" data-variation-collapse-id="{{ $variation->id }}"><i class="fal fa-angle-right"></i></a>
                                            </div>

                                        </div>
                                    </div>

                                <div id="variation-fields-{{ $variation->id }}" class="variation-fields">

                                        <div class="form-row">
                                            <label class="label-col" for="image{{ $variation->id }}">Image</label>
                                            <div class="input-col row">

                                                <div class="input-group">
                                                    <span class="input-group-btn">
                                                        <a class="lfm-image" data-lfm data-input="lfm-{{ $variation->id }}" data-preview="lfm-preview-{{ $variation->id }}">
                                                            <i class="fas fa-image"></i> Choose Image
                                                        </a>
                                                    </span>
                                                    <input id="lfm-{{ $variation->id }}" class="file-list-input" data-lfm-input value="{{ $variation->image }}" type="text" name="variations[{{ $variation->id }}][image]">
                                                </div>

                                                <div id="lfm-preview-{{ $variation->id }}" data-lfm-holder class="lfm-image-preview">
                                                @if( $variation->image )
                                                    <img src="{{ $variation->image }}" alt="featured image">
                                                    <a href="#" role="button" data-preview-id="lfm-preview-{{ $variation->id }}" data-input-id="lfm-{{ $variation->id }}" class="clear-lfm-image">&times;</a>
                                                @endif
                                                </div>

                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="desc-{{ $variation->id }}">Description</label>
                                            <div class="input-col">
                                                <textarea id="desc-{{ $variation->id }}" class="smaller" name="variations[{{ $variation->id }}][desc]">{{ $variation->desc }}</textarea>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="cost{{ $variation->id }}">Cost</label>
                                            <div class="input-col">
                                                <input type="number" id="cost{{ $variation->id }}" name="variations[{{ $variation->id }}][cost]" value="{{ $variation->cost }}" step="0.01">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="price{{ $variation->id }}">Price</label>
                                            <div class="input-col">
                                                <input type="number" id="price{{ $variation->id }}" name="variations[{{ $variation->id }}][price]" value="{{ $variation->price }}" step="0.01">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="sale-price{{ $variation->id }}">Sale Price</label>
                                            <div class="input-col">
                                                <input type="number" id="sale-price{{ $variation->id }}" name="variations[{{ $variation->id }}][sale_price]" value="{{ $variation->sale_price }}" step="0.01">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="sku{{ $variation->id }}">SKU</label>
                                            <div class="input-col">
                                                <input type="text" id="sku{{ $variation->id }}" name="variations[{{ $variation->id }}][sku]" value="{{ $variation->sku }}">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="mfg-part-number{{ $variation->id }}">MFG Part Number</label>
                                            <div class="input-col">
                                                <input type="text" id="mfg-part-number{{ $variation->id }}" name="variations[{{ $variation->id }}][mfg_part_number]" value="{{ $variation->mfg_part_number }}">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="stock{{ $variation->id }}">Stock</label>
                                            <div class="input-col">
                                                <input type="number" id="stock{{ $variation->id }}" name="variations[{{ $variation->id }}][stock]" value="{{ $variation->stock }}">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="weight{{ $variation->id }}">Weight</label>
                                            <div class="input-col input-col-group">
                                                <div><input type="number" id="weight{{ $variation->id }}" name="variations[{{ $variation->id }}][weight]" value="{{ $variation->weight }}" step="0.01"> lbs</div>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <label class="label-col" for="dim{{ $variation->id }}">Dimensions</label>
                                            <div class="input-col input-col-group">
                                                <div><input type="number" id="width{{ $variation->id }}" name="variations[{{ $variation->id }}][width]" value="{{ $variation->width }}" placeholder="Width" step="0.01"> in.</div>
                                                <div><input type="number" id="height{{ $variation->id }}" name="variations[{{ $variation->id }}][height]" value="{{ $variation->height }}" placeholder="Height" step="0.01"> in.</div>
                                                <div><input type="number" id="depth{{ $variation->id }}" name="variations[{{ $variation->id }}][depth]" value="{{ $variation->depth }}" placeholder="Depth" step="0.01"> in.</div>
                                            </div>
                                        </div>

                                </div>

                            </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="keywords">Keywords</label>
                <div class="input-col">
                    <input id="keywords" type="text" name="keywords" value="{{ old('keywords', $product->keywords) }}">
                </div>
            </div>

            <div class="form-row">
                <label class="label-col" for="meta-desc">Meta Description</label>
                <div class="input-col">
                    <input id="meta-desc" type="text" name="meta_description" value="{{ old('meta_description', $product->meta_description) }}">
                </div>
            </div>

            @foreach( $field_groups as $group )
            <h2 class="cf-group-title">{{ $group->title }}</h2>
                @if( $group->description )
                    <p>{{ $group->description }}</p>
                @endif
                @foreach( $group->fields as $field )
                {!! _generateField($field) !!}
                @endforeach
            @endforeach

        </div>

        <aside class="sidebar">
            <div class="side-fields">
                <div class="form-row">
                    <label class="label-col" for="status">Status</label>
                    <div class="input-col">
                        <div class="select-wrapper">
                            <select id="status" name="status">
                                <option value="P" {{ old('status', $product->status) === 'P'? 'selected="selected"' : '' }}>Publish</option>
                                <option value="D" {{ old('status', $product->status) === 'D'? 'selected="selected"' : '' }}>Draft</option>
                            </select>
                        </div>
                    </div>
                </div>

                @php $taxGroups = _getTaxonomyGroups('products') @endphp
                @foreach( $taxGroups as $taxGroup )
                    <div class="form-row">
                        <label class="label-col" for="{{ $taxGroup->slug }}">{{ str_plural($taxGroup->title) }}</label>
                        <div class="input-col">
                            <input type="text" name="tax_new[{{ $taxGroup->id }}]" placeholder="New {{ $taxGroup->title }}" style="margin-bottom: 4px">
                            <div class="term-group-select">
                                @foreach( $taxGroup->terms as $term )
                                <label><input type="checkbox" class="object-term-checkbox" data-entry-id="{{ $product->id }}" data-object-type="product" data-taxonomy-type="{{ $taxGroup->id }}" data-term-id="{{ $term->id }}" name="taxes[{{ $taxGroup->id }}][]" value="{{ $term->id }}" {{ $terms->contains('taxonomy_id', $term->id) ? 'checked="checked"' : '' }}> <span>{{ $term->title }}</span></label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="form-row">
                    <label class="label-col">Featured Image
                        <a class="lfm-featured-image" data-input="featured-image" data-multiple="0" data-preview="featured-image-preview">
                            <i class="fas fa-image"></i> Choose
                        </a>
                    </label>
                    <div class="input-col">
                        <input id="featured-image" class="file-list-input" value="{{ $product->featuredImage? $product->featuredImage->file_path : '' }}" type="text" name="featured_image">
                        <div id="featured-image-preview" class="featured-image-preview">
                            <img class="lfm-preview-image" src="{{ $product->featuredImage? $product->featuredImage->file_path : '' }}" style="height: 160px;">
                            @if($product->featuredImage)
                            <a class="clear-featured-image" href="/">&times;</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col">Gallery
                        <a class="lfm-gallery-image" data-input="gallery-image" data-multiple="1" data-preview="gallery-image-preview">
                            <i class="fas fa-image"></i> Choose
                        </a>
                    </label>
                    <div class="input-col">
                        <div id="gallery-image" class="file-list-input"></div>
                        <div id="gallery-image-preview" class="gallery-previews featured-image-preview"></div>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col">Social Image
                        <a class="lfm-social-image" data-input="social-image" data-preview="social-image-preview">
                            <i class="fas fa-image"></i> Choose
                        </a>
                    </label>
                    <div class="input-col">
                        <input id="social-image" class="file-list-input" value="{{ $product->social_image? $product->social_image : '' }}" type="text" name="social_image">
                        <div id="social-image-preview" class="featured-image-preview">
                            <img class="lfm-preview-image" src="{{ $product->social_image? $product->social_image : '' }}" style="height: 160px;">
                            @if($product->social_image)
                            <a class="clear-social-image" href="/">&times;</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn form-btn">Update</button>
                </div>
            </div>
        </aside>
    </div>
</form>
@endsection

@section('js')
<script>
window.object_user_edit = { object_type: 'product', id: <?php echo $product->id ?>, user_id: <?php echo auth()->user()->id; ?>, user_name: '<?php echo auth()->user()->name; ?>' };
window.editorCss = '<?php echo getEditorCss(); ?>';
</script>
@endsection
