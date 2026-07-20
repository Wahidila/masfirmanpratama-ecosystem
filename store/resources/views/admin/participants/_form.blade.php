@php
    $inputClass = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

@if ($participant->order)
    <div class="mb-5">
        <x-admin.alert tone="primary">
            Peserta ini berasal dari pesanan
            <a href="{{ route('admin.orders.show', $participant->order) }}" class="font-mono font-semibold underline">{{ $participant->order->order_number }}</a>.
            Status pembayaran mengikuti pesanan dan diperbarui otomatis saat pembayaran diverifikasi.
        </x-admin.alert>
    </div>
@endif

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-admin.form-group label="Kelas" name="course_id" required>
            <select id="course_id" name="course_id" class="{{ $inputClass }}">
                <option value="">— Pilih kelas —</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) old('course_id', $participant->course_id) === (string) $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </x-admin.form-group>
    </div>

    <x-admin.form-group label="Nama peserta" name="name" required>
        <input type="text" id="name" name="name" value="{{ old('name', $participant->name) }}" class="{{ $inputClass }}">
    </x-admin.form-group>

    <x-admin.form-group label="Email" name="email">
        <input type="email" id="email" name="email" value="{{ old('email', $participant->email) }}" class="{{ $inputClass }}">
    </x-admin.form-group>

    <x-admin.form-group label="Nomor WhatsApp" name="phone">
        <input type="text" id="phone" name="phone" value="{{ old('phone', $participant->phone) }}" class="{{ $inputClass }}">
    </x-admin.form-group>

    <x-admin.form-group label="Pekerjaan" name="occupation">
        <input type="text" id="occupation" name="occupation" value="{{ old('occupation', $participant->occupation) }}" class="{{ $inputClass }}">
    </x-admin.form-group>

    <x-admin.form-group label="Status peserta" name="status" required>
        <select id="status" name="status" class="{{ $inputClass }}">
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $participant->status) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-admin.form-group>

    @if ($participant->order_id)
        {{-- Peserta dari pesanan: status bayar mengikuti pembayaran order,
             diperbarui otomatis oleh listener. Sengaja tidak bisa diedit manual. --}}
        <x-admin.form-group label="Status pembayaran"
            hint="Mengikuti pembayaran pada pesanan — otomatis berubah jadi Lunas saat cicilan selesai diverifikasi. Tidak bisa diubah manual.">
            <div class="flex h-11 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-theme-xs font-medium
                    {{ $participant->payment_status === 'lunas'
                        ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500'
                        : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500' }}">
                    {{ $participant->paymentStatusLabel() }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">disinkronkan dari pesanan</span>
            </div>
        </x-admin.form-group>
    @else
        <x-admin.form-group label="Status pembayaran" name="payment_status" required
            hint="Lunas = pembayaran penuh. Cicilan = cicilan masih berjalan.">
            <select id="payment_status" name="payment_status" class="{{ $inputClass }}">
                @foreach ($paymentStatuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('payment_status', $participant->payment_status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </x-admin.form-group>
    @endif

    <x-admin.form-group label="Tanggal bergabung" name="joined_at">
        <input type="date" id="joined_at" name="joined_at"
            value="{{ old('joined_at', $participant->joined_at?->format('Y-m-d')) }}" class="{{ $inputClass }}">
    </x-admin.form-group>

    <div class="md:col-span-2">
        <x-admin.form-group label="Motivasi" name="motivation">
            <textarea id="motivation" name="motivation" rows="3" class="{{ $areaClass }}">{{ old('motivation', $participant->motivation) }}</textarea>
        </x-admin.form-group>
    </div>

    <div class="md:col-span-2">
        <x-admin.form-group label="Catatan admin" name="notes" hint="Tidak ditampilkan ke peserta.">
            <textarea id="notes" name="notes" rows="3" class="{{ $areaClass }}">{{ old('notes', $participant->notes) }}</textarea>
        </x-admin.form-group>
    </div>
</div>
