@php $event = $event ?? null; @endphp

<div class="grid md:grid-cols-2 gap-4">
    <x-form.group label="Judul Event" name="title" required>
        <x-form.input name="title" value="{{ old('title', $event?->title) }}" required placeholder="Nama event" />
    </x-form.group>
    <x-form.group label="Tipe" name="type" required>
        <x-form.select name="type" required>
            @foreach (['challenge' => 'Challenge', 'contest' => 'Contest', 'bonus' => 'Bonus'] as $val => $lbl)
                <option value="{{ $val }}" @selected(old('type', $event?->type) === $val)>{{ $lbl }}</option>
            @endforeach
        </x-form.select>
    </x-form.group>
</div>

<x-form.group label="Deskripsi" name="description">
    <x-form.textarea name="description" rows="3" class="resize-none">{{ old('description', $event?->description) }}</x-form.textarea>
</x-form.group>

<div class="grid md:grid-cols-3 gap-4">
    <x-form.group label="Tanggal Mulai" name="start_date" required>
        <x-form.input type="date" name="start_date" value="{{ old('start_date', $event?->start_date?->format('Y-m-d')) }}" required />
    </x-form.group>
    <x-form.group label="Tanggal Selesai" name="end_date" required>
        <x-form.input type="date" name="end_date" value="{{ old('end_date', $event?->end_date?->format('Y-m-d')) }}" required />
    </x-form.group>
    <x-form.group label="Status" name="status" required>
        <x-form.select name="status" required>
            @foreach (['draft' => 'Draft', 'active' => 'Aktif', 'ended' => 'Selesai'] as $val => $lbl)
                <option value="{{ $val }}" @selected(old('status', $event?->status ?? 'draft') === $val)>{{ $lbl }}</option>
            @endforeach
        </x-form.select>
    </x-form.group>
</div>

<x-form.group label="Rewards (JSON)" name="rewards_json"
    hint="Array of object. Key wajib: rank (≥1), reward_type (cash/voucher/badge/bonus_commission), reward_value (≥0). Opsional: description.">
    <x-form.textarea name="rewards_json" rows="5" class="font-mono text-xs"
        placeholder='[{"rank":1,"reward_type":"cash","reward_value":500000,"description":"Juara 1"}]'>{{ old('rewards_json', $event && $event->rewards ? json_encode($event->rewards, JSON_PRETTY_PRINT) : '') }}</x-form.textarea>
</x-form.group>
