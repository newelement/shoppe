<div class="setting-tab-section">

    <div class="setting-content">
        <div class="setting-fields">
            <h3>Shipping Methods</h3>

            <h4 style="margin:0">Note:</h4>
            <p style="font-size: 16px;">
            If free shipping is used all other shipping methods will be ignored. If estimated shipping it used, all flat rate methods will be ignored. If free shipping is used and an customer's order has not met the mininum order amount, the next used methods will be estimated or flat rate.
            </p>

                <div class="shipping-methods-group-list">
                @foreach( $settings->shipping_methods as $key => $shippingMethod )
                <div class="shipping-method-group" data-id="{{ $shippingMethod->id }}">
                    <div class="shipping-method-rows">

                        <div class="sm-title">
                            <a href="/admin/shoppe-settings/shipping-methods/{{ $shippingMethod->id }}?tab=shipping&section=shipping_methods">{{ $shippingMethod->title }}</a>
                        </div>

                        <div class="sm-type">
                        {{ ucwords($shippingMethod->method_type) }}
                        </div>

                        <div class="sm-amount">
                            @if( $shippingMethod->method_type === 'flat' )
                                {{ $shippingMethod->amount }}
                            @endif

                            @if( $shippingMethod->method_type === 'free' )
                                {{ $shippingMethod->minimum_order_amount }}
                            @endif
                        </div>

                        <div class="sm-level">
                            @if( $shippingMethod->method_type === 'flat' )
                                {{ $shippingMethod->service_level }}
                            @endif
                        </div>

                        <div class="sm-edit text-center">
                            <a href="/admin/shoppe-settings/shipping-methods/{{ $shippingMethod->id }}?tab=shipping&section=shipping_methods">Edit</a>
                        </div>

                        <div class="sm-delete">
                            <form class="delete-form" action="/admin/shoppe-settings/shipping-methods/{{ $shippingMethod->id }}" method="post">
                                @method('delete')
                                @csrf
                                <button type="submit" class="delete-btn">&times;</button>
                            </form>
                        </div>
                    </div>


                    @if( $settings->shipping_classes->count() && $shippingMethod->method_type === 'flat' )
                    <form action="/admin/shoppe-settings/shipping-method-classes/{{$shippingMethod->id}}" method="post">
                        @csrf
                        <div class="shipping-classes-explain">
                            <h4 style="margin: 0">Shipping Class Costs (optional): </h4>
                        </div>
                        <div class="shipping-method-classes-group">
                            @foreach( $settings->shipping_classes as $shippingClass )
                            @php
                            $calcType = 'per_class';
                            $classSet = collect();
                            $methodClasses = $shippingMethod->methodClasses;
                            foreach( $methodClasses as $methodClass ){
                                if( $methodClass->shipping_method_id === $shippingMethod->id && $methodClass->shipping_class_id === $shippingClass->id ){
                                    $classSet = $methodClass;
                                    $calcType = $methodClass->calc_type;
                                }
                            }
                            @endphp

                            <div class="shipping-method-classes-row">
                                <div class="shipping-method-class-title">{{ $shippingClass->title }}</div>
                                <label>Cost <input type="number" name="classes[{{$shippingClass->id}}][amount]" value="{{ isset($classSet->amount)? $classSet->amount : '' }}"></label>
                            </div>
                            @endforeach
                        </div>

                        <div class="calc-class-rows">
                            <label for="calc-type-{{ $key }}">Calculation Type</label>
                            <select id="calc-type-{{ $key }}" name="calc_type" style="width: 100%;">
                                <option value="per_class" {{ $calcType === 'per_class'? 'selected="selected"' : ''}} >Per Class: Charge for each shipping class individually</option>
                                <option value="per_order" {{ $calcType === 'per_order'? 'selected="selected"' : ''}}>Per Order: Charge for the most expensive shipping class</option>
                            </select>
                        </div>

                        <div class="shipping-class-actions-row text-right">
                            <button type="submit" class="btn small smaller">Update Method Classes</button>
                        </div>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <aside class="setting-sidebar">
            <h3>{{ $settings->edit_method? 'Edit' : 'Create' }} Shipping Method</h3>
            <form @if($settings->edit_method) action="/admin/shoppe-settings/shipping-methods/{{ $shipping_method->id }}" @else action="/admin/shoppe-settings/shipping-methods" @endif method="post" style="margin-bottom: 24px;">
                @csrf
                <div class="form-row">
                    <label class="label-col" for="add-shipping-method-name">Method Title</label>
                    <div class="input-col">
                        <input id="add-shipping-method-name" type="text" name="title" @if($settings->edit_method) value="{{ $shipping_method->title }}" @endif required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col">Method Type</label>
                    <div class="input-col has-checkbox">
                        <label><input id="shipping-method-type-a" class="shipping-method-type" type="radio" name="method_type" value="estimated" @if($settings->edit_method) {{ $shipping_method->method_type === 'estimated'? 'checked="checked"' : '' }} @endif> Estimated</label>
                        <label><input id="shipping-method-type-b" class="shipping-method-type" type="radio" name="method_type" value="flat" @if($settings->edit_method) {{ $shipping_method->method_type === 'flat'? 'checked="checked"' : '' }} @else checked="checked"  @endif > Flat Rate</label>
                        <label><input id="shipping-method-type-c" class="shipping-method-type" type="radio" name="method_type" value="free" @if($settings->edit_method) {{ $shipping_method->method_type === 'free'? 'checked="checked"' : '' }} @endif> Free Shipping</label>
                    </div>
                </div>

                <div class="estimated-rate-shipping-fields @if( $settings->edit_method ) {{ $shipping_method->method_type === 'estimated' ? '' : 'hide' }} @else hide @endif" style="margin-bottom: 12px; padding: 0 12px 12px 12px;">
                    <span style="color: #888; font-size: 15px">
                    *Estimated shipping requires a shipping connector and a vendor like <a href="https://shippo.com" target="_blank">Shippo</a> or <a href="https://shipstation.com" target="_blank">Shipstation</a>. You can only have one method for estimated shipping. Your shipping connector will pull methods from your vendor.
                    </span>
                </div>

                <div class="flat-rate-shipping-fields @if( $settings->edit_method ) {{ $shipping_method->method_type === 'flat' ? '' : 'hide' }} @endif">

                    <div class="form-row">
                        <label class="label-col" for="add-shipping-service-evel">Service Level</label>
                        <div class="input-col">
                            <div class="select-wrapper">
                                <select id="add-shipping-service-level" name="service_level">
                                    <option value="">Choose ...</option>
                                    @foreach( $settings->service_levels as $serviceLevel )
                                    <optgroup label="{{$serviceLevel['carrier']}}">
                                        @foreach( $serviceLevel['levels'] as $key => $level )
                                        <option value="{{ $key }}" @if($settings->edit_method) {{ $shipping_method->service_level === $key ? 'selected="selected"' : '' }}  @endif>{{ $level }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="label-col" for="add-shipping-days">Estimated Days</label>
                        <div class="input-col">
                            <input id="add-shipping-days" type="text" name="estimated_days" @if($settings->edit_method) value="{{ $shipping_method->estimated_days }}" @endif placeholder="1-4 days">
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="label-col" for="add-shipping-method-amount">Cost</label>
                        <div class="input-col">
                            <input id="add-shipping-method-amount" type="number" @if($settings->edit_method) value="{{ $shipping_method->amount }}" name="amount" @endif>
                        </div>
                    </div>

                </div>

                <div class="free-shipping-fields @if( $settings->edit_method ) {{ $shipping_method->method_type === 'free' ? '' : 'hide' }} @else hide @endif">

                    <div class="form-row">
                        <label class="label-col" for="min-order-amount">Minimum Order Amount</label>
                        <div class="input-col">
                            <input id="min-order-amount" type="number" name="minimum_order_amount" @if($settings->edit_method) value="{{ $shipping_method->minimum_order_amount }}" @endif>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="label-col" for="add-shipping-days1">Estimated Days</label>
                        <div class="input-col">
                            <input id="add-shipping-days1" type="text" name="free_estimated_days" @if($settings->edit_method) value="{{ $shipping_method->free_estimated_days }}" @endif placeholder="1-4 days">
                        </div>
                    </div>

                </div>

                <div class="form-row">
                    <label class="label-col" for="add-shipping-method-desc">Description</label>
                    <div class="input-col">
                        <textarea id="add-shipping-method-desc" name="notes" @if($settings->edit_method) value="{{ $shipping_method->notes }}" @endif style="height: 80px;"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn">{{ $settings->edit_method? 'Edit' : 'Create' }} Shipping Method</button>
            </form>

        </aside>
    </div>

</div>
