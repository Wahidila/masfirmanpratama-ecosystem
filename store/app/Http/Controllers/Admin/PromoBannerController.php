<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoBannerRequest;
use App\Models\PromoBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD banner promo/jadwal terdekat homepage. Banner sering ganti (event
 * kelas offline per kota/tanggal) sehingga harus dikelola dari dasbor,
 * bukan hardcode di blade.
 */
class PromoBannerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $filterStatus = $request->query('status'); // null|active|inactive

        $banners = PromoBanner::query()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($filterStatus === 'active', fn ($q) => $q->where('active', true))
            ->when($filterStatus === 'inactive', fn ($q) => $q->where('active', false))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // visible = lolos scopeVisible (aktif + dalam jendela tayang).
        $visibleIds = PromoBanner::visible()->pluck('id')->all();

        $stats = [
            'total' => PromoBanner::count(),
            'visible' => count($visibleIds),
            'active' => PromoBanner::where('active', true)->count(),
            'inactive' => PromoBanner::where('active', false)->count(),
        ];

        return view('admin.promo-banners.index', [
            'banners' => $banners,
            'visibleIds' => $visibleIds,
            'stats' => $stats,
            'search' => $search,
            'filterStatus' => in_array($filterStatus, ['active', 'inactive'], true) ? $filterStatus : null,
        ]);
    }

    public function create(): View
    {
        return view('admin.promo-banners.create', [
            'banner' => new PromoBanner(['active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(PromoBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image_path'] = $this->storeImage($request);
        unset($data['image']);

        PromoBanner::create($data);

        return redirect()
            ->route('admin.promo-banners.index')
            ->with('status', 'Banner berhasil ditambahkan.');
    }

    public function edit(PromoBanner $promoBanner): View
    {
        return view('admin.promo-banners.edit', [
            'banner' => $promoBanner,
        ]);
    }

    public function update(PromoBannerRequest $request, PromoBanner $promoBanner): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($promoBanner->image_path);
            $data['image_path'] = $this->storeImage($request);
        }
        unset($data['image']);

        $promoBanner->update($data);

        return redirect()
            ->route('admin.promo-banners.index')
            ->with('status', 'Banner berhasil diperbarui.');
    }

    public function destroy(PromoBanner $promoBanner): RedirectResponse
    {
        $this->deleteImage($promoBanner->image_path);
        $promoBanner->delete();

        return redirect()
            ->route('admin.promo-banners.index')
            ->with('status', 'Banner dihapus.');
    }

    /** Toggle aktif/nonaktif cepat dari list. */
    public function toggle(PromoBanner $promoBanner): RedirectResponse
    {
        $promoBanner->update(['active' => ! $promoBanner->active]);

        return back()->with('status',
            $promoBanner->active ? 'Banner diaktifkan.' : 'Banner dinonaktifkan.'
        );
    }

    /** Simpan upload ke disk public di folder banners/. */
    protected function storeImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = now()->format('YmdHis').'-'.Str::random(6).'.'.$file->getClientOriginalExtension();

        return $file->storeAs('banners', $filename, 'public');
    }

    /** Hapus file lama HANYA bila milik kita di disk public (folder banners/). */
    protected function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'banners/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
