<?php

namespace App\Http\Requests;

use App\Models\CourseParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'motivation' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(array_keys(CourseParticipant::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(CourseParticipant::PAYMENT_STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'joined_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Kelas wajib dipilih.',
            'course_id.exists' => 'Kelas tidak ditemukan.',
            'name.required' => 'Nama peserta wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'status.required' => 'Status peserta wajib dipilih.',
            'payment_status.required' => 'Status pembayaran wajib dipilih.',
        ];
    }
}
