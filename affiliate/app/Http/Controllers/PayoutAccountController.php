<?php

namespace App\Http\Controllers;

use App\Models\AffiliatorPayoutAccount;
use App\Models\WithdrawalMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rekening tujuan penarikan milik affiliator.
 *
 * Disimpan sekali lalu dipilih saat menarik, menggantikan pengetikan nomor
 * rekening berulang di tiap pengajuan.
 */
class PayoutAccountController extends Controller
{
    public function index(): View
    {
        $affiliator = Auth::guard('affiliator')->user();

        $accounts = $affiliator->payoutAccounts()
            ->with('method')
            ->orderByDesc('is_primary')
            ->latest()
            ->get();

        return view('payout-accounts.index', [
            'accounts' => $accounts,
            'methods' => WithdrawalMethod::active()->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $affiliator = Auth::guard('affiliator')->user();

        $validated = $request->validate([
            // Hanya metode yang sedang diaktifkan admin yang boleh disimpan.
            'withdrawal_method_id' => [
                'required',
                Rule::exists('withdrawal_methods', 'id')->where('is_active', true),
            ],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:100'],
        ], [
            'withdrawal_method_id.required' => 'Metode wajib dipilih.',
            'withdrawal_method_id.exists' => 'Metode tersebut sedang tidak tersedia.',
            'account_number.required' => 'Nomor rekening / no. HP wajib diisi.',
            'account_name.required' => 'Nama pemilik wajib diisi.',
        ]);

        $duplicate = $affiliator->payoutAccounts()
            ->where('withdrawal_method_id', $validated['withdrawal_method_id'])
            ->where('account_number', $validated['account_number'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['account_number' => 'Rekening ini sudah tersimpan.'])->withInput();
        }

        $affiliator->payoutAccounts()->create([
            ...$validated,
            // Rekening pertama otomatis jadi utama, supaya form penarikan selalu
            // punya pilihan terpilih.
            'is_primary' => ! $affiliator->payoutAccounts()->exists(),
        ]);

        return redirect()->route('payout-accounts.index')
            ->with('success', 'Rekening tujuan berhasil ditambahkan.');
    }

    public function setPrimary(AffiliatorPayoutAccount $payoutAccount): RedirectResponse
    {
        $affiliator = $this->authorizeOwnership($payoutAccount);

        $affiliator->payoutAccounts()->update(['is_primary' => false]);
        $payoutAccount->update(['is_primary' => true]);

        return redirect()->route('payout-accounts.index')
            ->with('success', 'Rekening utama berhasil diubah.');
    }

    public function destroy(AffiliatorPayoutAccount $payoutAccount): RedirectResponse
    {
        $affiliator = $this->authorizeOwnership($payoutAccount);

        $wasPrimary = $payoutAccount->is_primary;
        $payoutAccount->delete();

        // Jangan tinggalkan affiliator tanpa rekening utama.
        if ($wasPrimary) {
            $affiliator->payoutAccounts()->oldest()->first()?->update(['is_primary' => true]);
        }

        return redirect()->route('payout-accounts.index')
            ->with('success', 'Rekening tujuan berhasil dihapus.');
    }

    /**
     * Rekening hanya boleh disentuh pemiliknya — ID milik affiliator lain
     * mudah ditebak dan tidak terlindungi oleh route model binding.
     */
    private function authorizeOwnership(AffiliatorPayoutAccount $account)
    {
        $affiliator = Auth::guard('affiliator')->user();

        abort_unless($account->affiliator_id === $affiliator->id, 403);

        return $affiliator;
    }
}
