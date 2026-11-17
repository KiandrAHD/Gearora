<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Rental
    |--------------------------------------------------------------------------
    |
    | Nilai baseline dari .docs/RENTAL_POLICY.md v1.0. Semua nilai dapat
    | dioverride melalui .env tanpa mengubah kode. Jangan hardcode nilai
    | kebijakan di controller; baca dari config ini.
    |
    */

    'rental' => [
        'min_days' => (int) env('GEARORA_RENTAL_MIN_DAYS', 1),
        'max_days' => (int) env('GEARORA_RENTAL_MAX_DAYS', 30),
        'horizon_days' => (int) env('GEARORA_RENTAL_HORIZON_DAYS', 90),
        'enforce_horizon' => (bool) env('GEARORA_RENTAL_ENFORCE_HORIZON', true),
        'operating_hours' => [
            'open' => env('GEARORA_RENTAL_OPEN_HOUR', '08:00'),
            'close' => env('GEARORA_RENTAL_CLOSE_HOUR', '20:00'),
        ],
        'pickup_window' => [
            'start' => env('GEARORA_RENTAL_PICKUP_START', '08:00'),
            'end' => env('GEARORA_RENTAL_PICKUP_END', '18:00'),
        ],
        'return_deadline' => env('GEARORA_RENTAL_RETURN_DEADLINE', '20:00'),
    ],

    'deposit' => [
        'percentage_of_daily_rate' => (int) env('GEARORA_DEPOSIT_PERCENT', 50),
        'minimum' => (int) env('GEARORA_DEPOSIT_MIN', 50000),
        'maximum' => (int) env('GEARORA_DEPOSIT_MAX', 2000000),
        'rounding' => (int) env('GEARORA_DEPOSIT_ROUNDING', 10000),
    ],

    'payment' => [
        'proof_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
        'proof_max_kb' => (int) env('GEARORA_PAYMENT_PROOF_MAX_KB', 5120),
        'proof_disk' => env('GEARORA_PAYMENT_PROOF_DISK', 'local'),
        'instructions' => [
            'bank_name' => env('GEARORA_BANK_NAME'),
            'bank_account_number' => env('GEARORA_BANK_ACCOUNT_NUMBER'),
            'bank_account_name' => env('GEARORA_BANK_ACCOUNT_NAME'),
            'cash_location' => env('GEARORA_CASH_LOCATION'),
        ],
    ],

    'uploads' => [
        'image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'image_max_kb' => (int) env('GEARORA_IMAGE_MAX_KB', 4096),
        'public_disk' => env('GEARORA_PUBLIC_DISK', 'public'),
    ],

    'login' => [
        'max_attempts' => (int) env('GEARORA_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('GEARORA_LOGIN_DECAY_SECONDS', 60),
    ],

];
