<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

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
        $postId = $this->route('post')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($postId)->whereNull('deleted_at'),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:200000'],
            'image' => [
                'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096',
            ],
            'remove_image' => ['nullable', 'boolean'],
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
        return (new StorePostRequest)->messages();
    }
}
