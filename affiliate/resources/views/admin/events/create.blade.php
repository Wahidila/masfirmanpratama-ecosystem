@extends('admin.layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.events.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali ke daftar event</a>
    <h1 class="text-xl font-bold text-slate-800 mt-2">Buat Event Baru</h1>
</div>

<div class="bg-white rounded-2xl border border-slate-100 p-6">
    <form method="POST" action="{{ route('admin.events.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Judul Event <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                @error('title')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipe <span class="text-rose-500">*</span></label>
                <select name="type" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                    <option value="challenge" {{ old('type') === 'challenge' ? 'selected' : '' }}>Challenge</option>
                    <option value="contest" {{ old('type') === 'contest' ? 'selected' : '' }}>Contest</option>
                    <option value="bonus" {{ old('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                </select>
                @error('type')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('description') }}</textarea>
            @error('description')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                @error('start_date')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                @error('end_date')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-rose-500">*</span></label>
                <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="ended" {{ old('status') === 'ended' ? 'selected' : '' }}>Selesai</option>
                </select>
                @error('status')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-1">Rewards (JSON)</label>
            <textarea name="rewards_json" rows="5" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder='[{"rank":1,"reward_type":"cash","reward_value":500000,"description":"Juara 1"}]'>{{ old('rewards_json') }}</textarea>
            <p class="text-xs text-slate-500 mt-1">Format: array of object. Key wajib: rank (angka ≥1), reward_type (cash/voucher/badge/bonus_commission), reward_value (angka ≥0). Key opsional: description.</p>
            @error('rewards_json')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white text-sm rounded-xl hover:bg-primary-700">Simpan Event</button>
        </div>
    </form>
</div>
@endsection
