<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionSetting;
use App\Models\ReferralCode;
use App\Models\ReferralOrder;
use App\Models\WebhookLog;
use App\Services\Gamification\EventScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk menerima webhook dari Store.
 * Verifikasi HMAC-SHA256, idempotency check, lalu proses event.
 */
class StoreWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Signature', '');
        $sourceIp = $request->ip();
        $secret = config('services.store_webhook.secret');

        // Fail-closed: tolak semua request kalau secret belum dikonfigurasi,
        // supaya hash_hmac dengan key kosong tidak bisa di-forge.
        if (empty($secret)) {
            WebhookLog::create([
                'event_type' => $request->header('X-Webhook-Event', 'unknown'),
                'payload' => json_decode($rawBody, true) ?? [],
                'signature' => $signature,
                'status' => 'invalid_signature',
                'error_message' => 'Webhook secret not configured',
                'source_ip' => $sourceIp,
            ]);

            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        // Verifikasi HMAC-SHA256
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        if (! hash_equals($expected, $signature)) {
            WebhookLog::create([
                'event_type' => $request->header('X-Webhook-Event', 'unknown'),
                'payload' => json_decode($rawBody, true) ?? [],
                'signature' => $signature,
                'status' => 'invalid_signature',
                'error_message' => 'Signature verification failed',
                'source_ip' => $sourceIp,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true);
        $eventType = $request->header('X-Webhook-Event', $payload['event'] ?? 'unknown');

        // Log received
        $webhookLog = WebhookLog::create([
            'event_type' => $eventType,
            'payload' => $payload,
            'signature' => $signature,
            'status' => 'received',
            'source_ip' => $sourceIp,
        ]);

        // Idempotency: cek apakah sudah pernah diproses
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $alreadyProcessed = WebhookLog::where('status', 'processed')
                ->where('event_type', $eventType)
                ->whereJsonContains('payload->idempotency_key', $idempotencyKey)
                ->where('id', '!=', $webhookLog->id)
                ->exists();

            if ($alreadyProcessed) {
                $webhookLog->update(['status' => 'processed', 'error_message' => 'Duplicate - idempotency skip']);

                return response()->json(['message' => 'Already processed']);
            }
        }

        return match ($eventType) {
            'order-paid' => $this->handleOrderPaid($payload, $webhookLog),
            'order-refunded' => $this->handleOrderRefunded($payload, $webhookLog),
            default => $this->handleUnknownEvent($eventType, $webhookLog),
        };
    }

    /**
     * Handle event order-paid: buat ReferralOrder + hitung komisi.
     */
    private function handleOrderPaid(array $payload, WebhookLog $webhookLog): JsonResponse
    {
        $storeOrderId = $payload['store_order_id'] ?? null;
        $refCode = $payload['ref_code'] ?? null;
        $buyerName = $payload['buyer_name'] ?? '';
        $buyerEmail = $payload['buyer_email'] ?? null;
        $orderTotal = (float) ($payload['order_total'] ?? 0);
        $productType = $payload['product_type'] ?? null;
        $orderedAt = $payload['ordered_at'] ?? now()->toIso8601String();

        // Idempotency tambahan: cek store_order_id di referral_orders
        if ($storeOrderId && ReferralOrder::where('store_order_id', $storeOrderId)->exists()) {
            $webhookLog->update(['status' => 'processed', 'error_message' => 'Duplicate store_order_id - skip']);

            return response()->json(['message' => 'Already processed']);
        }

        // Resolve referral code
        $referralCode = ReferralCode::where('code', $refCode)->where('is_active', true)->first();

        if (! $referralCode) {
            $webhookLog->update([
                'status' => 'failed',
                'error_message' => 'Referral code not found: '.$refCode,
            ]);

            return response()->json(['message' => 'Referral code not found']);
        }

        $affiliator = $referralCode->affiliator;

        // Self-referral guard: affiliator tidak boleh dapat komisi (atau mengerek skor
        // gamifikasi) dari pembelian dirinya sendiri. Deteksi via email pembeli == email
        // affiliator (case-insensitive).
        $isSelfReferral = $buyerEmail !== null
            && strcasecmp(trim($buyerEmail), trim((string) $affiliator->email)) === 0;

        // Fail-closed: tanpa email pembeli kita TIDAK bisa memastikan pembeli bukan si
        // affiliator sendiri. Jangan bayar komisi / kerek skor untuk order tak terverifikasi
        // — cukup catat di webhook_logs untuk ditinjau manual. (Sisi Store mewajibkan email
        // saat ada referral, jadi order referral yang sah selalu membawa email.)
        $buyerUnverifiable = trim((string) $buyerEmail) === '';

        if ($isSelfReferral || $buyerUnverifiable) {
            $webhookLog->update([
                'status' => 'processed',
                'error_message' => $isSelfReferral
                    ? 'Self-referral detected (buyer email matches affiliator), order & commission skipped'
                    : 'Buyer email missing — cannot verify self-referral, commission withheld',
            ]);

            return response()->json([
                'message' => $isSelfReferral
                    ? 'Self-referral detected, skipped'
                    : 'Buyer unverifiable, commission withheld',
            ]);
        }

        // Buat ReferralOrder
        $referralOrder = ReferralOrder::create([
            'referral_code_id' => $referralCode->id,
            'affiliator_id' => $affiliator->id,
            'store_order_id' => $storeOrderId,
            'buyer_name' => $buyerName,
            'buyer_email' => $buyerEmail,
            'order_total' => $orderTotal,
            'status' => 'paid',
            'ordered_at' => $orderedAt,
        ]);

        // Cari commission setting yang cocok (prioritas: type+product > type+null > null+product > global)
        $commissionSetting = $this->resolveCommissionSetting($affiliator->affiliator_type_id, $productType);

        if (! $commissionSetting) {
            $webhookLog->update([
                'status' => 'failed',
                'error_message' => 'No commission setting found for type_id='.$affiliator->affiliator_type_id.', product='.$productType,
            ]);

            return response()->json(['message' => 'No commission setting']);
        }

        // Guard min_amount
        if ($orderTotal < $commissionSetting->min_amount) {
            $webhookLog->update([
                'status' => 'processed',
                'error_message' => 'Order total below min_amount ('.$orderTotal.' < '.$commissionSetting->min_amount.'), commission skipped',
            ]);

            return response()->json(['message' => 'Below min amount, commission skipped']);
        }

        // Hitung dan buat commission
        $amount = $orderTotal * $commissionSetting->rate / 100;
        $availableAt = now()->addDays($commissionSetting->cooling_days);

        Commission::create([
            'affiliator_id' => $affiliator->id,
            'referral_order_id' => $referralOrder->id,
            'amount' => $amount,
            'rate_applied' => $commissionSetting->rate,
            'status' => 'cooling',
            'available_at' => $availableAt,
        ]);

        // Recompute skor gamifikasi (non-blocking — gagal scoring tidak boleh gagalkan webhook)
        try {
            app(EventScoringService::class)->recomputeForAffiliator($affiliator);
        } catch (\Throwable $e) {
            Log::error('Gamifikasi scoring gagal (order-paid)', [
                'affiliator_id' => $affiliator->id,
                'store_order_id' => $storeOrderId,
                'error' => $e->getMessage(),
            ]);
        }

        $webhookLog->update(['status' => 'processed']);

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle event order-refunded: update status ReferralOrder + cancel commission.
     */
    private function handleOrderRefunded(array $payload, WebhookLog $webhookLog): JsonResponse
    {
        $storeOrderId = $payload['store_order_id'] ?? null;

        $referralOrder = ReferralOrder::where('store_order_id', $storeOrderId)->first();

        if (! $referralOrder) {
            $webhookLog->update([
                'status' => 'failed',
                'error_message' => 'Referral order not found for store_order_id: '.$storeOrderId,
            ]);

            return response()->json(['message' => 'Referral order not found']);
        }

        // Update referral order status
        $referralOrder->update(['status' => 'refunded']);

        // Cancel commission yang masih cooling atau available (JANGAN yang sudah withdrawn)
        Commission::where('referral_order_id', $referralOrder->id)
            ->whereIn('status', ['cooling', 'available'])
            ->update(['status' => 'cancelled']);

        // Recompute skor gamifikasi (non-blocking)
        try {
            $affiliator = $referralOrder->affiliator;
            app(EventScoringService::class)->recomputeForAffiliator($affiliator);
        } catch (\Throwable $e) {
            Log::error('Gamifikasi scoring gagal (order-refunded)', [
                'affiliator_id' => $referralOrder->affiliator_id,
                'store_order_id' => $storeOrderId,
                'error' => $e->getMessage(),
            ]);
        }

        $webhookLog->update(['status' => 'processed']);

        return response()->json(['message' => 'Refund processed']);
    }

    /**
     * Handle event yang tidak dikenali.
     */
    private function handleUnknownEvent(string $eventType, WebhookLog $webhookLog): JsonResponse
    {
        $webhookLog->update([
            'status' => 'failed',
            'error_message' => 'Unknown event: '.$eventType,
        ]);

        return response()->json(['message' => 'Unknown event']);
    }

    /**
     * Resolve CommissionSetting dengan prioritas:
     * 1. affiliator_type_id match + product_type match
     * 2. affiliator_type_id match + product_type null
     * 3. affiliator_type_id null + product_type match
     * 4. global (keduanya null)
     */
    private function resolveCommissionSetting(?int $affiliatorTypeId, ?string $productType): ?CommissionSetting
    {
        // Prioritas 1: exact match (type + product)
        if ($affiliatorTypeId && $productType) {
            $setting = CommissionSetting::where('affiliator_type_id', $affiliatorTypeId)
                ->where('product_type', $productType)
                ->where('is_active', true)
                ->first();
            if ($setting) {
                return $setting;
            }
        }

        // Prioritas 2: type match, product null
        if ($affiliatorTypeId) {
            $setting = CommissionSetting::where('affiliator_type_id', $affiliatorTypeId)
                ->whereNull('product_type')
                ->where('is_active', true)
                ->first();
            if ($setting) {
                return $setting;
            }
        }

        // Prioritas 3: type null, product match
        if ($productType) {
            $setting = CommissionSetting::whereNull('affiliator_type_id')
                ->where('product_type', $productType)
                ->where('is_active', true)
                ->first();
            if ($setting) {
                return $setting;
            }
        }

        // Prioritas 4: global (keduanya null)
        return CommissionSetting::whereNull('affiliator_type_id')
            ->whereNull('product_type')
            ->where('is_active', true)
            ->first();
    }
}
