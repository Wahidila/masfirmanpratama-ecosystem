<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;

/**
 * 301 redirects from the old WordPress URL structure (posts lived at the site
 * root, categories/tags under /category and /tag) to the new /blog/{slug}
 * structure, preserving SEO after migration. See PRD §10.4.
 */
class LegacyRedirectController extends Controller
{
    /**
     * Catch-all for old root-level post permalinks: /{slug} and /{slug}/.
     * Only redirects when the slug matches a real published post; otherwise 404.
     */
    public function post(string $slug): RedirectResponse
    {
        $exists = Post::published()->where('slug', $slug)->exists();

        abort_unless($exists, 404);

        return redirect()->route('blog.show', $slug, 301);
    }

    /**
     * Old /category/{slug} archive → new blog listing filtered by category.
     */
    public function category(string $slug): RedirectResponse
    {
        if (BlogCategory::where('slug', $slug)->exists()) {
            return redirect()->route('blog.index', ['category' => $slug], 301);
        }

        return redirect()->route('blog.index', [], 301);
    }

    /**
     * Old /tag/{slug} archive → blog index (no tag archive page in v1).
     */
    public function tag(string $slug): RedirectResponse
    {
        return redirect()->route('blog.index', [], 301);
    }
}
