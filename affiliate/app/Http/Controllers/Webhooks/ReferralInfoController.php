<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Affiliator;
use App\Models\ReferralCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lookup nama affiliator dari kode referral.
 *
 * Dipanggil server-to-server oleh app Store (detail order admin) untuk
 * menampilkan siapa yang mereferralkan sebuah order. Diproteksi HMAC-SHA256
 * atas {code} memakai secret yang sama dengan webhook (services.store_webhook.secret
 * = AFFILIATE_WEBHOOK_SECRET di Store). Fail-closed kalau secret kosong.
 */
class ReferralInfoController extends Controller
{
    public function show(Request $request, string $code): JsonResponse
    {
        $secret = (string) config('services.store_webhook.secret');

        if ($secret === '') {
            return response()->json(['message' => 'Not configured'], 503);
        }

        $expected = 'sha256='.hash_hmac('sha256', $code, $secret);
        if (! hash_equals($expected, (string) $request->header('X-Signature', ''))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $referralCode = ReferralCode::where('code', $code)->first();

        if (! $referralCode || ! ($affiliator = Affiliator::find($referralCode->affiliator_id))) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'code' => $code,
            'affiliator_name' => $affiliator->name,
            'status' => $affiliator->status,
        ]);
    }
}
