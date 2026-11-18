<?php

namespace App\Support;

/**
 * Verifikasi signature & hash event webhook Midtrans.
 *
 * Dipakai bersama oleh notifikasi rental dan retail agar tidak ada duplikasi
 * logika keamanan (lihat ADR_SHARED_PAYMENT_FOR_RETAIL.md).
 */
final class MidtransSignature
{
    public static function isValid(string $orderId, string $statusCode, string $grossAmount, string $signature): bool
    {
        $serverKey = (string) config('gearora.midtrans.server_key');

        if ($serverKey === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function eventHash(array $payload): string
    {
        return hash('sha256', implode('|', [
            'midtrans',
            (string) ($payload['order_id'] ?? ''),
            (string) ($payload['transaction_id'] ?? ''),
            (string) ($payload['transaction_status'] ?? ''),
            (string) ($payload['fraud_status'] ?? ''),
            (string) ($payload['gross_amount'] ?? ''),
        ]));
    }

    /**
     * Hanya field non-rahasia yang disimpan untuk audit.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function safePayload(array $payload): array
    {
        $allowed = [
            'order_id', 'status_code', 'transaction_status', 'fraud_status',
            'transaction_id', 'payment_type', 'gross_amount', 'currency',
            'transaction_time', 'settlement_time',
        ];

        return array_intersect_key($payload, array_flip($allowed));
    }
}
