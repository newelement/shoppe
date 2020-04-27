<div class="setting-tab-section">
    <h3>Shipping Settings</h3>

    <form action="/admin/shoppe-settings/shipping" method="post">
    @csrf

        <div class="form-row">
            <div class="label-col">Shipping Type</div>
            <div class="input-col has-checkbox">
                <label><input type="radio" name="shipping_type" value="flat" {{  getShoppeSetting('shipping_type', $settings->shipping) === "flat" || getShoppeSetting('shipping_type', $settings->shipping) === ""? 'checked="checked"' : '' }}> <span>Flat Rates</span></label>
                <label><input type="radio" name="shipping_type" value="estimated" {{ getShoppeSetting('shipping_type', $settings->shipping) === "estimated"? 'checked="checked"' : '' }}> <span>Estimated</span></label>
            </div>
            <span class="input-notes"><span class="note">Estimated shipping requires a shipping vendor like <a href="https://shippo.com" target="_blank">Shippo</a> or <a href="https://shipstation.com" target="_blank">Shipstation</a></span></span>
        </div>

        <div class="form-row">
            <div class="label-col">Disable Shipping</div>
            <div class="input-col has-checkbox">
                <label><input type="checkbox" name="disable_shipping" value="1" {{  getShoppeSetting('disable_shipping', $settings->shipping) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
            </div>
            <span class="input-notes"><span class="note">Turn shipping completely off. Turn off if you are selling nothing but digital items.</span></span>
        </div>

        <h3>Shipping From Address</h3>

        <div class="form-row">
            <label class="label-col" for="shipping-name">Name</label>
            <div class="input-col">
                <input id="shipping-name" type="text" name="shipping_name" value="{{ getShoppeSetting('shipping_name', $settings->shipping) }}">
            </div>
        </div>

        <div class="form-row">
            <label class="label-col" for="shipping-address">Address</label>
            <div class="input-col">
                <input id="shipping-address" type="text" name="shipping_address" value="{{ getShoppeSetting('shipping_address', $settings->shipping) }}">
            </div>
        </div>

        <div class="form-row">
            <label class="label-col" for="shipping-address2">Address 2</label>
            <div class="input-col">
                <input id="shipping-address2" type="text" name="shipping_address2" value="{{ getShoppeSetting('shipping_address2', $settings->shipping) }}">
            </div>
        </div>

        <div class="form-row">
            <label class="label-col" for="shipping-city">City</label>
            <div class="input-col">
                <input id="shipping-city" type="text" name="shipping_city" value="{{ getShoppeSetting('shipping_city', $settings->shipping) }}">
            </div>
        </div>

        <div class="form-row">
            <label class="label-col" for="shipping-state">State/Providence</label>
            <div class="input-col">
            @php $states = getStates(); @endphp
            <div class="select-wrapper">
                <select id="shipping-state" name="shipping_state">
                    <option value="">Choose state ...</option>
                    @foreach( $states as $key => $state )
                    <option value="{{$key}}" {{ getShoppeSetting('shipping_state', $settings->shipping) === $key? 'selected="selected"' : '' }}>{{$state}}</option>
                    @endforeach
                </select>
            </div>
            </div>
        </div>

        <div class="form-row">
            <label class="label-col" for="shipping-postal">Zip/Postal code</label>
            <div class="input-col">
                <input id="shipping-postal" type="text" name="shipping_postal" value="{{ getShoppeSetting('shipping_postal', $settings->shipping) }}">
            </div>
        </div>

        <button type="submit" class="btn">Save Shipping Settings</button>

    </form>
</div>
