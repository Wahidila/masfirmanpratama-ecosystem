<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Halaman tunggal Settings dengan 2 tab:
     * - store-info: nama/alamat/kota/telp/email/jam operasional
     * - bank-accounts: list rekening (CRUD inline)
     *
     * Tab dipilih via query string ?tab=store-info|bank-accounts.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'store-info');
        if (! in_array($tab, ['store-info', 'bank-accounts'], true)) {
            $tab = 'store-info';
        }

        return view('admin.settings.index', [
            'tab' => $tab,
            'storeInfo' => Settings::getStoreInfo(),
            'bankAccounts' => Settings::getBankAccounts(),
        ]);
    }

    /**
     * Update store info (tab 1). Single form, semua key di-set.
     */
    public function updateStoreInfo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'operating_hours' => ['nullable', 'string', 'max:200'],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        foreach ($data as $key => $value) {
            Settings::set('store.'.$key, $value ?? '', 'string');
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'store-info'])
            ->with('status', 'Store info berhasil diperbarui.');
    }

    /**
     * Replace seluruh list bank_accounts (full upsert pattern, simpler than
     * partial update — toh form admin selalu submit list lengkap).
     *
     * Format input: bank_accounts[] dengan sub-fields bank/number/holder/logo_color.
     */
    public function updateBankAccounts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank' => ['nullable', 'string', 'max:60'],
            'bank_accounts.*.number' => ['nullable', 'string', 'max:40'],
            'bank_accounts.*.holder' => ['nullable', 'string', 'max:120'],
            'bank_accounts.*.logo_color' => ['nullable', 'string', 'max:30'],
            'bank_accounts.*.primary' => ['nullable'],
        ]);

        // Validate inline: kalau partial-filled (bank tanpa number, dst), reject.
        foreach ($data['bank_accounts'] ?? [] as $idx => $acc) {
            $hasBank = ! empty($acc['bank']);
            $hasNumber = ! empty($acc['number']);
            if ($hasBank xor $hasNumber) {
                return back()->withInput()->withErrors([
                    "bank_accounts.{$idx}.bank" => 'Bank dan nomor rekening harus diisi keduanya, atau kosongkan keduanya.',
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $rawAccounts */
        $rawAccounts = $data['bank_accounts'] ?? [];

        $accounts = collect($rawAccounts)
            ->filter(fn ($acc) => ! empty($acc['bank']) && ! empty($acc['number']))
            ->map(fn ($acc) => [
                'bank' => $acc['bank'],
                'number' => $acc['number'],
                'holder' => $acc['holder'] ?? '',
                'logo_color' => $acc['logo_color'] ?? 'slate',
                'primary' => ! empty($acc['primary']),
            ])
            ->values()
            ->all();

        Settings::set('bank_accounts', $accounts, 'array');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'bank-accounts'])
            ->with('status', count($accounts).' rekening tersimpan.');
    }
}
