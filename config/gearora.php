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
        'inspection_disk' => env('GEARORA_INSPECTION_PHOTO_DISK', 'local'),
        'inspection_photo_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'inspection_photo_max_kb' => (int) env('GEARORA_INSPECTION_PHOTO_MAX_KB', 5120),
        'instructions' => [
            'bank_name' => env('GEARORA_BANK_NAME'),
            'bank_account_number' => env('GEARORA_BANK_ACCOUNT_NUMBER'),
            'bank_account_name' => env('GEARORA_BANK_ACCOUNT_NAME'),
            'cash_location' => env('GEARORA_CASH_LOCATION'),
        ],
        // Metode otomatis (gateway) dinonaktifkan secara default sampai kredensial
        // Midtrans valid diisi operator. Lihat ADR-5.7.
        'gateway_enabled' => (bool) env('GEARORA_PAYMENT_GATEWAY_ENABLED', false),
    ],

    'midtrans' => [
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'sandbox_base_url' => env('MIDTRANS_SANDBOX_BASE_URL', 'https://api.sandbox.midtrans.com'),
        'production_base_url' => env('MIDTRANS_PRODUCTION_BASE_URL', 'https://api.midtrans.com'),
        'sandbox_snap_url' => env('MIDTRANS_SNAP_SANDBOX_URL', 'https://app.sandbox.midtrans.com/snap/v2/vtweb'),
        'production_snap_url' => env('MIDTRANS_SNAP_PRODUCTION_URL', 'https://app.midtrans.com/snap/v2/vtweb'),
        'expiry_minutes' => (int) env('MIDTRANS_EXPIRY_MINUTES', 60),
        'notification_url' => env('MIDTRANS_NOTIFICATION_URL'),
        // Timeout HTTP (detik) untuk request ke Midtrans.
        'http_timeout' => (int) env('MIDTRANS_HTTP_TIMEOUT', 20),
        // Gunakan provider tiruan (tanpa jaringan) untuk development tanpa kredensial.
        'fake' => (bool) env('MIDTRANS_FAKE', false),
    ],

    'refund' => [
        // Refund biaya sewa berdasarkan kebijakan (RENTAL_POLICY.md §7).
        'full_refund_hours' => (int) env('GEARORA_REFUND_FULL_HOURS', 48),
        'half_refund_hours' => (int) env('GEARORA_REFUND_HALF_HOURS', 24),
    ],

    'late_fee' => [
        'grace_minutes' => (int) env('GEARORA_LATE_GRACE_MINUTES', 60),
        // 100% harga sewa harian per hari keterlambatan.
        'daily_rate_percent' => (int) env('GEARORA_LATE_RATE_PERCENT', 100),
        'max_days' => (int) env('GEARORA_LATE_MAX_DAYS', 7),
    ],

    'uploads' => [
        'image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'image_max_kb' => (int) env('GEARORA_IMAGE_MAX_KB', 4096),
        'public_disk' => env('GEARORA_PUBLIC_DISK', 'public'),
    ],

    'trip' => [
        'max_results' => (int) env('GEARORA_TRIP_MAX_RESULTS', 12),
        'max_duration_days' => (int) env('GEARORA_TRIP_MAX_DAYS', 60),
        'max_participants' => (int) env('GEARORA_TRIP_MAX_PARTICIPANTS', 100),
    ],

    'guides' => [
        'cover_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'cover_max_kb' => (int) env('GEARORA_GUIDE_COVER_MAX_KB', 4096),
        'cover_disk' => env('GEARORA_GUIDE_COVER_DISK', 'public'),
        'cover_directory' => 'guide-covers',
        'max_content_kb' => (int) env('GEARORA_GUIDE_CONTENT_MAX_KB', 200),
    ],

    'sales' => [
        // Reservasi stok retail kedaluwarsa setelah N menit bila belum dibayar.
        'reservation_minutes' => (int) env('GEARORA_SALES_RESERVATION_MINUTES', 120),
        'max_quantity_per_line' => (int) env('GEARORA_SALES_MAX_QTY_PER_LINE', 99),
    ],

    'login' => [
        'max_attempts' => (int) env('GEARORA_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('GEARORA_LOGIN_DECAY_SECONDS', 60),
    ],

];
