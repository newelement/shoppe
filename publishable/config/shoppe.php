<?php
return [
    'controllers' => [
        'namespace' => 'Newelement\\Shoppe\\Http\\Controllers',
    ],

    'currency' => 'USD',

    /*
        You can name your products landing page.
        products, store, shop ... whatever you want.
        Make sure the entry for store_landing type exists.
    */
    'slugs' => [
        'store_landing' => 'products',
        'product_single' => 'products',
        'checkout_success' => 'thank-you',
    ],

    'pagination_limits' => [
        'products' => 20,
    ],

    'shippo_api_token' => env('APP_ENV') === 'production'? env('SHIPPO_API_TOKEN') : env('SHIPPO_API_TOKEN_DEV'),

    'avalara_user' => env('APP_ENV') === 'production'? env('AVALARA_USER') : env('AVALARA_USER_DEV'),
    'avalara_pass' => env('APP_ENV') === 'production'? env('AVALARA_PASS') : env('AVALARA_PASS_DEV'),

    'taxjar_token_live' => env('TAXJAR_TOKEN_LIVE'),

    'AUTHORIZE_NET_USER' => env('APP_ENV') === 'production'? env('AUTHORIZE_NET_USER') : env('AUTHORIZE_NET_USER_DEV'),
    'AUTHORIZE_NET_PASS' => env('APP_ENV') === 'production'? '' : '',

    'STRIPE_USER' => '',
    'STRIPE_PASS' => '',

    'SQUARE_USER' => '',
    'SQUARE_PASS' => '',

    'shipping_connector' => 'Newelement\\Shoppe\\Http\\Controllers\\ShippingController@getShippingCosts',
    'taxes_connector' => 'Newelement\\Shoppe\\Http\\Controllers\\TaxesController@getTaxes',
    'payment_connector' => 'Newelement\\Shoppe\\Http\\Controllers',

];
