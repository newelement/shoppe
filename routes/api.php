<?php

Route::post('/taxes', 'CheckoutController@getTaxes');
Route::post('/shipping', 'CheckoutController@getShipping');
