<div class="setting-tab-section">

    <div class="setting-content">
        <div class="setting-fields">
            <h3>Shipping Methods</h3>

            <form action="/admin/shoppe-settings/shipping-methods" method="post">
                @csrf
                @method('put')
                <div class="form-row-groups">
                    @foreach( $settings->shipping_methods as $shippingMethod )
                    <input type="hidden" name="shipping_classes[{{ $loop->index }}][id]" value="{{ $shippingMethod->id }}">
                    <div class="form-row-group">
                        <div class="form-row">
                            <label class="label-col" for="add-shipping-method-name-{{ $loop->index }}">Shipping Method Title</label>
                            <div class="input-col">
                                <input id="add-shipping-method-name-{{ $loop->index }}" type="text" name="shipping_methods[{{ $loop->index }}][title]" value="{{ $shippingMethod->title }}" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col">Method Type</label>
                            <div class="input-col has-checkbox">
                                <label><input id="shipping-method-type-a-{{ $loop->index }}" type="radio" name="shipping_methods[{{ $loop->index }}][method_type]" value="estimated" {{ $shippingMethod->method_type === 'estimated'? 'checked="checked"' : '' }}> Estimated</label>
                                <label><input id="shipping-method-type-b-{{ $loop->index }}" type="radio" name="shipping_methods[{{ $loop->index }}][method_type]" value="flat" {{ $shippingMethod->method_type === 'flat'? 'checked="checked"' : '' }}> Flat Rate</label>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="add-shipping-service-level-{{ $loop->index }}">Service Level</label>
                            <div class="input-col">
                                <input id="add-shipping-service-level-{{ $loop->index }}" type="text" name="shipping_methods[{{ $loop->index }}][service_level]" value="{{ $shippingMethod->service_level }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="add-shipping-days-{{ $loop->index }}">Estimated Days</label>
                            <div class="input-col">
                                <input id="add-shipping-days-{{ $loop->index }}" type="text" name="shipping_methods[{{ $loop->index }}][estimated_days]" value="{{ $shippingMethod->estimated_days }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="add-shipping-method-amount-{{ $loop->index }}">Cost</label>
                            <div class="input-col">
                                <input id="add-shipping-method-amount-{{ $loop->index }}" type="number" name="shipping_methods[{{ $loop->index }}][amount]" value="{{ $shippingMethod->amount }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="add-shipping-method-desc-{{ $loop->index }}">Description</label>
                            <div class="input-col">
                                <textarea id="add-shipping-method-desc-{{ $loop->index }}" name="shipping_methods[{{ $loop->index }}][notes]" style="height: 80px;">{{ $shippingMethod->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if( $settings->shipping_methods->count() )
                <button type="submit" class="btn">Update Shipping Methods</button>
                @endif

            </form>

        </div>
        <aside class="setting-sidebar">
            <h3>Create Shipping Method</h3>
            <form action="/admin/shoppe-settings/shipping-methods" method="post" style="margin-bottom: 24px;">
                @csrf
                <div class="form-row">
                    <label class="label-col" for="add-shipping-method-name">Method Title</label>
                    <div class="input-col">
                        <input id="add-shipping-method-name" type="text" name="title" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col">Method Type</label>
                    <div class="input-col has-checkbox">
                        <label><input id="shipping-method-type-a" type="radio" name="method_type" value="estimated" checked="checked"> Estimated</label>
                        <label><input id="shipping-method-type-b" type="radio" name="method_type" value="flat"> Flat Rate</label>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="add-shipping-service-evel">Service Level</label>
                    <div class="input-col">
                        <input id="add-shipping-service-level" type="text" name="service_level">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="add-shipping-days">Estimated Days</label>
                    <div class="input-col">
                        <input id="add-shipping-days" type="text" name="estimated_days" placeholder="1-4 days">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="add-shipping-method-amount">Cost</label>
                    <div class="input-col">
                        <input id="add-shipping-method-amount" type="number" name="amount">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="add-shipping-method-desc">Description</label>
                    <div class="input-col">
                        <textarea id="add-shipping-method-desc" name="notes" style="height: 80px;"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn">Create Shipping Method</button>
            </form>

        </aside>
    </div>

</div>
