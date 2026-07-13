<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.cron_trigger.token' => 'test-cron-token']);
    }

    public function test_runs_whitelisted_commands_with_valid_token(): void
    {
        $this->withHeaders(['X-Cron-Token' => 'test-cron-token'])
            ->postJson('/cron/run')
            ->assertOk()
            ->assertExactJson([
                'ok' => true,
                'ran' => ['commissions:release', 'events:finalize'],
            ]);
    }

    public function test_rejects_invalid_token(): void
    {
        $this->withHeaders(['X-Cron-Token' => 'salah'])
            ->postJson('/cron/run')
            ->assertStatus(401);
    }

    public function test_rejects_missing_token(): void
    {
        $this->postJson('/cron/run')->assertStatus(401);
    }

    public function test_rejects_when_token_not_configured(): void
    {
        config(['services.cron_trigger.token' => '']);

        $this->withHeaders(['X-Cron-Token' => 'apa-saja'])
            ->postJson('/cron/run')
            ->assertStatus(401);
    }
}
