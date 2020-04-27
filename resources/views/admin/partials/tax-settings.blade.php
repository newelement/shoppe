<h3>Tax Settings</h3>

    <form action="/admin/shoppe-settings/tax" method="post">
    @csrf

        <div class="form-row">
            <div class="label-col">Tax on Address</div>
            <div class="input-col has-checkbox">
                <label><input type="radio" name="tax_address" value="shipping" {{  getShoppeSetting('tax_address', $settings->taxes) === "shipping" ? 'checked="checked"' : '' }}> <span>Shipping Address</span></label>
                <label><input type="radio" name="tax_address" value="billing" {{ getShoppeSetting('tax_address', $settings->taxes) === "billing"? 'checked="checked"' : '' }}> <span>Billing Address</span></label>
            </div>
            <span class="input-notes"><span class="note">Which address to charge taxes on. When only selling digital items use the billing address.</span></span>
        </div>

        <div class="form-row">
            <label class="label-col" for="tax-states">Tax Jurisdictions</label>
            <div class="input-col">
            @php
            $states = getStates();
            $statesValue = getShoppeSetting('tax_states', $settings->taxes);
            @endphp
                <select name="tax_states[]" id="tax-states" multiple size="6">
                    @foreach($states as $key => $state)
                    <option value="{{$key}}" {{ in_array($key, (array) $statesValue)? 'selected="selected"' : '' }}>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <span class="input-notes"><span class="note">States/Provinces where you have established businesses in.</span></span>
        </div>

        <button type="submit" class="btn">Save Tax Settings</button>

    </form>
