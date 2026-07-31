<?php

namespace Tests\Feature;

use App\Services\Webhook\AffiliateLookupClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AffiliateLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhooks/store',
            'webhook.secret' => 'test-secret',
            'webhook.timeout' => 5,
        ]);
    }

    public function test_returns_affiliator_name_and_signs_request(): void
    {
        Http::fake([
            '*/referral-info/*' => Http::response(['affiliator_name' => 'Naufal Ulinnuha', 'status' => 'active'], 200),
        ]);

        $name = app(AffiliateLookupClient::class)->affiliatorName('HUQJUMKG');

        $this->assertSame('Naufal Ulinnuha', $name);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://affiliate.test/referral-info/HUQJUMKG')
                && str_starts_with($request->header('X-Signature')[0] ?? '', 'sha256=');
        });
    }

    public function test_caches_result(): void
    {
        Http::fake(['*/referral-info/*' => Http::response(['affiliator_name' => 'Naufal'], 200)]);

        $client = app(AffiliateLookupClient::class);
        $client->affiliatorName('HUQJUMKG');
        $client->affiliatorName('HUQJUMKG');

        Http::assertSentCount(1);
    }

    public function test_does_not_cache_failure(): void
    {
        Http::fake(['*/referral-info/*' => Http::sequence()
            ->push(['message' => 'Not found'], 404)
            ->push(['affiliator_name' => 'Naufal'], 200)]);

        $client = app(AffiliateLookupClient::class);
        $this->assertNull($client->affiliatorName('HUQJUMKG'));   // gagal -> tidak di-cache
        $this->assertSame('Naufal', $client->affiliatorName('HUQJUMKG'));

        Http::assertSentCount(2);
    }

    public function test_returns_null_when_config_empty(): void
    {
        config(['webhook.affiliate_url' => '', 'webhook.secret' => '']);
        Http::fake();

        $this->assertNull(app(AffiliateLookupClient::class)->affiliatorName('HUQJUMKG'));
        Http::assertNothingSent();
    }
}
