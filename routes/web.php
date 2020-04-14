<?php

Route::get('/'.config('shoppe.slugs.store_landing', 'products'), ['uses' => 'ProductController@index', 'as' => 'products']);
Route::get('/'.config('shoppe.slugs.store_landing', 'products').'/{any}', ['uses' => 'ProductController@index', 'as' => 'products'])->where('any', '.*');
Route::get('/'.config('shoppe.slugs.product_single', 'products').'/{slug}', ['uses' => 'ProductController@get', 'as' => 'products']);

Route::post('/cart', ['uses' => 'CartController@create', 'as' => 'cart']);
Route::get('/cart', ['uses' => 'CartController@index', 'as' => 'cart']);
Route::put('/cart', ['uses' => 'CartController@update', 'as' => 'cart']);
Route::delete('/cart', ['uses' => 'CartController@delete', 'as' => 'cart']);

Route::get('/checkout', ['uses' => 'CheckoutController@index', 'as' => 'checkout']);
Route::post('/checkout', ['uses' => 'CheckoutController@processCheckout', 'as' => 'checkout']);
Route::get('/'.config('shoppe.slugs.order_complete', 'order-complete').'/{ref_id}', ['uses' => 'CheckoutController@checkoutSuccess', 'as' => 'order-complete']);

Route::get('/del-filter/{name}/{value}', 'ProductController@delFilter');

