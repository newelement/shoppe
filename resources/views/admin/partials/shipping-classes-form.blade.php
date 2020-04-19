<div class="setting-tab-section">

    <div class="setting-content">
        <div class="setting-fields">
            <h3>Shipping Classes</h3>

            <form id="shipping-classes-form" action="/admin/shoppe-settings/shipping-classes" method="post">
                @csrf
                @method('put')
                <div class="form-row-groups">
                    @foreach( $settings->shipping_classes as $shippingClass )
                    <input type="hidden" name="shipping_classes[{{ $loop->index }}][id]" value="{{ $shippingClass->id }}">
                    <div class="form-row-group">
                        <div class="form-row">
                            <label class="label-col" for="add-shipping-class-name-{{ $loop->index }}">Shipping Class</label>
                            <div class="input-col">
                                <input id="add-shipping-class-name-{{ $loop->index }}" type="text" name="shipping_classes[{{ $loop->index }}][title]" value="{{ $shippingClass->title }}" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="add-shipping-class-desc-{{ $loop->index }}">Description</label>
                            <div class="input-col">
                                <textarea id="add-shipping-class-desc-{{ $loop->index }}" name="shipping_classes[{{ $loop->index }}][notes]" style="height: 80px;">{{ $shippingClass->notes }}</textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="text-right" style="width: 100%">
                                <a class="delete-shipping-class" href="/admin/shoppe-settings/shipping-classes/delete/{{ $shippingClass->id }}">Delete "{{ $shippingClass->title }}" class</a>
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>

                @if($settings->shipping_classes->count())
                <button type="submit" class="btn">Update Shipping Classes</button>
                @endif

            </form>

        </div>
        <aside class="setting-sidebar">
            <h3>Create Shipping Class</h3>
            <form action="/admin/shoppe-settings/shipping-classes" method="post" style="margin-bottom: 24px;">
                @csrf
                <div class="form-row">
                    <label class="label-col" for="add-shipping-class-name">Class Title</label>
                    <div class="input-col">
                        <input id="add-shipping-class-name" type="text" name="title" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="add-shipping-class-desc">Description</label>
                    <div class="input-col">
                        <textarea id="add-shipping-class-desc" name="notes" style="height: 80px;"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn">Create Shipping Class</button>
            </form>

        </aside>
    </div>

</div>
