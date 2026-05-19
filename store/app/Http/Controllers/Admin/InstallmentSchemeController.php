<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallmentScheme;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallmentSchemeController extends Controller
{
    public function index(Request $request): View
    {
        $filterScope = $request->query('scope'); // null|global|product
        $search = trim((string) $request->query('q', ''));

        $query = InstallmentScheme::query()
            ->with('product:id,title,slug')
            ->orderByRaw('product_id IS NULL DESC') // global first
            ->orderBy('n_installments');

        if ($filterScope === 'global') {
            $query->whereNull('product_id');
        } elseif ($filterScope === 'product') {
            $query->whereNotNull('product_id');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        $schemes = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => InstallmentScheme::count(),
            'active' => InstallmentScheme::where('active', true)->count(),
            'global' => InstallmentScheme::whereNull('product_id')->count(),
            'product' => InstallmentScheme::whereNotNull('product_id')->count(),
        ];

        return view('admin.installment-schemes.index', [
            'schemes' => $schemes,
            'stats' => $stats,
            'filterScope' => $filterScope,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.installment-schemes.create', [
            'scheme' => new InstallmentScheme([
                'dp_pct' => 30,
                'n_installments' => 3,
                'interval_days' => 30,
                'active' => true,
            ]),
            'products' => $this->productOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateScheme($request);

        InstallmentScheme::create($data);

        return redirect()
            ->route('admin.installment-schemes.index')
            ->with('status', 'Skema cicilan berhasil ditambahkan.');
    }

    public function edit(InstallmentScheme $installmentScheme): View
    {
        return view('admin.installment-schemes.edit', [
            'scheme' => $installmentScheme,
            'products' => $this->productOptions(),
        ]);
    }

    public function update(Request $request, InstallmentScheme $installmentScheme): RedirectResponse
    {
        $data = $this->validateScheme($request);

        $installmentScheme->update($data);

        return redirect()
            ->route('admin.installment-schemes.index')
            ->with('status', 'Skema cicilan berhasil diperbarui.');
    }

    public function destroy(InstallmentScheme $installmentScheme): RedirectResponse
    {
        $installmentScheme->delete();

        return redirect()
            ->route('admin.installment-schemes.index')
            ->with('status', 'Skema cicilan dihapus.');
    }

    /**
     * Toggle active flag (quick action di list).
     */
    public function toggle(InstallmentScheme $installmentScheme): RedirectResponse
    {
        $installmentScheme->update(['active' => ! $installmentScheme->active]);

        return back()->with('status',
            $installmentScheme->active
                ? 'Skema diaktifkan.'
                : 'Skema dinonaktifkan.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateScheme(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'dp_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'n_installments' => ['required', 'integer', 'min:1', 'max:36'],
            'interval_days' => ['required', 'integer', 'min:0', 'max:365'],
            'active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama skema wajib diisi.',
            'dp_pct.max' => 'DP maksimum 100%.',
            'n_installments.min' => 'Jumlah pembayaran minimal 1 (lunas).',
        ]);

        $validated['active'] = (bool) ($validated['active'] ?? false);

        return $validated;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function productOptions()
    {
        return Product::query()
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
    }
}
