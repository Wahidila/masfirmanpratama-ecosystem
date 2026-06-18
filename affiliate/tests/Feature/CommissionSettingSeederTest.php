<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use Database\Seeders\AffiliatorTypeSeeder;
use Database\Seeders\CommissionSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionSettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_global_fallback_setting(): void
    {
        $this->seed(AffiliatorTypeSeeder::class);
        $this->seed(CommissionSettingSeeder::class);

        // Global fallback: type null + product null
        $this->assertDatabaseHas('commission_settings', [
            'affiliator_type_id' => null,
            'product_type' => null,
            'rate' => 8.00,
            'cooling_days' => 7,
            'is_active' => true,
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AffiliatorTypeSeeder::class);

        $this->seed(CommissionSettingSeeder::class);
        $countAfterFirst = CommissionSetting::count();

        // Run again — tidak boleh menggandakan baris
        $this->seed(CommissionSettingSeeder::class);
        $countAfterSecond = CommissionSetting::count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
        $this->assertGreaterThan(0, $countAfterFirst);
    }
}
