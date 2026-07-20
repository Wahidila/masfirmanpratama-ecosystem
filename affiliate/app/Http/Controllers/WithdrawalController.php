<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(): View
    {
        $affiliator = Auth::guard('affiliator')->user();

        $withdrawals = $affiliator->withdrawals()
            ->with('method')
            ->latest()
            ->paginate(10);

        return view('withdrawals.index', compact('withdrawals'));
    }

    public function create(): View
    {
        $affiliator = Auth::guard('affiliator')->user();
        $availableBalance = $affiliator->availableBalance();

        // Rekening yang metodenya sedang dinonaktifkan admin tidak ditawarkan.
        $accounts = $affiliator->payoutAccounts()
            ->with('method')
            ->whereHas('method', fn ($query) => $query->where('is_active', true))
            ->orderByDesc('is_primary')
            ->get();

        return view('withdrawals.create', compact('availableBalance', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $affiliator = Auth::guard('affiliator')->user();
        $availableBalance = $affiliator->availableBalance();

        $request->validate([
            // Terikat pemilik: ID rekening mudah ditebak, dan tanpa penjepitan ini
            // affiliator bisa menarik ke rekening milik orang lain.
            'payout_account_id' => [
                'required',
                Rule::exists('affiliator_payout_accounts', 'id')->where('affiliator_id', $affiliator->id),
            ],
            'amount' => ['required', 'numeric', 'min:1', "max:{$availableBalance}"],
        ], [
            'amount.required' => 'Jumlah penarikan wajib diisi.',
            'amount.min' => 'Jumlah penarikan minimal Rp 1.',
            'amount.max' => 'Jumlah penarikan melebihi saldo tersedia.',
            'payout_account_id.required' => 'Rekening tujuan wajib dipilih.',
            'payout_account_id.exists' => 'Rekening tujuan tidak ditemukan.',
        ]);

        $account = $affiliator->payoutAccounts()->with('method')->findOrFail($request->payout_account_id);
        $method = $account->method;

        // Metode bisa dinonaktifkan admin setelah halaman ini dibuka.
        if (! $method || ! $method->is_active) {
            return back()->withErrors([
                'payout_account_id' => 'Metode penarikan untuk rekening ini sedang tidak tersedia. Silakan pilih rekening lain.',
            ])->withInput();
        }

        if ($request->amount < $method->min_withdrawal) {
            return back()->withErrors([
                'amount' => "Minimum penarikan untuk {$method->name} adalah Rp ".number_format($method->min_withdrawal, 0, ',', '.'),
            ])->withInput();
        }

        // Check no pending withdrawal
        $pendingExists = $affiliator->withdrawals()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($pendingExists) {
            return back()->withErrors([
                'amount' => 'Anda masih memiliki penarikan yang sedang diproses.',
            ])->withInput();
        }

        // Biaya admin dipotong dari yang ditransfer, bukan dari saldo: saldo
        // berkurang sebesar bruto, affiliator menerima neto.
        $fee = $method->feeFor((float) $request->amount);
        $netAmount = $method->netAmountFor((float) $request->amount);

        DB::transaction(function () use ($affiliator, $request, $method, $account, $fee, $netAmount) {
            // Create withdrawal
            $withdrawal = Withdrawal::create([
                'affiliator_id' => $affiliator->id,
                'withdrawal_method_id' => $method->id,
                // Snapshot: rename metode di panel admin tidak boleh mengubah
                // bunyi riwayat penarikan lama.
                'method_name' => $method->name,
                'amount' => $request->amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'status' => 'pending',
            ]);

            // Mark commissions as withdrawn (FIFO)
            $remaining = $request->amount;
            $commissions = $affiliator->commissions()
                ->where('status', 'available')
                ->orderBy('available_at')
                ->get();

            foreach ($commissions as $commission) {
                if ($remaining <= 0) {
                    break;
                }

                $commission->update([
                    'status' => 'withdrawn',
                    'withdrawn_at' => now(),
                ]);
                $remaining -= $commission->amount;
            }

            // Activity log
            ActivityLog::create([
                'affiliator_id' => $affiliator->id,
                'action' => 'withdraw_request',
                'description' => 'Permintaan penarikan Rp '.number_format($request->amount, 0, ',', '.')." via {$method->name}",
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('withdrawals.index')
            ->with('success', 'Permintaan penarikan berhasil diajukan. Mohon tunggu proses verifikasi admin.');
    }
}
