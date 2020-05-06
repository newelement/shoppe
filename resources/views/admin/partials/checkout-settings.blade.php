<h3>Checkout Settings</h3>

<form action="/admin/shoppe-settings/checkout" method="post">
    @csrf
    <!--
    <div class="form-row">
        <label class="label-col" for="add-cart-action">Add to cart action</label>
        <div class="input-col">
            <div class="select-wrapper">
                <select id="add-cart-action" name="add_cart_action" >
                    <option value="ajax" {{ getShoppeSetting('add_cart_action', $settings->cart) === 'ajax'? 'selected="selected"' : '' }}>Keep user on product page and show slide-in cart</option>
                    <option value="redirect" {{ getShoppeSetting('add_cart_action', $settings->cart) === 'redirect'? 'selected="selected"' : '' }}>Redirect user to the cart page</option>
                </select>
            </div>
        </div>
        <span class="input-notes">
            <span class="note">What happens when a user adds something to the cart?</span>
        </span>
    </div>-->

    <div class="form-row">
        <div class="label-col">Create Account During Checkout</div>
        <div class="input-col has-checkbox">
            <label><input type="checkbox" value="1" name="create_account_checkout" {{ getShoppeSetting('create_account_checkout', $settings->checkout) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
        </div>
        <span class="input-notes">
            <span class="note">Allow user to enter an account password during checkout to create an account.</span>
        </span>
    </div>

    <button type="submit" class="btn">Save Checkout Settings</button>

</form>
