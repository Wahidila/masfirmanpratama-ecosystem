<?php

namespace App\Services;

use App\Models\CourseParticipant;
use App\Models\Order;

/**
 * Sinkronisasi order kelas -> peserta kursus.
 *
 * Dipakai oleh listener SyncCourseParticipant (saat pembayaran diverifikasi)
 * dan command participants:backfill (untuk order lama).
 */
class CourseParticipantSync
{
    /**
     * Buat/perbarui peserta dari sebuah order.
     *
     * Return null kalau: bukan order kelas, ATAU pembayaran belum LUNAS.
     * Peserta baru masuk daftar kelas setelah cicilan lunas (total terbayar
     * penuh) — bukan setelah cicilan/DP pertama.
     */
    public function fromOrder(Order $order): ?CourseParticipant
    {
        $courseItem = $order->items()->whereNotNull('course_id')->first();
        if (! $courseItem) {
            return null;
        }

        $verified = (float) $order->payments()->where('status', 'verified')->sum('amount');
        $total = (float) $order->payableTotal();

        // Hanya enroll saat lunas (menutup total + kode unik). Cicilan/DP yang
        // belum menutup total tidak menjadikan pembeli peserta kelas.
        if ($verified <= 0 || $verified < $total) {
            return null;
        }

        $participant = CourseParticipant::firstOrNew(['order_id' => $order->id]);

        // Data kontak hanya diisi saat pembuatan supaya editan admin tidak tertimpa.
        if (! $participant->exists) {
            $meta = is_array($order->order_meta)
                ? $order->order_meta
                : (json_decode((string) $order->order_meta, true) ?: []);

            $participant->fill([
                'course_id' => $courseItem->course_id,
                'name' => $order->customer_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'occupation' => $meta['occupation'] ?? null,
                'motivation' => $meta['motivation'] ?? null,
                'status' => 'registered',
                'joined_at' => $order->created_at ?? now(),
            ]);
        }

        // Sampai sini berarti sudah lunas (guard di atas menolak yang belum).
        $participant->payment_status = 'lunas';
        $participant->save();

        return $participant;
    }
}
