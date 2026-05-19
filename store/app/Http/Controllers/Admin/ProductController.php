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
     * Quick stats + listing produk dengan optional filter status & type.
     */
    public function index(Request $request): View
    {
        $filterStatus = $request->query('status');
        $filterType = $request->query('type');
        $search = trim((string) $request->query('q', ''));

        $query = Product::query()->latest('id');

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
        ];

        return view('admin.products.index', [
            'products' => $products,
            'stats' => $stats,
            'filterStatus' => $filterStatus,
            'filterType' => $filterType,
            'search' => $search,
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
        $product->delete(); // soft delete

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Produk \"{$title}\" dipindahkan ke arsip (soft delete).");
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
