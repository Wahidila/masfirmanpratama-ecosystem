<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * Auto-fill slug from title when empty, normalise optional fields.
     */
    protected function prepareForValidation(): void
    {
        $title = (string) $this->input('title', '');
        $slugInput = (string) $this->input('slug', '');

        $slug = Str::slug($slugInput !== '' ? $slugInput : $title);

        $this->merge([
            'slug' => $slug,
            'meta_title' => $this->input('meta_title') ?: null,
            'meta_description' => $this->input('meta_description') ?: null,
            'published_at' => $this->input('published_at') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->whereNull('deleted_at'),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:200000'],
            'image' => [
                'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096',
            ],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('blog_categories', 'id')],
            'primary_category_id' => ['nullable', 'integer', Rule::exists('blog_categories', 'id')],
            'tags' => ['nullable', 'string', 'max:1000'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul artikel wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'slug.required' => 'Slug wajib diisi (otomatis dari judul kalau kosong).',
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung (mis. judul-artikel-keren).',
            'slug.unique' => 'Slug sudah dipakai artikel lain. Pilih yang berbeda.',
            'content.required' => 'Isi artikel wajib diisi.',
            'status.required' => 'Pilih status artikel.',
            'status.in' => 'Status hanya boleh draft, published, atau scheduled.',
            'published_at.required_if' => 'Tanggal tayang wajib diisi untuk artikel terjadwal (scheduled).',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar tidak didukung. Pakai JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar terlalu besar. Maksimal 4 MB.',
            'meta_title.max' => 'Meta title SEO maksimal 160 karakter.',
            'meta_description.max' => 'Meta description SEO maksimal 320 karakter.',
        ];
    }
}
