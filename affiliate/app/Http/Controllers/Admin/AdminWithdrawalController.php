<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $query = Withdrawal::with(['affiliator', 'method']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->latest()->paginate(15);

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    public function approve(Withdrawal $withdrawal): RedirectResponse
    {
        $withdrawal->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        Notification::create([
            'affiliator_id' => $withdrawal->affiliator_id,
            'type' => 'withdrawal_completed',
            'title' => 'Penarikan Diproses',
            // Yang ditransfer adalah neto; menyebut bruto di sini bikin affiliator
            // mengira menerima lebih banyak dari yang masuk ke rekeningnya.
            'message' => $withdrawal->fee > 0
                ? 'Penarikan Rp '.number_format($withdrawal->amount, 0, ',', '.').' telah diproses. Rp '.number_format($withdrawal->net_amount, 0, ',', '.').' ditransfer ke rekening Anda setelah biaya admin Rp '.number_format($withdrawal->fee, 0, ',', '.').'.'
                : 'Penarikan sebesar Rp '.number_format($withdrawal->net_amount, 0, ',', '.').' telah ditransfer ke rekening Anda.',
        ]);

        return back()->with('success', 'Penarikan berhasil disetujui dan ditandai selesai.');
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $request->validate(['admin_note' => 'required|string|max:500']);

        // Return commissions to available
        $withdrawal->affiliator->commissions()
            ->where('status', 'withdrawn')
            ->where('withdrawn_at', '>=', $withdrawal->created_at)
            ->update(['status' => 'available', 'withdrawn_at' => null]);

        $withdrawal->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note,
            'processed_at' => now(),
        ]);

        Notification::create([
            'affiliator_id' => $withdrawal->affiliator_id,
            'type' => 'withdrawal_rejected',
            'title' => 'Penarikan Ditolak',
            'message' => 'Penarikan Rp '.number_format($withdrawal->amount, 0, ',', '.')." ditolak. Alasan: {$request->admin_note}. Saldo dikembalikan.",
        ]);

        return back()->with('success', 'Penarikan ditolak. Saldo dikembalikan ke affiliator.');
    }
}
