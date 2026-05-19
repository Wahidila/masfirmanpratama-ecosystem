<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * Auto-fill slug dari title kalau kosong, dan normalisasi
     * sebelum validation. Slug yang user input juga di-kebab-case.
     */
    protected function prepareForValidation(): void
    {
        $title = (string) $this->input('title', '');
        $slugInput = (string) $this->input('slug', '');

        $slug = $slugInput !== '' ? $slugInput : $title;
        $slug = Str::slug($slug);

        $this->merge([
            'slug' => $slug,
            'meta_title' => $this->input('meta_title') ?: null,
            'meta_description' => $this->input('meta_description') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::in(['book', 'course'])],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // KB → 2 MB
                'dimensions:min_width=800,min_height=800',
            ],
            'description' => ['nullable', 'string', 'max:8000'],
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
            'title.required' => 'Judul produk wajib diisi.',
            'title.max' => 'Judul maksimal 200 karakter.',

            'slug.required' => 'Slug wajib diisi (otomatis dari judul kalau kosong).',
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung (mis. judul-produk-keren).',
            'slug.unique' => 'Slug sudah dipakai produk lain. Pilih yang berbeda.',
            'slug.max' => 'Slug maksimal 200 karakter.',

            'type.required' => 'Pilih tipe produk dulu (buku atau kelas).',
            'type.in' => 'Tipe produk harus buku atau kelas.',

            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus angka.',
            'price.min' => 'Harga tidak boleh negatif.',
            'price.max' => 'Harga melebihi batas maksimum.',

            'stock.required' => 'Stok wajib diisi (isi 0 kalau habis).',
            'stock.integer' => 'Stok harus angka bulat.',
            'stock.min' => 'Stok tidak boleh negatif.',

            'status.required' => 'Pilih status produk.',
            'status.in' => 'Status hanya boleh draft, active, atau archived.',

            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar tidak didukung. Pakai JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar terlalu besar. Maksimal 2 MB.',
            'image.dimensions' => 'Resolusi gambar minimal 800 × 800 piksel.',

            'description.max' => 'Deskripsi maksimal 8.000 karakter.',
            'meta_title.max' => 'Meta title SEO maksimal 160 karakter.',
            'meta_description.max' => 'Meta description SEO maksimal 320 karakter.',
        ];
    }
}
