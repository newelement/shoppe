@extends('neutrino::admin.template.header-footer')
@section('title', 'Create Product | ')
@section('content')
<form action="/admin/products" method="post" enctype="multipart/form-data">

		<div class="container">
			<div class="content">

				<h2>Create Product</h2>
					@csrf
					<div class="form-row">
						<label class="label-col" for="title">Title</label>
						<div class="input-col">
							<input id="title" class="to-slug" type="text" name="title" value="{{ old('title') }}">
						</div>
					</div>

					<div class="form-row">
						<label class="label-col" for="slug">Slug</label>
						<div class="input-col">
							<input id="slug" class="slug-input" type="text" name="slug" value="{{ old('slug') }}">
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
                                <textarea id="short-content" class="editor" name="short_content">{{ old('short_content') }}</textarea>
                            </div>
                            <div id="tabb" class="tab-content">
                                <textarea id="content" class="editor" name="content">{{ old('content') }}</textarea>
                            </div>
                            <div id="tabc" class="tab-content">
                                <textarea id="specs" class="editor" name="specs">{{ old('specs') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="label-col" for="product-type">Product Type</label>
                        <div class="input-col">
                            <div class="select-wrapper">
                                <select id="product-type" name="product_type">
                                    <option value="physical" {{ old('product_type') === 'physical' ? 'selected="selected"' : '' }}>Physical</option>
                                    @if( $subscriptions )
                                    <option value="subscription" {{ old('product_type') === 'subscription' ? 'selected="selected"' : '' }}>Subscription</option>
                                    @endif
                                    <option value="download" {{ old('product_type') === 'download' ? 'selected="selected"' : '' }}>Downloadable</option>
                                    <option value="role" {{ old('product_type') === 'role' ? 'selected="selected"' : '' }}>User Role</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @if( $subscriptions )
                    <div id="product-subscription-row" class="form-row" style="display: none">
                        <label class="label-col">Subscription</label>
                        <div class="input-col">
                            <div class="select-wrapper">
                                <select id="subscription-id" name="subscription_id">
                                    <option value="">Choose...</option>
                                    @foreach( $subscriptions['plans'] as $subscription )
                                    <option value="{{$subscription['id']}}" {{ old('subscription_id') === $subscription['id'] ? 'selected="selected"' : '' }}>{{$subscription['name']}} - {{$subscription['amount']}} {{$subscription['interval']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div id="product-file-row" class="form-row" style="display: none;">
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

                    <div id="product-role-row" class="form-row" style="display: none;">
                        <label class="label-col" for="role-id">Role</label>
                        <div class="input-col">
                            <div class="select-wrapper">
                                <select id="role-id" name="role_id">
                                    <option value="">Choose...</option>
                                    @foreach( $roles as $role )
                                    <option value="{{ $role->id }}" {{ old('role_id') == '$role->id' ? 'selected="selected"' : '' }}>{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="label-col" for="cost">Cost</label>
                        <div class="input-col">
                            <input type="number" id="cost" name="cost" value="{{ old('cost') }}" step="0.01">
                        </div>
                    </div>

                    <div class="form-row">
						<label class="label-col" for="price">Price</label>
						<div class="input-col">
							<input type="number" id="price" name="price" value="{{ old('price') }}" step="0.01">
						</div>
					</div>

					<div class="form-row">
						<label class="label-col" for="sale-price">Sale Price</label>
						<div class="input-col">
							<input type="number" id="sale-price" name="sale_price" value="{{ old('sale_price') }}" step="0.01">
						</div>
					</div>

                    <div class="form-row">
                        <label class="label-col" for="taxable">Taxable</label>
                        <div class="input-col has-checkbox">
                            <label><input type="checkbox" id="taxable" name="is_taxable" value="1" {{ old('is_taxable') === '1' ? 'checked' : '' }}> Yes</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="label-col" for="contact-price">Contact for Pricing</label>
                        <div class="input-col has-checkbox">
                            <label><input type="checkbox" id="contact-price" name="contact_price" value="1" {{ old('contact_price') === '1' ? 'checked' : '' }}> Yes</label>
                        </div>
                    </div>

					<div class="form-row">
						<label class="label-col" for="sku">SKU</label>
						<div class="input-col">
							<input type="text" id="sku" name="sku" value="{{ old('sku') }}">
						</div>
					</div>

					<div class="form-row">
						<label class="label-col" for="mfg-part-number">MFG Part Number</label>
						<div class="input-col">
							<input type="text" id="mfg-part-number" name="mfg_part_number" value="{{ old('mfg_part_number') }}">
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
            							<label><input type="checkbox" id="monitor-stock" name="monitor_stock" value="{{ old('monitor_stock') }}"> Yes</label>
            						</div>
            						<span class="input-notes"><span class="note">Get notification when stock level gets low.</span></span>
            					</div>

            					<div class="form-row">
            						<label class="label-col" for="stock">Stock</label>
            						<div class="input-col">
            							<input type="number" id="stock" name="stock" value="{{ old('stock') }}">
            						</div>
            					</div>

            					<div class="form-row">
            						<label class="label-col" for="min-stock">Min Stock</label>
            						<div class="input-col">
            							<input type="number" id="min-stock" name="min_stock" value="{{ old('min_stock') }}">
            						</div>
            					</div>

                                <div class="form-row">
                                    <label class="label-col" for="contact-avail">Contact for Availability</label>
                                    <div class="input-col has-checkbox">
                                        <label><input type="checkbox" id="contact-avail" name="contact_avail" value="{{ old('contact_avail') }}"> Yes</label>
                                    </div>
                                </div>

        					</div>
        					<div id="tab2" class="tab-content">

            					<div class="form-row">
            						<label class="label-col" for="weight">Weight</label>
            						<div class="input-col input-col-group">
            							<div><input type="number" id="weight" name="weight" value="{{ old('weight') }}" step="0.01"> lbs</div>
            						</div>
            					</div>

            					<div class="form-row">
            						<label class="label-col" for="weight">Dimensions</label>
            						<div class="input-col input-col-group">
            							<div><input type="number" id="width" name="width" value="{{ old('width') }}" placeholder="Width" step="0.01"> in.</div>
            							<div><input type="number" id="height" name="height" value="{{ old('height') }}" placeholder="Height" step="0.01"> in.</div>
            							<div><input type="number" id="depth" name="depth" value="{{ old('depth') }}" placeholder="Depth" step="0.01"> in.</div>
            						</div>
            					</div>

            					<div class="form-row">
            						<label class="label-col" for="product-shipping-rate-type">Shipping Rate Type</label>
            						<div class="input-col">
            							<div class="select-wrapper">
                							<select id="product-shipping-rate-type" name="shipping_rate_type">
                    							<option value="global" {{ old('shipping_rate_type') === 'global' ? 'selected="selected"' : '' }}>Use Global Value</option>
                    							<option value="flat" {{ old('shipping_rate_type') === 'flat' ? 'selected="selected"' : '' }}>Flat Rate</option>
                    							<option value="estimated" {{ old('shipping_rate_type') === 'estimated' ? 'selected="selected"' : '' }}>Estimated</option>
                							</select>
            							</div>
            						</div>
            						<span class="input-notes"><span class="note">This setting will override the global shipping setting.</span></span>
            					</div>

                                <div id="shipping-rate-row" class="form-row" style="display: none">
                                    <label class="label-col" for="shipping-rate">Shipping Rate</label>
                                    <div class="input-col">
                                        <input type="number" id="shipping-rate" name="shipping_rate" value="{{ old('shipping_rate') }}" step="0.01">
                                    </div>
                                    <span class="input-notes"><span class="note">This setting will override the global shipping setting.</span></span>
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
                                    <div class="attr-row">
                                        <label id="attr-label-{{ $attr->id }}"><input type="checkbox" id="product-attr-{{ $attr->id }}" class="product-attr" name="attribute_ids[{{ $attr->id }}]" data-id="{{ $attr->id }}" value="{{ $attr->id }}"> <span>{{ $attr->name }}</span></label>
                                        <div id="attr-values-{{ $attr->id }}" class="attr-values">
                                            @php
                                            $attrValues = array_map('trim', explode(',',$attr->values));
                                            @endphp
                                            @foreach( (array) $attrValues as $value )
                                                <label><input type="checkbox" class="attr-value attr-value-{{ $attr->id }}" data-id="{{ $attr->id }}" name="attribute_values[{{ $attr->id }}][{{ $value }}]" value="{{ $value }}"> <span>{{ $value }}</span></label>
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
                                </div>

        					</div>
    					</div>
					</div>


					<div class="form-row">
						<label class="label-col" for="keywords">Keywords</label>
						<div class="input-col">
							<input id="keywords" type="text" name="keywords" value="{{ old('keywords') }}">
						</div>
					</div>

					<div class="form-row">
						<label class="label-col" for="meta-desc">Meta Description</label>
						<div class="input-col">
							<input id="meta-desc" type="text" name="meta_description" value="{{ old('meta_description') }}">
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
    								<option value="P" {{ old('status') === 'P'? 'selected="selected"' : '' }}>Publish</option>
    								<option value="D" {{ old('status') === 'D'? 'selected="selected"' : '' }}>Draft</option>
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
								<label><input type="checkbox" name="taxes[{{ $taxGroup->id }}][]" value="{{ $term->id }}"> {{ $term->title }}</label>
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
							<input id="featured-image" class="file-list-input" value="" type="text" name="featured_image">
							<div id="featured-image-preview" class="featured-image-preview"></div>
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
							<input id="social-image" class="file-list-input" value="" type="text" name="social_image">
							<div id="social-image-preview" class="featured-image-preview"></div>
						</div>
					</div>

					<div class="form-actions">
						<button type="submit" class="btn form-btn">Create</button>
					</div>
				</div>
			</aside>
		</div>
	</form>
@endsection

@section('js')
<script>
window.editorCss = '<?php echo getEditorCss(); ?>';
</script>
@endsection
