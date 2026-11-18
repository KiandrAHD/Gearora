<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint notifikasi webhook Midtrans.
 *
 * Tidak memerlukan login, memakai rate limiting, dan TIDAK mematikan CSRF global
 * (route berada di grup `api` yang tidak memakai middleware web/CSRF).
 */
class MidtransNotificationController extends Controller
{
    public function __construct(private readonly PaymentNotificationService $notifications) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if ($payload === []) {
            return response()->json(['message' => 'Payload kosong.'], 400);
        }

        $result = $this->notifications->handle($payload);
        $status = $result['status'];

        $http = match ($status) {
            'invalid_signature' => 401,
            'unknown_attempt' => 404,
            'amount_mismatch', 'currency_mismatch' => 422,
            'failed' => 500,
            default => 200,
        };

        return response()->json([
            'status' => $status,
            'event_id' => $result['event_id'] ?? null,
        ], $http);
    }
}
