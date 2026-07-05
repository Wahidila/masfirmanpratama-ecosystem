<?php

namespace App\Services\Installment;

use App\Models\Order;
use Illuminate\Support\Carbon;

/**
 * Computes the installment (cicilan) plan for a course order: which payments
 * are DP vs angsuran, paid vs outstanding, their due dates, and which one is
 * next. Used to (a) decide whether the admin "Reminder Cicilan" action applies
 * and (b) build the WhatsApp reminder content.
 *
 * Model: a cicilan order carries an order_meta.installment snapshot
 * {scheme_name, dp_pct, n_installments, interval_days} and >1 OrderPayment
 * (DP first, then N angsuran). Due dates are NOT stored per payment — DP is due
 * at checkout and angsuran i is due at checkout + i*interval_days (mirrors the
 * schedule shown on the course checkout-success page).
 */
class InstallmentReminder
{
    public const DEFAULT_INTERVAL_DAYS = 30;

    private const SECONDS_PER_DAY = 86400;

    /**
     * A cicilan order carries the installment snapshot in order_meta. As a
     * fallback for orders created before that snapshot existed, a COURSE order
     * with >1 scheduled payment (DP + angsuran) also counts — but never a book
     * order (installment is course-only; book proofs update one payment row).
     */
    public function isInstallment(Order $order): bool
    {
        if (data_get($order->order_meta, 'installment') !== null) {
            return true;
        }

        return str_starts_with((string) $order->order_number, 'COURSE-')
            && $order->payments->count() > 1;
    }

    /**
     * Ordered plan steps (DP first, then each angsuran).
     *
     * @return list<array{
     *   index:int, label:string, amount:float, status:string,
     *   due_date:?Carbon, is_next:bool, overdue_days:int
     * }>
     */
    public function schedule(Order $order): array
    {
        $payments = $order->payments->sortBy('id')->values();
        if ($payments->isEmpty()) {
            return [];
        }

        $interval = $this->intervalDays($order);
        $checkout = $order->created_at ? $order->created_at->copy() : now();
        $now = now();

        $nextChosen = false;
        $steps = [];

        foreach ($payments as $i => $payment) {
            $due = $i === 0 ? $checkout->copy() : $checkout->copy()->addDays($i * $interval);
            $isPaid = $payment->status === 'verified';

            $isNext = false;
            if (! $nextChosen && ! $isPaid) {
                $isNext = true;
                $nextChosen = true;
            }

            $overdueDays = 0;
            if (! $isPaid && $now->greaterThan($due)) {
                // Timestamp math is Carbon-version-agnostic (avoids diffInDays
                // sign/return-type differences between Carbon 2 and 3).
                $overdueDays = (int) floor(($now->getTimestamp() - $due->getTimestamp()) / self::SECONDS_PER_DAY);
            }

            $steps[] = [
                'index' => $i,
                'label' => $i === 0 ? 'DP' : 'Cicilan ke-'.$i,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'due_date' => $due,
                'is_next' => $isNext,
                'overdue_days' => $overdueDays,
            ];
        }

        return $steps;
    }

    /**
     * The first not-yet-verified step (the payment the customer owes next).
     *
     * @return array{index:int, label:string, amount:float, status:string, due_date:?Carbon, is_next:bool, overdue_days:int}|null
     */
    public function nextDue(Order $order): ?array
    {
        foreach ($this->schedule($order) as $step) {
            if ($step['is_next']) {
                return $step;
            }
        }

        return null;
    }

    /** Sisa = total order − jumlah pembayaran terverifikasi. */
    public function remaining(Order $order): float
    {
        $verified = (float) $order->payments->where('status', 'verified')->sum('amount');

        return max(0, (float) $order->total - $verified);
    }

    public function paidCount(Order $order): int
    {
        return $order->payments->where('status', 'verified')->count();
    }

    public function totalCount(Order $order): int
    {
        return $order->payments->count();
    }

    /** Masih ada angsuran belum lunas (dan sisa > 0). */
    public function hasOutstanding(Order $order): bool
    {
        return $this->isInstallment($order)
            && $this->remaining($order) > 0
            && $order->payments->contains(fn ($p) => $p->status !== 'verified');
    }

    private function intervalDays(Order $order): int
    {
        $interval = (int) data_get($order->order_meta, 'installment.interval_days', self::DEFAULT_INTERVAL_DAYS);

        return $interval > 0 ? $interval : self::DEFAULT_INTERVAL_DAYS;
    }
}
