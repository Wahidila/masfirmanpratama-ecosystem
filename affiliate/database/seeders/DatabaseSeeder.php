<?php

namespace Database\Seeders;

use App\Models\Affiliator;
use App\Models\AffiliatorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AffiliatorTypeSeeder::class,
            WithdrawalMethodSeeder::class,
            CommissionSettingSeeder::class,
        ]);

        $this->seedDefaultAffiliator();
    }

    /**
     * Seed a ready-to-use affiliator (user) account for local development.
     *
     * Admin login is not stored in the database — it authenticates against
     * config/admin.php (ADMIN_EMAIL / ADMIN_PASSWORD in .env), so there is
     * nothing to seed here for the admin. Set those env vars instead.
     */
    private function seedDefaultAffiliator(): void
    {
        $type = AffiliatorType::where('slug', 'non-alumni')->first()
            ?? AffiliatorType::first();

        Affiliator::updateOrCreate(
            ['email' => 'affiliator@gmail.com'],
            [
                'affiliator_type_id' => $type?->id,
                'name' => 'Affiliator Demo',
                'password' => 'affiliator', // hashed via the model's cast
                'status' => 'active',
                'email_verified_at' => Carbon::now(),
                'approved_at' => Carbon::now(),
            ],
        );
    }
}
