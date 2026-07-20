<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan metode penarikan yang boleh dipakai affiliator.
 *
 * Daftarnya sudah dipakai form penarikan sejak awal, tapi sebelumnya hanya bisa
 * diubah lewat database. Menonaktifkan sebuah metode menyembunyikannya dari
 * dropdown sekaligus menolak pengajuan yang mengarah padanya
 * (lihat WithdrawalController::store()).
 */
class AdminWithdrawalMethodController extends Controller
{
    public function index(): View
    {
        $methods = WithdrawalMethod::withCount(['withdrawals', 'payoutAccounts'])
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.withdrawal-methods.index', compact('methods'));
    }

    public function create(): View
    {
        return view('admin.withdrawal-methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $validated['is_active'] = $request->boolean('is_active');

        WithdrawalMethod::create($validated);

        return redirect()->route('admin.withdrawal-methods.index')
            ->with('success', 'Metode penarikan berhasil dibuat.');
    }

    public function edit(WithdrawalMethod $withdrawalMethod): View
    {
        return view('admin.withdrawal-methods.edit', ['method' => $withdrawalMethod]);
    }

    public function update(Request $request, WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        $validated = $request->validate(
            $this->rules($withdrawalMethod),
            $this->messages()
        );

        $validated['is_active'] = $request->boolean('is_active');

        $withdrawalMethod->update($validated);

        return redirect()->route('admin.withdrawal-methods.index')
            ->with('success', 'Metode penarikan berhasil diperbarui.');
    }

    public function toggle(WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        $withdrawalMethod->update(['is_active' => ! $withdrawalMethod->is_active]);

        return redirect()->route('admin.withdrawal-methods.index')
            ->with('success', $withdrawalMethod->is_active
                ? "Metode {$withdrawalMethod->name} berhasil diaktifkan."
                : "Metode {$withdrawalMethod->name} berhasil dinonaktifkan.");
    }

    public function destroy(WithdrawalMethod $withdrawalMethod): RedirectResponse
    {
        // Foreign key dari withdrawals tanpa cascade: menghapus metode yang sudah
        // terpakai membuat riwayat penarikan tidak bisa dirender. Nonaktifkan saja.
        if ($withdrawalMethod->isInUse()) {
            return redirect()->route('admin.withdrawal-methods.index')
                ->with('error', "Metode {$withdrawalMethod->name} sudah dipakai pada riwayat penarikan atau rekening tersimpan. Nonaktifkan saja agar riwayat tetap utuh.");
        }

        $name = $withdrawalMethod->name;
        $withdrawalMethod->delete();

        return redirect()->route('admin.withdrawal-methods.index')
            ->with('success', "Metode {$name} berhasil dihapus.");
    }

    /**
     * @return array<string, string>
     */
    private function rules(?WithdrawalMethod $method = null): array
    {
        $unique = 'unique:withdrawal_methods,name'.($method ? ','.$method->id : '');

        return [
            'name' => 'required|string|max:100|'.$unique,
            'type' => 'required|in:'.implode(',', array_keys(WithdrawalMethod::TYPES)),
            'min_withdrawal' => 'required|numeric|min:1',
            // Biaya wajib lebih kecil dari minimum, kalau tidak affiliator bisa
            // menerima nol atau angka negatif.
            'fee_flat' => 'required|numeric|min:0|lt:min_withdrawal',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'Nama metode wajib diisi.',
            'name.unique' => 'Metode dengan nama ini sudah ada.',
            'type.required' => 'Tipe metode wajib dipilih.',
            'type.in' => 'Tipe metode tidak valid.',
            'min_withdrawal.required' => 'Minimum penarikan wajib diisi.',
            'min_withdrawal.min' => 'Minimum penarikan harus lebih dari nol.',
            'fee_flat.required' => 'Biaya admin wajib diisi. Isi 0 kalau tidak ada biaya.',
            'fee_flat.lt' => 'Biaya admin harus lebih kecil dari minimum penarikan.',
        ];
    }
}
