<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $search = trim((string) $request->query('q', ''));

        $query = Post::published()
            ->with(['categories', 'primaryCategory'])
            ->latest('published_at');

        if ($categorySlug) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        $query->search($search);

        $posts = $query->paginate(9)->withQueryString();

        $activeCategory = $categorySlug
            ? BlogCategory::where('slug', $categorySlug)->first()
            : null;

        return view('pages.blog.index', [
            'posts' => $posts,
            'categories' => BlogCategory::orderBy('name')->get(),
            'recentPosts' => Post::published()->latest('published_at')->take(5)->get(['id', 'title', 'slug', 'published_at']),
            'activeCategory' => $activeCategory,
            'search' => $search,
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::published()
            ->with(['categories', 'tags', 'products', 'primaryCategory'])
            ->where('slug', $slug)
            ->first();

        abort_if($post === null, 404);

        // Non-blocking view counter (avoid touching updated_at / firing events).
        Post::whereKey($post->id)->update(['views' => $post->views + 1]);

        $categoryIds = $post->categories->pluck('id');

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->when($categoryIds->isNotEmpty(), fn ($q) => $q->whereHas(
                'categories', fn ($c) => $c->whereIn('blog_categories.id', $categoryIds)
            ))
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'slug', 'image_path', 'excerpt', 'published_at']);

        return view('pages.blog.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'categories' => BlogCategory::orderBy('name')->get(),
            'recentPosts' => Post::published()->where('id', '!=', $post->id)->latest('published_at')->take(5)->get(['id', 'title', 'slug', 'published_at']),
        ]);
    }
}
