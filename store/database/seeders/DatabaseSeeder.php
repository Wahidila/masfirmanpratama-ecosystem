<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order penting:
     * 1. AdminSeeder              — referenced by OrderPayment.verified_by (FK)
     * 2. SettingsSeeder           — k/v store, tidak punya FK
     * 3. ProductSeeder            — referenced by OrderItem.product_id + InstallmentScheme.product_id (BUKU only)
     * 4. CourseSeeder             — referenced by OrderItem.course_id + InstallmentScheme.course_id (KELAS)
     * 5. InstallmentSchemeSeeder  — depend on Product + Course (skema 12x untuk kelas reguler)
     * 6. OrderSeeder              — depend on Product + Course, ngerangkai Order + OrderItem + OrderPayment
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingsSeeder::class,
            ProductSeeder::class,
            CourseSeeder::class,
            InstallmentSchemeSeeder::class,
            OrderSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
