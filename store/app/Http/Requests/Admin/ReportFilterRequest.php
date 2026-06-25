<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'before_or_equal:today'],
            'to' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Cross-field validation: from harus <= to.
     * Error ditampilkan di field 'from' agar UX jelas (tanggal awal salah).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('from');
            $to = $this->input('to');

            if ($from && $to) {
                try {
                    $fromDate = Carbon::parse($from)->startOfDay();
                    $toDate = Carbon::parse($to)->endOfDay();

                    if ($fromDate->gt($toDate)) {
                        $validator->errors()->add('from', 'Tanggal awal tidak boleh setelah tanggal akhir.');
                    }
                } catch (\Throwable) {
                    // Format error sudah ditangani rule 'date'.
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.date' => 'Tanggal awal harus berupa tanggal yang valid.',
            'from.before_or_equal' => 'Tanggal awal tidak boleh melebihi hari ini.',
            'to.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'to.before_or_equal' => 'Tanggal akhir tidak boleh melebihi hari ini.',
        ];
    }

    /**
     * Parse 'from' input ke Carbon (start of day). Default: awal bulan ini.
     */
    public function dateFrom(): Carbon
    {
        $from = $this->input('from');

        return $from
            ? Carbon::parse($from)->startOfDay()
            : Carbon::now()->startOfMonth();
    }

    /**
     * Parse 'to' input ke Carbon (end of day). Default: akhir hari ini.
     */
    public function dateTo(): Carbon
    {
        $to = $this->input('to');

        return $to
            ? Carbon::parse($to)->endOfDay()
            : Carbon::now()->endOfDay();
    }
}
