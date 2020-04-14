<?php

Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account'), ['uses' => 'CustomerController@index', 'as' => 'customer.account']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/orders/{id}', ['uses' => 'CustomerController@order', 'as' => 'customer.account']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/security', ['uses' => 'CustomerController@security', 'as' => 'customer.security']);
Route::post('/'.config('shoppe.slugs.customer_account', 'customer-account').'/security/password', ['uses' => 'CustomerController@securityChangePassword', 'as' => 'customer.security']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses', ['uses' => 'CustomerController@addresses', 'as' => 'customer.addresses']);
Route::put('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses/{id}', ['uses' => 'CustomerController@addressUpdate', 'as' => 'customer.addresses']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses/{id}/default', ['uses' => 'CustomerController@addressDefault', 'as' => 'customer.addresses']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses/{id}/delete', ['uses' => 'CustomerController@addressDelete', 'as' => 'customer.addresses']);
Route::post('/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses', ['uses' => 'CustomerController@addressCreate', 'as' => 'customer.addresses']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/cards', ['uses' => 'CustomerController@cards', 'as' => 'customer.cards']);

Route::put('/'.config('shoppe.slugs.customer_account', 'customer-account').'/cards/{id}', ['uses' => 'CustomerController@cardsUpdate', 'as' => 'customer.cards']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/cards/{id}/delete', ['uses' => 'CustomerController@cardsDelete', 'as' => 'customer.cards']);
Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/cards/{id}/default', ['uses' => 'CustomerController@cardsDefault', 'as' => 'customer.cards']);
Route::post('/'.config('shoppe.slugs.customer_account', 'customer-account').'/cards', ['uses' => 'CustomerController@cardsCreate', 'as' => 'customer.cards']);

Route::get('/'.config('shoppe.slugs.customer_account', 'customer-account').'/subscriptions', ['uses' => 'CustomerController@getSubscriptions', 'as' => 'customer.subscriptions']);
Route::post('/'.config('shoppe.slugs.customer_account', 'customer-account').'/subscriptions/{id}/cancel', ['uses' => 'CustomerController@cancelSubscription', 'as' => 'customer.subscriptions']);
Route::post('/'.config('shoppe.slugs.customer_account', 'customer-account').'/subscriptions/{id}/reactivate', ['uses' => 'CustomerController@reactivateSubscription', 'as' => 'customer.subscriptions']);
