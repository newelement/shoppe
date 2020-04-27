<h3>Cart Settings</h3>

<form action="/admin/shoppe-settings/cart" method="post">
                            @csrf
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
                            </div>

                            <div class="form-row">
                                <div class="label-col">Skip Cart</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="skip_cart" {{ getShoppeSetting('skip_cart', $settings->cart) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                                <span class="input-notes">
                                    <span class="note">Skip the cart page and take user directly to checkout.</span>
                                </span>
                            </div>

                            <button type="submit" class="btn">Save Cart Settings</button>

                        </form>
