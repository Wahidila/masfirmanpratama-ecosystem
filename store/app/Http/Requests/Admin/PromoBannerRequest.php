<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PromoBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'link_url' => $this->input('link_url') ?: null,
            'active' => $this->boolean('active'),
            'sort_order' => (int) $this->input('sort_order', 0),
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Gambar wajib saat create; saat edit opsional (kosong = pertahankan lama).
        $isCreate = $this->route('promo_banner') === null;

        return [
            'title' => ['required', 'string', 'max:200'],
            'image' => [
                $isCreate ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096', // KB — banner lebar butuh ruang lebih dari foto biasa
            ],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul banner wajib diisi.',
            'image.required' => 'Gambar banner wajib diupload.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar: JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 4 MB.',
            'link_url.url' => 'Link tujuan harus URL valid (mis. https://wa.me/...).',
            'ends_at.after_or_equal' => 'Akhir tayang tidak boleh sebelum mulai tayang.',
        ];
    }
}
