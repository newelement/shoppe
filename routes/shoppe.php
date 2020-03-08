<?php
$namespacePrefix = '\\Newelement\\Shoppe\\Http\\Controllers\\';

Route::group(['prefix' => 'admin', 'as' => 'shoppe.', 'middleware' => 'admin.user'], function () use ( $namespacePrefix ) {

    Route::get('/products', ['uses' => $namespacePrefix.'Admin\ProductController@index', 'as' => 'products']);
    Route::get('/product', ['uses' => $namespacePrefix.'Admin\ProductController@getCreate', 'as' => 'products']);
    Route::post('/products', ['uses' => $namespacePrefix.'Admin\ProductController@create', 'as' => 'products']);
    Route::get('/product/{id}', ['uses' => $namespacePrefix.'Admin\ProductController@get', 'as' => 'products']);
    Route::post('/product/{id}', ['uses' => $namespacePrefix.'Admin\ProductController@update', 'as' => 'products']);
    Route::delete('/products/{id}', ['uses' => $namespacePrefix.'Admin\ProductController@delete', 'as' => 'products']);
    Route::get('/products-trash', $namespacePrefix.'Admin\ProductController@getTrash')->name('products');
    Route::delete('/product-variation-delete', $namespacePrefix.'Admin\ProductController@deleteVariation');
    Route::get('/products/recover/{id}', $namespacePrefix.'Admin\ProductController@recover');
    Route::get('/products/destroy/{id}', $namespacePrefix.'Admin\ProductController@destroy');

    Route::get('/product-attributes', ['uses' => $namespacePrefix.'Admin\ProductAttributesController@index', 'as' => 'products']);
    Route::post('/product-attributes', ['uses' => $namespacePrefix.'Admin\ProductAttributesController@create', 'as' => 'products']);
    Route::post('/product-attributes/{id}', ['uses' => $namespacePrefix.'Admin\ProductAttributesController@update', 'as' => 'products']);
    Route::get('/product-attributes/{id}', ['uses' => $namespacePrefix.'Admin\ProductAttributesController@get', 'as' => 'products']);
    Route::delete('/product-attributes/{id}', ['uses' => $namespacePrefix.'Admin\ProductAttributesController@delete', 'as' => 'products']);

    Route::get('/orders', ['uses' => $namespacePrefix.'Admin\OrderController@index', 'as' => 'shoppe']);
    Route::put('/orders/{order}/status', ['uses' => $namespacePrefix.'Admin\OrderController@updateStatus', 'as' => 'shoppe']);
    Route::get('/orders/{order}', ['uses' => $namespacePrefix.'Admin\OrderController@get', 'as' => 'orders']);
    Route::put('/orders/{order}/order-lines/{orderLine}/action', ['users' => $namespacePrefix.'Admin\OrderController@updateOrderLine', 'as' => 'shoppe']);
    Route::post('/order-lines/refund/{orderLine}', $namespacePrefix.'Admin\OrderController@refundOrderLine');
    Route::get('/shipping-label/{order}', $namespacePrefix.'Admin\OrderController@getShippingLabel');
    Route::get('/transaction-details/{transaction_id}', config('shoppe.payment_connector').'@getCharge');
    Route::post('/orders/{order}/note', $namespacePrefix.'Admin\OrderController@createNote');
    Route::get('/resend-reciept/{order}', $namespacePrefix.'Admin\OrderController@resendReceipt');

    Route::get('/shoppe', $namespacePrefix.'Admin\ShoppeController@index')->name('shoppe');
    Route::get('/shoppe-reports', $namespacePrefix.'Admin\ShoppeReportController@index')->name('shoppe');
    Route::get('/shoppe/sales-report', $namespacePrefix.'Admin\ShoppeReportController@getSales')->name('shoppe');
    Route::get('/shoppe/profit-report', $namespacePrefix.'Admin\ShoppeReportController@getProfit')->name('shoppe');
    Route::get('/shoppe-settings', $namespacePrefix.'Admin\ShoppeSettingsController@index')->name('shoppe');

});

Route::group(['as' => 'shoppe.'], function () use ( $namespacePrefix ) {
    Route::get('/'.config('shoppe.slugs.store_landing', 'products'), ['uses' => $namespacePrefix.'ProductController@index', 'as' => 'products']);
    Route::get('/'.config('shoppe.slugs.product_single', 'products').'/{slug}', ['uses' => $namespacePrefix.'ProductController@get', 'as' => 'products']);

    Route::post('/cart', ['uses' => $namespacePrefix.'CartController@create', 'as' => 'cart']);
    Route::get('/cart', ['uses' => $namespacePrefix.'CartController@index', 'as' => 'cart']);
    Route::put('/cart', ['uses' => $namespacePrefix.'CartController@update', 'as' => 'cart']);
    Route::delete('/cart', ['uses' => $namespacePrefix.'CartController@delete', 'as' => 'cart']);

    Route::get('/checkout', ['uses' => $namespacePrefix.'CheckoutController@index', 'as' => 'checkout']);
    Route::post('/checkout', ['uses' => $namespacePrefix.'CheckoutController@processCheckout', 'as' => 'checkout']);
    Route::get('/'.config('shoppe.slugs.order_complete', 'order-complete').'/{ref_id}', ['uses' => $namespacePrefix.'CheckoutController@checkoutSuccess', 'as' => 'order-complete']);
});

Route::group(['as' => 'shoppe.', 'middleware' => 'shoppe.customer'], function () use ( $namespacePrefix ) {
    Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account'), ['uses' => $namespacePrefix.'CustomerController@index', 'as' => 'customer.account']);
    Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/orders/{id}', ['uses' => $namespacePrefix.'CustomerController@order', 'as' => 'customer.order']);
    Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/security', ['uses' => $namespacePrefix.'CustomerController@security', 'as' => 'customer.security']);
    Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses', ['uses' => $namespacePrefix.'CustomerController@addresses', 'as' => 'customer.addresses']);
    Route::put('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses/{id}', ['uses' => $namespacePrefix.'CustomerController@addressUpdate', 'as' => 'customer.addresses']);
    Route::post('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses', ['uses' => $namespacePrefix.'CustomerController@addressCreate', 'as' => 'customer.addresses']);
    Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/cards', ['uses' => $namespacePrefix.'CustomerController@cards', 'as' => 'customer.cards']);

});

Route::group(['prefix' => 'api', 'as' => 'shoppe.'], function () use ( $namespacePrefix ) {
    Route::post('/taxes', $namespacePrefix.'CheckoutController@getTaxes');
    Route::post('/shipping', $namespacePrefix.'CheckoutController@getShipping');
});
