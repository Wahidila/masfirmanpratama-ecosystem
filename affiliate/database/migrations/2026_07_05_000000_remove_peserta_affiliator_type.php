<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tipe affiliator disederhanakan jadi 2: alumni & non-alumni.
     * Affiliator bertipe peserta dipindah ke non-alumni (masih mengikuti
     * program = belum alumni), restriksi materi (allowed_types) di-remap
     * peserta -> non-alumni, lalu tipe peserta beserta commission
     * setting-nya dihapus dari database.
     */
    public function up(): void
    {
        $peserta = DB::table('affiliator_types')->where('slug', 'peserta')->first();

        if (! $peserta) {
            return;
        }

        $nonAlumniId = DB::table('affiliator_types')->where('slug', 'non-alumni')->value('id');

        if (! $nonAlumniId) {
            $nonAlumniId = DB::table('affiliator_types')->insertGetId([
                'name' => 'Non-Alumni',
                'slug' => 'non-alumni',
                'description' => 'Affiliator umum yang belum mengikuti program AMC',
                'benefits' => json_encode(['Komisi standar', 'Akses materi marketing dasar', 'Support via grup']),
                'default_commission_rate' => 10.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('affiliators')
            ->where('affiliator_type_id', $peserta->id)
            ->update(['affiliator_type_id' => $nonAlumniId]);

        DB::table('commission_settings')
            ->where('affiliator_type_id', $peserta->id)
            ->delete();

        // materials.allowed_types = JSON array id tipe — remap id peserta ke
        // non-alumni (bukan dihapus) supaya restriksi materi tetap konsisten
        // dengan reassignment affiliator di atas
        DB::table('materials')->whereNotNull('allowed_types')->get()->each(function ($material) use ($peserta, $nonAlumniId) {
            $allowed = array_map('intval', json_decode($material->allowed_types, true) ?: []);

            if (! in_array((int) $peserta->id, $allowed, true)) {
                return;
            }

            $remapped = array_values(array_unique(array_map(
                fn ($id) => $id === (int) $peserta->id ? (int) $nonAlumniId : $id,
                $allowed
            )));

            DB::table('materials')->where('id', $material->id)->update([
                'allowed_types' => json_encode($remapped),
            ]);
        });

        DB::table('affiliator_types')->where('id', $peserta->id)->delete();
    }

    public function down(): void
    {
        // Reassignment affiliator tidak bisa dibalik — tipe peserta sengaja
        // tidak dibuat ulang karena sistem hanya mengenal 2 tipe.
    }
};
