                <nav class="customer-nav">
                    <ul>
                        <li class="{{ Route::currentRouteName() === 'shoppe.customer.account' ? 'active' : '' }}"><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}">Orders</a></li>
                        <li class="{{ Route::currentRouteName() === 'shoppe.customer.subscriptions' ? 'active' : '' }}"><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}/subscriptions">Subscriptions</a></li>
                        <li class="{{ Route::currentRouteName() === 'shoppe.customer.cards' ? 'active' : '' }}"><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}/cards">Saved Cards</a></li>
                        <li class="{{ Route::currentRouteName() === 'shoppe.customer.addresses' ? 'active' : '' }}"><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}/addresses">Addresses</a></li>
                        <li class="{{ Route::currentRouteName() === 'shoppe.customer.security' ? 'active' : '' }}"><a href="/{{ config('shoppe.slugs.customer_account', 'customer-account') }}/security">Security</a></li>
                    </ul>
                </nav>
