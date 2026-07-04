<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Post;
use App\Models\Product;
use App\Services\Blog\WxrImporter;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $filterStatus = $request->query('status');
        $filterCategory = $request->query('category');
        $search = trim((string) $request->query('q', ''));
        $view = $request->query('view', 'active'); // 'active' | 'trashed'

        $query = Post::query()->latest('id')->with('categories');

        if ($view === 'trashed') {
            $query->onlyTrashed();
        }

        if (in_array($filterStatus, ['draft', 'published', 'scheduled'], true)) {
            $query->where('status', $filterStatus);
        }

        if ($filterCategory) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $filterCategory));
        }

        $query->search($search);

        $posts = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Post::count(),
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'scheduled' => Post::where('status', 'scheduled')->count(),
            'trashed' => Post::onlyTrashed()->count(),
        ];

        return view('admin.posts.index', [
            'posts' => $posts,
            'stats' => $stats,
            'categories' => BlogCategory::orderBy('name')->get(),
            'filterStatus' => $filterStatus,
            'filterCategory' => $filterCategory,
            'search' => $search,
            'view' => $view,
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.create', [
            'post' => new Post(['status' => 'draft']),
            'categories' => BlogCategory::orderBy('name')->get(),
            'products' => Product::orderBy('title')->get(['id', 'title', 'type']),
            'selectedCategories' => [],
            'selectedProducts' => [],
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $post = new Post;
        $post->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => HtmlSanitizer::clean($data['content']),
            'status' => $data['status'],
            'published_at' => $this->resolvePublishedAt($data),
            'meta_seo' => $this->buildMetaSeo($data),
            'reading_minutes' => Post::estimateReadingMinutes($data['content']),
            'primary_category_id' => $data['primary_category_id'] ?? ($data['category_ids'][0] ?? null),
            'wp_author_login' => 'firmanp',
        ]);

        if ($request->hasFile('image')) {
            $post->image_path = $this->storeImage($request, $data['slug']);
        }

        $post->save();

        $this->syncTaxonomy($post, $data);

        return redirect()
            ->route('admin.posts.index')
            ->with('status', "Artikel \"{$post->title}\" berhasil ditambahkan.");
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('name')->get(),
            'products' => Product::orderBy('title')->get(['id', 'title', 'type']),
            'selectedCategories' => $post->categories()->pluck('blog_categories.id')->all(),
            'selectedProducts' => $post->products()->pluck('products.id')->all(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        $post->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => HtmlSanitizer::clean($data['content']),
            'status' => $data['status'],
            'published_at' => $this->resolvePublishedAt($data),
            'meta_seo' => $this->buildMetaSeo($data),
            'reading_minutes' => Post::estimateReadingMinutes($data['content']),
            'primary_category_id' => $data['primary_category_id'] ?? ($data['category_ids'][0] ?? null),
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($post->image_path);
            $post->image_path = $this->storeImage($request, $data['slug']);
        } elseif (! empty($data['remove_image'])) {
            $this->deleteImage($post->image_path);
            $post->image_path = null;
        }

        $post->save();

        $this->syncTaxonomy($post, $data);

        return redirect()
            ->route('admin.posts.index')
            ->with('status', "Artikel \"{$post->title}\" berhasil diperbarui.");
    }

    public function destroy(Post $post): RedirectResponse
    {
        $title = $post->title;
        $post->delete();

        return redirect()
            ->route('admin.posts.index')
            ->with('status', "Artikel \"{$title}\" dipindahkan ke arsip (soft delete).");
    }

    public function restore(string $slug): RedirectResponse
    {
        $post = Post::onlyTrashed()->where('slug', $slug)->firstOrFail();
        $post->restore();

        return redirect()
            ->route('admin.posts.index')
            ->with('status', "Artikel \"{$post->title}\" berhasil dipulihkan.");
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:publish,draft,soft_delete,restore,force_delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $action = $data['action'];

        $query = in_array($action, ['restore', 'force_delete'], true)
            ? Post::onlyTrashed()
            : Post::query();

        $posts = $query->whereIn('id', $data['ids'])->get();

        if ($posts->isEmpty()) {
            return back()->with('status', 'Tidak ada artikel yang cocok untuk diproses.');
        }

        $count = $posts->count();

        $message = match ($action) {
            'publish' => $this->bulkPublish($posts),
            'draft' => $this->bulkSetStatus($posts, 'draft'),
            'soft_delete' => $this->bulkSoftDelete($posts),
            'restore' => $this->bulkRestore($posts),
            'force_delete' => $this->bulkForceDelete($posts),
            default => 'Aksi tidak dikenal.',
        };

        return redirect()
            ->route('admin.posts.index', $request->only(['view', 'status', 'category', 'q']))
            ->with('status', $message);
    }

    // -----------------------------------------------------------------
    // WordPress import
    // -----------------------------------------------------------------

    public function importForm(): View
    {
        return view('admin.posts.import', [
            'result' => session('import_result'),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wxr' => ['required', 'file', 'mimes:xml,txt', 'max:51200'], // 50 MB
            'download_media' => ['nullable', 'boolean'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'wxr.required' => 'Pilih file export WordPress (.xml) dulu.',
            'wxr.mimes' => 'File harus berupa XML export WordPress (WXR).',
            'wxr.max' => 'File terlalu besar (maksimal 50 MB).',
        ]);

        $dryRun = (bool) ($data['dry_run'] ?? false);

        // A WXR import parses a large XML and (with media rehosting on) downloads
        // every image over HTTP — a real blog easily blows past the default 30s
        // max_execution_time and dies with a FatalError. This is an admin-only,
        // on-demand migration, so let it run to completion and keep going even if
        // the browser tab gives up mid-way.
        @set_time_limit(0);
        @ignore_user_abort(true);

        $importer = new WxrImporter(
            downloadMedia: (bool) ($data['download_media'] ?? false),
            force: false,
            dryRun: $dryRun,
        );

        try {
            $result = $importer->import($request->file('wxr')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('status', 'Import gagal: '.$e->getMessage());
        }

        $s = $result['summary'];

        if ($dryRun) {
            return back()->with('import_result', $result)
                ->with('status', 'Preview (dry-run) selesai — belum ada perubahan disimpan.');
        }

        $msg = sprintf(
            'Import selesai: %d artikel baru, %d diperbarui, %d kategori, %d tag, %d media, %d link internal dirapikan.',
            $s['posts_created'], $s['posts_updated'], $s['categories'], $s['tags'], $s['media_downloaded'], $s['links_relinked'] ?? 0,
        );

        return redirect()->route('admin.posts.index')->with('status', $msg);
    }

    // -----------------------------------------------------------------
    // Bulk helpers
    // -----------------------------------------------------------------

    /** @param Collection<int, Post> $posts */
    protected function bulkPublish($posts): string
    {
        foreach ($posts as $post) {
            $post->status = 'published';
            if (blank($post->published_at)) {
                $post->published_at = now();
            }
            $post->save();
        }

        return "{$posts->count()} artikel berhasil dipublish.";
    }

    /** @param Collection<int, Post> $posts */
    protected function bulkSetStatus($posts, string $status): string
    {
        foreach ($posts as $post) {
            $post->status = $status;
            $post->save();
        }

        return "{$posts->count()} artikel di-set ke {$status}.";
    }

    /** @param Collection<int, Post> $posts */
    protected function bulkSoftDelete($posts): string
    {
        foreach ($posts as $post) {
            $post->delete();
        }

        return "{$posts->count()} artikel dipindahkan ke arsip (soft delete).";
    }

    /** @param Collection<int, Post> $posts */
    protected function bulkRestore($posts): string
    {
        foreach ($posts as $post) {
            $post->restore();
        }

        return "{$posts->count()} artikel berhasil dipulihkan.";
    }

    /** @param Collection<int, Post> $posts */
    protected function bulkForceDelete($posts): string
    {
        $count = 0;
        foreach ($posts as $post) {
            $this->deleteImage($post->image_path);
            $post->forceDelete();
            $count++;
        }

        return "{$count} artikel dihapus permanen.";
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolvePublishedAt(array $data): ?string
    {
        return match ($data['status']) {
            'published' => $data['published_at'] ?? now()->toDateTimeString(),
            'scheduled' => $data['published_at'] ?? null,
            default => null, // draft
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncTaxonomy(Post $post, array $data): void
    {
        $post->categories()->sync($data['category_ids'] ?? []);
        $post->products()->sync($data['product_ids'] ?? []);
        $post->tags()->sync($this->resolveTagIds($data['tags'] ?? null));
    }

    /**
     * Parse comma-separated tag input into BlogTag ids (firstOrCreate by slug).
     *
     * @return list<int>
     */
    protected function resolveTagIds(?string $tagsInput): array
    {
        if (blank($tagsInput)) {
            return [];
        }

        return collect(explode(',', $tagsInput))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->map(function (string $name) {
                $slug = Str::slug($name);
                if ($slug === '') {
                    return null;
                }

                return BlogTag::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    protected function buildMetaSeo(array $data): ?array
    {
        $meta = array_filter([
            'title' => $data['meta_title'] ?? null,
            'description' => $data['meta_description'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return $meta ?: null;
    }

    protected function storeImage(Request $request, string $slug): string
    {
        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = bin2hex(random_bytes(8)).'.'.$ext;

        $path = $file->storeAs("posts/{$slug}", $filename, 'public');

        return 'storage/'.$path;
    }

    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        // Only manage assets we host under the public disk; never touch rehosted
        // blog media shared by other posts is out of scope here (per-post upload).
        $diskPath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        if (str_starts_with($diskPath, 'posts/') && Storage::disk('public')->exists($diskPath)) {
            Storage::disk('public')->delete($diskPath);
        }
    }
}
