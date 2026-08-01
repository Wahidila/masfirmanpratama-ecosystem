<?php

namespace Tests\Feature\Shipping;

use App\Services\Shipping\AgenwebsiteClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgenwebsiteClientTest extends TestCase
{
    public function test_sends_wordpress_user_agent_and_form_body_to_license_endpoint(): void
    {
        Http::fake([
            '*/license/activate' => Http::response([
                'data' => ['type' => 'exclusive', 'shipping_quota' => 'Unlimited'],
                'message' => 'Berhasil terkoneksi dengan Agenwebsite',
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->activateLicense();

        $this->assertSame('success', $result['status']);
        Http::assertSent(function ($request) {
            return str_contains($request->header('User-Agent')[0], 'WordPress/')
                && $request['product'] === 'agenwebsite-shipping'
                && $request->hasHeader('site-url');
        });
    }

    /**
     * REGRESSION: API bisa balas validation bag ala Laravel di mana `message`/
     * `errors` berupa ARRAY, bukan string. Kalau array itu diteruskan apa adanya
     * dan bocor ke Blade {{ }} → htmlspecialchars(): array given → 500.
     * post() harus selalu mengembalikan `message` berupa STRING (errors bag
     * di-flatten jadi satu baris yang terbaca).
     */
    public function test_coerces_array_error_message_to_string(): void
    {
        Http::fake([
            '*/license/activate' => Http::response([
                'message' => ['The given data was invalid.'],
                'errors' => [
                    'receiver.postcode' => ['Kode pos penerima wajib diisi.'],
                    'signed_key' => ['Signed key tidak valid.'],
                ],
            ], 422),
        ]);

        $result = app(AgenwebsiteClient::class)->activateLicense();

        $this->assertSame('error', $result['status']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('Kode pos penerima wajib diisi.', $result['message']);
        $this->assertStringContainsString('Signed key tidak valid.', $result['message']);
    }
}
