<?php
Route::get('/products', ['uses' => 'ProductController@index', 'as' => 'products']);
Route::get('/product', ['uses' => 'ProductController@getCreate', 'as' => 'products']);
Route::post('/products', ['uses' => 'ProductController@create', 'as' => 'products']);
Route::get('/product/{id}', ['uses' => 'ProductController@get', 'as' => 'products']);
Route::post('/product/{id}', ['uses' => 'ProductController@update', 'as' => 'products']);
Route::delete('/products/{id}', ['uses' => 'ProductController@delete', 'as' => 'products']);
Route::get('/products-trash', 'ProductController@getTrash')->name('products');
Route::delete('/product-variation-delete', 'ProductController@deleteVariation');
Route::get('/products/recover/{id}', 'ProductController@recover');
Route::get('/products/destroy/{id}', 'ProductController@destroy');

Route::get('/product-attributes', ['uses' => 'ProductAttributesController@index', 'as' => 'products']);
Route::post('/product-attributes', ['uses' => 'ProductAttributesController@create', 'as' => 'products']);
Route::post('/product-attributes/{id}', ['uses' => 'ProductAttributesController@update', 'as' => 'products']);
Route::get('/product-attributes/{id}', ['uses' => 'ProductAttributesController@get', 'as' => 'products']);
Route::delete('/product-attributes/{id}', ['uses' => 'ProductAttributesController@delete', 'as' => 'products']);

Route::get('/orders', ['uses' => 'OrderController@index', 'as' => 'shoppe']);
Route::put('/orders/{order}/status', ['uses' => 'OrderController@updateStatus', 'as' => 'shoppe']);
Route::get('/orders/{order}', ['uses' => 'OrderController@get', 'as' => 'orders']);
Route::put('/orders/{order}/order-lines/{orderLine}/action', ['users' => 'OrderController@updateOrderLine', 'as' => 'shoppe']);
Route::post('/order-lines/refund/{orderLine}', 'OrderController@refundOrderLine');
Route::get('/transaction-details/{transaction_id}', config('shoppe.payment_connector').'@getCharge');
Route::post('/orders/{order}/note', 'OrderController@createNote');
Route::get('/resend-reciept/{order}', 'OrderController@resendReceipt');

Route::post('/shipping-label/{order}', 'OrderController@getShippingLabel');
Route::post('/shipping-estimate', 'OrderController@getShippingEstimate');

Route::get('/subscription-plans', ['uses' => 'SubscriptionController@indexPlans', 'as' => 'shoppe']);
Route::get('/subscription-plan', ['uses' => 'SubscriptionController@showCreatePlan', 'as' => 'shoppe']);
Route::get('/subscription-plans/{id}', ['uses' => 'SubscriptionController@getPlan', 'as' => 'shoppe']);
Route::post('/subscription-plans', ['uses' => 'SubscriptionController@createPlan', 'as' => 'shoppe']);
Route::put('/subscription-plans/{id}', ['uses' => 'SubscriptionController@updatePlan', 'as' => 'shoppe']);
Route::delete('/subscription-plans/{id}', ['uses' => 'SubscriptionController@deletePlan', 'as' => 'shoppe']);

Route::get('/subscriptions', ['uses' => 'SubscriptionController@index', 'as' => 'shoppe']);
Route::get('/subscriptions/{subscriptionId}', ['uses' => 'SubscriptionController@get', 'as' => 'shoppe']);
Route::put('/subscriptions/{id}', ['uses' => 'SubscriptionController@update', 'as' => 'shoppe']);
Route::post('/subscriptions/{id}/cancel', ['uses' => 'SubscriptionController@cancel', 'as' => 'shoppe']);

Route::get('/stripe/tax-rates', ['uses' => 'SubscriptionController@taxRates', 'as' => 'shoppe']);
Route::get('/stripe/tax-rate', ['uses' => 'SubscriptionController@showTaxRate', 'as' => 'shoppe']);
Route::get('/stripe/tax-rates/{id}', ['uses' => 'SubscriptionController@getTaxRate', 'as' => 'shoppe']);
Route::post('/stripe/tax-rates', ['uses' => 'SubscriptionController@createTaxRate', 'as' => 'shoppe']);
Route::put('/stripe/tax-rates/{id}', ['uses' => 'SubscriptionController@updateTaxRate', 'as' => 'shoppe']);

Route::get('/shoppe', 'ShoppeController@index')->name('shoppe');
Route::get('/shoppe-reports', 'ShoppeReportController@index')->name('shoppe');
Route::get('/shoppe/sales-report', 'ShoppeReportController@getSales')->name('shoppe');
Route::get('/shoppe/profit-report', 'ShoppeReportController@getProfit')->name('shoppe');
Route::get('/shoppe-settings', 'ShoppeSettingsController@index')->name('shoppe');

Route::get('/discount-codes', ['uses' => 'DiscountCodeController@index', 'as' => 'shoppe'])->name('shoppe');
Route::get('/discount-codes/{id}', ['uses' => 'DiscountCodeController@get', 'as' => 'shoppe'])->name('shoppe');
Route::post('/discount-codes', ['uses' => 'DiscountCodeController@create', 'as' => 'shoppe'])->name('shoppe');
Route::put('/discount-codes/{id}', ['uses' => 'DiscountCodeController@update', 'as' => 'shoppe'])->name('shoppe');
Route::delete('/discount-codes/{id}', ['uses' => 'DiscountCodeController@delete', 'as' => 'shoppe'])->name('shoppe');

Route::post('/shoppe-settings/sort/shipping-methods', 'ShoppeSettingsController@updateShippingMethodsSort')->name('shoppe');
Route::post('/shoppe-settings/shipping-classes', 'ShoppeSettingsController@createShippingClass')->name('shoppe');
Route::post('/shoppe-settings/products', 'ShoppeSettingsController@updateProductSettings')->name('shoppe');
Route::post('/shoppe-settings/cart', 'ShoppeSettingsController@updateCartSettings')->name('shoppe');
Route::post('/shoppe-settings/shipping', 'ShoppeSettingsController@updateShippingSettings')->name('shoppe');
Route::post('/shoppe-settings/checkout', 'ShoppeSettingsController@updateCheckoutSettings')->name('shoppe');
Route::post('/shoppe-settings/tax', 'ShoppeSettingsController@updateTaxSettings')->name('shoppe');
Route::put('/shoppe-settings/shipping-classes', 'ShoppeSettingsController@updateShippingClasses')->name('shoppe');
Route::post('/shoppe-settings/shipping-methods', 'ShoppeSettingsController@createShippingMethod')->name('shoppe');
Route::put('/shoppe-settings/shipping-methods', 'ShoppeSettingsController@updateShippingMethods')->name('shoppe');
Route::get('/shoppe-settings/shipping-methods/{id}', 'ShoppeSettingsController@getShippingMethod')->name('shoppe');
Route::post('/shoppe-settings/shipping-methods/{id}', 'ShoppeSettingsController@updateShippingMethod')->name('shoppe');
Route::delete('/shoppe-settings/shipping-methods/{id}', 'ShoppeSettingsController@deleteShippingMethod')->name('shoppe');
Route::get('/shoppe-settings/shipping-classes/delete/{id}', 'ShoppeSettingsController@deleteShippingClass')->name('shoppe');
Route::post('/shoppe-settings/shipping-method-classes/{id}', 'ShoppeSettingsController@updateShippingMethodClasses')->name('shoppe');
