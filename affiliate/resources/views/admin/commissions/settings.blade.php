@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Pengaturan Komisi" subtitle="Atur rate komisi dan masa cooling per tipe & produk." />

<x-card>
    <form method="POST" action="{{ route('admin.commissions.settings.update') }}">
        @csrf @method('PUT')
        <div class="overflow-x-auto -mx-5">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-100">
                    <tr>
                        <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide text-slate-500">Tipe</th>
                        <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide text-slate-500">Produk</th>
                        <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide text-slate-500">Rate (%)</th>
                        <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide text-slate-500">Cooling (hari)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($settings as $setting)
                        <tr>
                            <td class="px-5 py-3 text-slate-700 font-medium">{{ $setting->affiliatorType->name ?? 'Global' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $setting->product_type ?? 'Semua' }}</td>
                            @php $rowLabel = ($setting->affiliatorType->name ?? 'Global').' · '.($setting->product_type ?? 'semua produk'); @endphp
                            <td class="px-5 py-3">
                                <input type="number" step="0.01" name="settings[{{ $setting->id }}][rate]" value="{{ $setting->rate }}"
                                       aria-label="Rate komisi (%) — {{ $rowLabel }}"
                                       class="w-24 h-10 px-3 rounded-lg border border-slate-200 text-sm text-slate-800 shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                            </td>
                            <td class="px-5 py-3">
                                <input type="number" name="settings[{{ $setting->id }}][cooling_days]" value="{{ $setting->cooling_days }}"
                                       aria-label="Cooling (hari) — {{ $rowLabel }}"
                                       class="w-20 h-10 px-3 rounded-lg border border-slate-200 text-sm text-slate-800 shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-5">
            <x-button type="submit" icon="save">Simpan Pengaturan</x-button>
        </div>
    </form>
</x-card>
@endsection
