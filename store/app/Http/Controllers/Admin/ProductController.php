<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** Ambang "stok menipis" untuk badge & filter (produk fisik). */
    public const LOW_STOCK_THRESHOLD = 5;

    /** Kolom yang boleh dipakai sorting (whitelist — cegah SQL injection via ?sort). */
    public const SORTABLE = ['title', 'price', 'stock', 'created_at'];

    /**
     * Quick stats + listing produk dengan optional filter status, type, stok,
     * search, sorting, dan view (active=default, trashed=onlyTrashed).
     */
    public function index(Request $request): View
    {
        $view = $request->query('view', 'active'); // 'active' (default) | 'trashed'

        $query = Product::query();
        if ($view === 'trashed') {
            $query->onlyTrashed();
        }
        $this->applyFilters($query, $request);

        // Sorting whitelist; default = terbaru (id desc).
        [$sort, $dir] = $this->resolveSort($request);
        if ($sort !== null) {
            $query->orderBy($sort, $dir);
        } else {
            $query->latest('id');
        }

        $products = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'draft' => Product::where('status', 'draft')->count(),
            'archived' => Product::where('status', 'archived')->count(),
            'trashed' => Product::onlyTrashed()->count(),
        ];

        // Tipe yang benar-benar ada (filter tipe hanya berguna kalau katalog campuran;
        // toko ini praktis semua 'book' — kelas di modul terpisah).
        $types = Product::query()->select('type')->distinct()->orderBy('type')->pluck('type')->all();

        // Total baris yang cocok filter (untuk "pilih semua N sesuai filter").
        $filteredTotal = $products->total();

        return view('admin.products.index', [
            'products' => $products,
            'stats' => $stats,
            'filterStatus' => $request->query('status'),
            'filterType' => $request->query('type'),
            'filterStock' => $request->query('stock'),
            'search' => trim((string) $request->query('q', '')),
            'view' => $view,
            'sort' => $sort,
            'dir' => $dir,
            'types' => $types,
            'filteredTotal' => $filteredTotal,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    /**
     * Terapkan filter status/type/stok/search ke query. Dipakai bersama index()
     * (listing) & bulk() (mode "pilih semua sesuai filter"). TIDAK menangani
     * view/trashed — caller yang atur (onlyTrashed) sesuai konteks aksi.
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        // input() (bukan query()) supaya jalan untuk GET (listing) & POST bulk
        // (filter datang sebagai hidden input di body form saat "pilih semua").
        $status = $request->input('status');
        if (in_array($status, ['draft', 'active', 'archived'], true)) {
            $query->where('status', $status);
        }

        $type = $request->input('type');
        if (in_array($type, ['book', 'course'], true)) {
            $query->where('type', $type);
        }

        // Stok hanya bermakna untuk produk fisik — samakan dgn badge (yang hanya
        // menandai is_shippable). Non-shippable (mis. digital) tak masuk filter stok.
        $stock = $request->input('stock');
        if ($stock === 'out') {
            $query->where('is_shippable', true)->where('stock', '<=', 0);
        } elseif ($stock === 'low') {
            $query->where('is_shippable', true)
                ->where('stock', '>', 0)
                ->where('stock', '<=', self::LOW_STOCK_THRESHOLD);
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Resolve sort+dir dari request dengan whitelist. Return [null, dir] kalau
     * kolom tak valid (caller pakai default).
     *
     * @return array{0: ?string, 1: string}
     */
    protected function resolveSort(Request $request): array
    {
        $dir = strtolower((string) $request->query('dir')) === 'asc' ? 'asc' : 'desc';
        $sort = $request->query('sort');

        return in_array($sort, self::SORTABLE, true) ? [$sort, $dir] : [null, $dir];
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product(['status' => 'draft', 'stock' => 0]),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $product = new Product;
        $product->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'type' => $data['type'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'status' => $data['status'],
            'weight_kg' => $data['weight_kg'],
            'description' => $data['description'] ?? null,
            'meta_seo' => $this->buildMetaSeo($data),
            'specs' => $this->buildSpecs($request),
        ]);

        if ($request->hasFile('image')) {
            $product->image_path = $this->storeImage($request, $data['slug']);
        }

        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Produk \"{$product->title}\" berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $product->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'type' => $data['type'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'status' => $data['status'],
            'weight_kg' => $data['weight_kg'],
            'description' => $data['description'] ?? null,
            'meta_seo' => $this->buildMetaSeo($data),
            'specs' => $this->buildSpecs($request),
        ]);

        // Replace image
        if ($request->hasFile('image')) {
            $this->deleteImage($product->image_path);
            $product->image_path = $this->storeImage($request, $data['slug']);
        } elseif (! empty($data['remove_image'])) {
            $this->deleteImage($product->image_path);
            $product->image_path = null;
        }

        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Produk \"{$product->title}\" berhasil diperbarui.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $title = $product->title;
        $product->delete(); // soft delete (deleted_at terisi)

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Produk \"{$title}\" dipindahkan ke arsip (soft delete).");
    }

    /**
     * Restore produk yang sudah soft-deleted.
     * Route: POST /admin/products/{slug}/restore
     */
    public function restore(string $slug): RedirectResponse
    {
        $product = Product::onlyTrashed()->where('slug', $slug)->firstOrFail();
        $product->restore();

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Produk \"{$product->title}\" berhasil dipulihkan.");
    }

    /**
     * Bulk action di index list. Format request: action + ids[].
     * Action: archive (status='archived'), activate (status='active'),
     *         soft_delete (delete()), restore (restore()), force_delete (force).
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:archive,activate,soft_delete,restore,force_delete'],
            'select_all' => ['nullable', 'boolean'],
            // ids wajib KECUALI mode "pilih semua sesuai filter". max:1000 cegah
            // silent-truncate PHP max_input_vars saat seleksi manual sangat besar.
            'ids' => [Rule::requiredIf(fn () => ! $request->boolean('select_all')), 'array', 'max:1000'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $action = $data['action'];
        $selectAll = $request->boolean('select_all');

        // Untuk restore/force_delete kita perlu trashed records, action lain pakai active set
        $base = in_array($action, ['restore', 'force_delete'], true)
            ? Product::onlyTrashed()
            : Product::query();

        if ($selectAll) {
            // Semua produk yang cocok filter SAAT INI (bukan cuma 20 di halaman) —
            // pakai filter yang sama dengan listing supaya aksi massal konsisten.
            $products = $this->applyFilters($base, $request)->get();
        } else {
            $products = $base->whereIn('id', $data['ids'] ?? [])->get();
        }

        $count = $products->count();

        if ($count === 0) {
            return back()->with('status', 'Tidak ada produk yang cocok untuk diproses.');
        }

        $message = match ($action) {
            'archive' => $this->bulkUpdateStatus($products, 'archived'),
            'activate' => $this->bulkUpdateStatus($products, 'active'),
            'soft_delete' => $this->bulkSoftDelete($products),
            'restore' => $this->bulkRestore($products),
            'force_delete' => $this->bulkForceDelete($products),
            default => 'Aksi tidak dikenal.',
        };

        return redirect()
            ->route('admin.products.index', $request->only(['view', 'status', 'q', 'type', 'stock', 'sort', 'dir']))
            ->with('status', $message);
    }

    /**
     * Toggle status cepat dari list tanpa buka halaman Edit: active <-> archived,
     * draft -> active (publish). Tidak menyentuh soft-delete.
     */
    public function toggleStatus(Request $request, Product $product): RedirectResponse
    {
        $product->status = $product->status === 'active' ? 'archived' : 'active';
        $product->save();

        $label = $product->status === 'active' ? 'diaktifkan' : 'diarsipkan';

        return back()->with('status', "Produk \"{$product->title}\" {$label}.");
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    protected function bulkUpdateStatus($products, string $status): string
    {
        foreach ($products as $product) {
            $product->status = $status;
            $product->save();
        }

        $label = $status === 'active' ? 'diaktifkan' : 'di-archive';

        return "{$products->count()} produk berhasil {$label}.";
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    protected function bulkSoftDelete($products): string
    {
        foreach ($products as $product) {
            $product->delete();
        }

        return "{$products->count()} produk dipindahkan ke arsip (soft delete).";
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    protected function bulkRestore($products): string
    {
        foreach ($products as $product) {
            $product->restore();
        }

        return "{$products->count()} produk berhasil dipulihkan.";
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    protected function bulkForceDelete($products): string
    {
        $count = 0;
        foreach ($products as $product) {
            // Cleanup image dulu sebelum hard delete
            $this->deleteImage($product->image_path);
            $product->forceDelete();
            $count++;
        }

        return "{$count} produk dihapus permanen.";
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    protected function buildMetaSeo(array $data): ?array
    {
        $title = $data['meta_title'] ?? null;
        $desc = $data['meta_description'] ?? null;

        if (! $title && ! $desc) {
            return null;
        }

        return array_filter([
            'title' => $title,
            'description' => $desc,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Build specs array from parallel specs_keys[] + specs_values[] inputs.
     * Skips rows where both key and value are empty.
     *
     * @return array<string, string>|null
     */
    protected function buildSpecs(Request $request): ?array
    {
        $keys = $request->input('specs_keys', []);
        $values = $request->input('specs_values', []);

        if (! is_array($keys) || ! is_array($values)) {
            return null;
        }

        $specs = [];
        foreach ($keys as $index => $key) {
            $key = trim((string) $key);
            $value = trim((string) ($values[$index] ?? ''));

            if ($key === '' && $value === '') {
                continue;
            }

            if ($key !== '') {
                $specs[$key] = $value;
            }
        }

        return $specs ?: null;
    }

    /**
     * Simpan file gambar ke public disk under products/{slug}.
     * Pakai random hex filename biar tidak trust input client.
     */
    protected function storeImage(Request $request, string $slug): string
    {
        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = bin2hex(random_bytes(8)).'.'.$ext;

        $path = $file->storeAs("products/{$slug}", $filename, 'public');

        // Prepend storage/ so asset($image_path) resolves to /storage/products/...
        return 'storage/'.$path;
    }

    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        // Strip storage/ prefix before addressing the disk (disk uses paths relative to storage/app/public)
        $diskPath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        if (Storage::disk('public')->exists($diskPath)) {
            Storage::disk('public')->delete($diskPath);
        }
    }
}
