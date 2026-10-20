<?php

return [
    'webhook_timeout_minutes' => (int) env('DEMO_WEBHOOK_TIMEOUT_MINUTES', 15),
    'admin' => [
        'name' => env('DEMO_ADMIN_NAME', 'Payment Admin'),
        'email' => env('DEMO_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('DEMO_ADMIN_PASSWORD', 'password'),
    ],
    'merchant' => [
        'owner_name' => env('DEMO_MERCHANT_OWNER_NAME', 'Demo Merchant'),
        'email' => env('DEMO_MERCHANT_EMAIL', 'merchant@example.com'),
        'password' => env('DEMO_MERCHANT_PASSWORD', 'password'),
        'store_name' => env('DEMO_MERCHANT_STORE_NAME', 'ABC Store'),
    ],
    'offer' => [
        'name' => env('DEMO_OFFER_NAME', 'Demo Item'),
        'amount' => env('DEMO_OFFER_AMOUNT', '1000.00'),
    ],
];
