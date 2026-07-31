<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\InstallmentScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Skema cicilan — HANYA untuk kelas/kursus. Skema bisa global (berlaku semua
 * kelas) atau dikunci ke satu kelas. Produk/buku tidak punya cicilan.
 */
class InstallmentSchemeController extends Controller
{
    public function index(Request $request): View
    {
        $filterScope = $request->query('scope'); // null|global|course
        $filterCourse = $request->integer('course') ?: null; // filter ke 1 kelas
        $search = trim((string) $request->query('q', ''));

        $query = InstallmentScheme::query()
            ->with('course:id,title,slug')
            ->orderByRaw('course_id IS NULL DESC') // global dulu
            ->orderBy('n_installments');

        if ($filterCourse !== null) {
            $query->where('course_id', $filterCourse);
        } elseif ($filterScope === 'global') {
            $query->whereNull('course_id');
        } elseif ($filterScope === 'course') {
            $query->whereNotNull('course_id');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            });
        }

        $schemes = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => InstallmentScheme::count(),
            'active' => InstallmentScheme::where('active', true)->count(),
            'global' => InstallmentScheme::whereNull('course_id')->count(),
            'course' => InstallmentScheme::whereNotNull('course_id')->count(),
        ];

        return view('admin.installment-schemes.index', [
            'schemes' => $schemes,
            'stats' => $stats,
            'filterScope' => $filterScope,
            'filterCourseModel' => $filterCourse ? Course::find($filterCourse) : null,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.installment-schemes.create', [
            'scheme' => new InstallmentScheme([
                'course_id' => $request->integer('course') ?: null,
                'dp_pct' => 30,
                'n_installments' => 3,
                'interval_days' => 30,
                'active' => true,
            ]),
            'courses' => $this->courseOptions(),
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
            'courses' => $this->courseOptions(),
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
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'dp_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'n_installments' => ['required', 'integer', 'min:1', 'max:36'],
            'interval_days' => ['required', 'integer', 'min:0', 'max:365'],
            'active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama skema wajib diisi.',
            'course_id.exists' => 'Kelas yang dipilih tidak valid.',
            'dp_pct.max' => 'DP maksimum 100%.',
            'n_installments.min' => 'Jumlah pembayaran minimal 1 (lunas).',
        ]);

        $validated['course_id'] = $validated['course_id'] ?? null;
        $validated['active'] = (bool) ($validated['active'] ?? false);

        return $validated;
    }

    /**
     * Kelas aktif untuk dropdown scope skema.
     *
     * @return Collection<int, Course>
     */
    protected function courseOptions()
    {
        return Course::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
    }
}
