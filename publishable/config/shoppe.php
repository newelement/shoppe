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
        'order_complete' => 'order-complete',
        'customer_account' => 'customer-account',
    ],

    'pagination_limits' => [
        'products' => 20,
    ],

    'shippo_api_token' => env('APP_ENV') === 'production'? env('SHIPPO_API_TOKEN') : env('SHIPPO_API_TOKEN_DEV'),

    'avalara_user' => env('APP_ENV') === 'production'? env('AVALARA_USER') : env('AVALARA_USER_DEV'),
    'avalara_pass' => env('APP_ENV') === 'production'? env('AVALARA_PASS') : env('AVALARA_PASS_DEV'),

    'taxjar_token_live' => env('TAXJAR_TOKEN_LIVE'),

    'authorize_net_user' => env('APP_ENV') === 'production'? env('AUTHORIZE_NET_USER') : env('AUTHORIZE_NET_USER_DEV'),
    'authorize_net_pass' => env('APP_ENV') === 'production'? env('AUTHORIZE_NET_PASS') : env('AUTHORIZE_NET_PASS_DEV'),

    'stripe_key' => env('APP_ENV') === 'production'? env('STRIPE_KEY') : env('STRIPE_KEY_TEST'),
    'stripe_secret' => env('APP_ENV') === 'production'? env('STRIPE_SECRET') : env('STRIPE_SECRET_TEST') ,

    'SQUARE_USER' => '',
    'SQUARE_PASS' => '',

    'shipping_connector' => '\\Newelement\\Shoppe\\Connectors\\Shipping',
    'taxes_connector' => '\\Newelement\\Shoppe\\Connectors\\AvalaraConnector',
    'payment_connector' => '\\Newelement\\Shoppe\\Connectors\\Payment',

];
