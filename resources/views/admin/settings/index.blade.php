@extends('neutrino::admin.template.header-footer')
@section('title', 'Shoppe Settings | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Shoppe Settings</h2>
            </div>

            <div class="settings-container">
                <ul class="form-tabs">
                    <li><a href="#tab-0" class="active">Cart</a></li>
                    <li><a href="#tab-1">Shipping</a></li>
                </ul>
                <div class="tabs-content">

                    <div id="tab-0" class="tab-content active">

                        <div class="form-row">
                            <label class="label-col" for="add-cart-action">Add to cart action</label>
                            <div class="input-col">
                                <div class="select-wrapper">
                                    <select id="add-cart-action" name="add_cart_action" >
                                        <option value="ajax">Keep user on product page and show slide-in cart</option>
                                        <option value="redirect">Redirect user to the cart page</option>
                                    </select>
                                </div>
                            </div>
                            <span class="input-notes">
                                <span class="note">What happens when a user adds something to the cart?</span>
                            </span>
                        </div>

                    </div>
                    <div id="tab-1" class="tab-content">
                        <div class="form-row">
                            <label class="label-col" for="">Test 0</label>
                            <div class="input-col">

                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="">Test 0</label>
                            <div class="input-col">
                                <select id="" name="test" data-toggle-to="toggle-field1">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
