<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliator;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAffiliatorController extends Controller
{
    public function index(Request $request): View
    {
        $query = Affiliator::with('type');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $affiliators = $query->latest()->paginate(15);

        return view('admin.affiliators.index', compact('affiliators'));
    }

    public function show(Affiliator $affiliator): View
    {
        $affiliator->load(['type', 'referralCodes', 'commissions', 'withdrawals', 'payoutAccounts.method']);

        $stats = [
            'total_earnings' => $affiliator->totalEarnings(),
            'available_balance' => $affiliator->availableBalance(),
            'total_orders' => $affiliator->referralOrders()->count(),
            'total_clicks' => $affiliator->referralCodes()->withCount('clicks')->get()->sum('clicks_count'),
        ];

        return view('admin.affiliators.show', compact('affiliator', 'stats'));
    }

    public function approve(Affiliator $affiliator): RedirectResponse
    {
        $affiliator->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);

        Notification::create([
            'affiliator_id' => $affiliator->id,
            'type' => 'account_approved',
            'title' => 'Akun Disetujui',
            'message' => 'Selamat! Akun affiliate Anda telah disetujui. Anda sekarang bisa mulai membagikan link referral.',
        ]);

        return back()->with('success', "Affiliator {$affiliator->name} berhasil disetujui.");
    }

    public function suspend(Affiliator $affiliator): RedirectResponse
    {
        $affiliator->update(['status' => 'suspended']);

        Notification::create([
            'affiliator_id' => $affiliator->id,
            'type' => 'account_suspended',
            'title' => 'Akun Disuspend',
            'message' => 'Akun affiliate Anda telah disuspend oleh admin. Hubungi support untuk informasi lebih lanjut.',
        ]);

        return back()->with('success', "Affiliator {$affiliator->name} berhasil disuspend.");
    }

    public function reactivate(Affiliator $affiliator): RedirectResponse
    {
        $affiliator->update([
            'status' => 'active',
            'approved_at' => $affiliator->approved_at ?? now(),
        ]);

        return back()->with('success', "Affiliator {$affiliator->name} berhasil diaktifkan kembali.");
    }

    public function destroy(Affiliator $affiliator): RedirectResponse
    {
        $affiliator->delete();

        return redirect()->route('admin.affiliators.index')
            ->with('success', "Affiliator {$affiliator->name} berhasil dihapus.");
    }
}
