<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Quick stats + listing produk dengan optional filter status, type, search,
     * dan view (active=default, trashed=onlyTrashed for archived view).
     */
    public function index(Request $request): View
    {
        $filterStatus = $request->query('status');
        $filterType = $request->query('type');
        $search = trim((string) $request->query('q', ''));
        $view = $request->query('view', 'active'); // 'active' (default) | 'trashed'

        $query = Product::query()->latest('id');

        // View toggle: trashed = onlyTrashed (soft-deleted), default = exclude trashed
        if ($view === 'trashed') {
            $query->onlyTrashed();
        }

        if (in_array($filterStatus, ['draft', 'active', 'archived'], true)) {
            $query->where('status', $filterStatus);
        }

        if (in_array($filterType, ['book', 'course'], true)) {
            $query->where('type', $filterType);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'draft' => Product::where('status', 'draft')->count(),
            'archived' => Product::where('status', 'archived')->count(),
            'trashed' => Product::onlyTrashed()->count(),
        ];

        return view('admin.products.index', [
            'products' => $products,
            'stats' => $stats,
            'filterStatus' => $filterStatus,
            'filterType' => $filterType,
            'search' => $search,
            'view' => $view,
        ]);
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
            'description' => $data['description'] ?? null,
            'meta_seo' => $this->buildMetaSeo($data),
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
            'description' => $data['description'] ?? null,
            'meta_seo' => $this->buildMetaSeo($data),
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $action = $data['action'];
        $ids = $data['ids'];

        // Untuk restore/force_delete kita perlu trashed records, action lain pakai active set
        $query = in_array($action, ['restore', 'force_delete'], true)
            ? Product::onlyTrashed()
            : Product::query();

        $products = $query->whereIn('id', $ids)->get();
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
        };

        return redirect()
            ->route('admin.products.index', $request->only(['view', 'status', 'type', 'q']))
            ->with('status', $message);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
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
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     */
    protected function bulkSoftDelete($products): string
    {
        foreach ($products as $product) {
            $product->delete();
        }

        return "{$products->count()} produk dipindahkan ke arsip (soft delete).";
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     */
    protected function bulkRestore($products): string
    {
        foreach ($products as $product) {
            $product->restore();
        }

        return "{$products->count()} produk berhasil dipulihkan.";
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
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
     * Simpan file gambar ke public disk under products/{slug}.
     * Pakai random hex filename biar tidak trust input client.
     */
    protected function storeImage(Request $request, string $slug): string
    {
        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = bin2hex(random_bytes(8)).'.'.$ext;

        $path = $file->storeAs("products/{$slug}", $filename, 'public');

        return $path; // relative path within the disk (e.g. products/slug-x/abcd.jpg)
    }

    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
