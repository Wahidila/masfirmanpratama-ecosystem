<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

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
        $productId = $this->route('product')?->id;

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')
                    ->ignore($productId)
                    ->whereNull('deleted_at'),
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
                'max:2048',
                'dimensions:min_width=800,min_height=800',
            ],
            'remove_image' => ['nullable', 'boolean'],
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
        return (new StoreProductRequest)->messages();
    }
}
