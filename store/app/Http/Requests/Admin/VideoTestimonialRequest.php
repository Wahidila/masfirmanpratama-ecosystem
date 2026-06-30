<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->input('role') ?: null,
            'poster_url' => $this->input('poster_url') ?: null,
            'show_on_homepage' => $this->boolean('show_on_homepage'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'participant_name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'video_url' => ['required', 'url', 'max:2048'],
            'poster_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'show_on_homepage' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul testimoni wajib diisi.',
            'participant_name.required' => 'Nama peserta wajib diisi.',
            'video_url.required' => 'URL video wajib diisi.',
            'video_url.url' => 'URL video harus valid.',
            'poster_url.url' => 'URL poster harus valid.',
            'status.in' => 'Status hanya boleh draft, active, atau archived.',
            'sort_order.integer' => 'Urutan tampil harus angka bulat.',
        ];
    }
}
