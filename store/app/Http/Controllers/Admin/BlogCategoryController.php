<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.blog-categories.index', [
            'categories' => BlogCategory::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        BlogCategory::create($data);

        return back()->with('status', "Kategori \"{$data['name']}\" ditambahkan.");
    }

    public function update(Request $request, BlogCategory $blogCategory): RedirectResponse
    {
        $data = $this->validateData($request, $blogCategory->id);

        $blogCategory->update($data);

        return back()->with('status', "Kategori \"{$blogCategory->name}\" diperbarui.");
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        $name = $blogCategory->name;
        $blogCategory->delete();

        return back()->with('status', "Kategori \"{$name}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'max:140',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_categories', 'slug')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', Rule::exists('blog_categories', 'id')],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'slug.unique' => 'Slug kategori sudah dipakai.',
            'slug.regex' => 'Slug hanya huruf kecil, angka, dan tanda hubung.',
        ]);
    }
}
