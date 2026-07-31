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
     * Return null kalau: bukan order kelas, ATAU belum ada pembayaran
     * terverifikasi (order belum bayar → tidak masuk daftar peserta).
     */
    public function fromOrder(Order $order): ?CourseParticipant
    {
        $courseItem = $order->items()->whereNotNull('course_id')->first();
        if (! $courseItem) {
            return null;
        }

        $verified = (float) $order->payments()->where('status', 'verified')->sum('amount');
        if ($verified <= 0) {
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

        // Selalu diperbarui: cicilan bisa berubah jadi lunas.
        $participant->payment_status = $verified >= (float) $order->total ? 'lunas' : 'cicil';
        $participant->save();

        return $participant;
    }
}
