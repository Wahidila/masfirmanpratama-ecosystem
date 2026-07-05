@extends('layouts.admin', ['active' => 'orders'])

@section('title', 'Pesanan ' . $order->order_number . ' · Admin')

@php
    $paymentLabel = [
        'pending' => 'Menunggu',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];
    $fulfillmentLabel = [
        'shipped' => 'Terkirim',
        'waiting_awb' => 'Tunggu AWB',
        'pending_payment' => 'Bayar Ongkir',
        'failed' => 'Gagal',
    ];
@endphp

@section('content')
    <x-admin.page-header
        :title="'Pesanan ' . $order->order_number"
        :subtitle="'Dibuat ' . $order->created_at?->format('d M Y · H:i') . ' WIB'">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.orders.index') }}" variant="outline" size="sm">
                ← Kembali
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6">
            <x-admin.alert tone="danger" dismissible>{{ is_array(session('error')) ? implode(' ', \Illuminate\Support\Arr::flatten(session('error'))) : session('error') }}</x-admin.alert>
        </div>
    @endif

    @if (session('info'))
        <div class="mb-6">
            <x-admin.alert tone="primary" dismissible>{{ session('info') }}</x-admin.alert>
        </div>
    @endif

    {{-- Status & total summary strip --}}
    <section class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</div>
            <div class="mt-2">
                <x-admin.status-badge :status="$order->status" />
            </div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Pesanan</div>
            <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">
                Rp {{ number_format((float) $order->total, 0, ',', '.') }}
            </div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Sudah Lunas</div>
            <div class="mt-2 text-2xl font-semibold text-success-600 dark:text-success-500">
                Rp {{ number_format($totalPaid, 0, ',', '.') }}
            </div>
            @if ($totalPending > 0)
                <div class="mt-1 text-xs text-warning-600 dark:text-warning-500">
                    + Rp {{ number_format($totalPending, 0, ',', '.') }} menunggu verifikasi
                </div>
            @endif
        </x-admin.card>
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Sisa</div>
            <div class="mt-2 text-2xl font-semibold {{ $remaining > 0 ? 'text-warning-700 dark:text-warning-500' : 'text-gray-400 dark:text-gray-500' }}">
                Rp {{ number_format($remaining, 0, ',', '.') }}
            </div>
        </x-admin.card>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left col: items + payments --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Items --}}
            <x-admin.card :padded="false">
                <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Item Pesanan</h2>
                </div>
                @if ($order->items->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada item di pesanan ini.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-2 text-left font-medium">Produk</th>
                                <th class="px-5 py-2 text-right font-medium">Qty</th>
                                <th class="px-5 py-2 text-right font-medium">Harga</th>
                                <th class="px-5 py-2 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($order->items as $item)
                                @php
                                    $itemTitle = $item->product?->title ?? $item->course?->title ?? ($item->course_id ? '(kelas dihapus)' : '(produk dihapus)');
                                    $itemSlug = $item->product?->slug ?? $item->course?->slug ?? null;
                                    $itemType = $item->course_id ? 'Kelas' : 'Produk';
                                @endphp
                                <tr>
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-gray-800 dark:text-white/90">
                                            {{ $itemTitle }}
                                        </div>
                                        @if ($itemSlug)
                                            <div class="text-xs text-gray-500 font-mono dark:text-gray-400">{{ $itemSlug }}</div>
                                        @endif
                                        @if ($item->course_id)
                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400 mt-1">
                                                Kelas
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ $item->qty }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">
                                        Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-800 dark:text-white/90">
                                        Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 text-sm dark:bg-white/[0.03]">
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right text-gray-500 dark:text-gray-400">Total</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-800 dark:text-white/90">
                                    Rp {{ number_format((float) $order->total, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </x-admin.card>

            {{-- Payments timeline --}}
            <x-admin.card :padded="false">
                <div class="border-b border-gray-200 px-5 py-3 flex items-center justify-between dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Pembayaran</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order->payments->count() }} entri</span>
                </div>
                @if ($order->payments->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada bukti bayar yang di-upload customer.
                    </div>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($order->payments as $payment)
                            @php
                                $pBadgeClass = match ($payment->status) {
                                    'pending' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                                    'verified' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                    'rejected' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                                    default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
                                };
                            @endphp
                            <li class="px-5 py-4" x-data="{ showApprove: false, showReject: false }">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base font-semibold text-gray-800 dark:text-white/90">
                                                Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                            </span>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pBadgeClass }}">
                                                {{ $paymentLabel[$payment->status] ?? $payment->status }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Metode: <span class="text-gray-700 font-medium dark:text-gray-300">{{ ucfirst($payment->method) }}</span>
                                            @if ($payment->paid_at)
                                                · Dibayar {{ $payment->paid_at->format('d M Y H:i') }}
                                            @endif
                                        </div>
                                        @if ($payment->verified_at)
                                            <div class="mt-1 text-xs {{ $payment->status === 'verified' ? 'text-success-600 dark:text-success-500' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $payment->status === 'verified' ? 'Diverifikasi' : 'Diproses' }}
                                                {{ $payment->verified_at->format('d M Y H:i') }}
                                                @if ($payment->verifier)
                                                    oleh {{ $payment->verifier->name }}
                                                @endif
                                            </div>
                                        @endif
                                        @if ($payment->status === 'rejected' && $payment->rejection_reason)
                                            <div class="mt-2 rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-700 dark:bg-white/[0.03] dark:border-gray-800 dark:text-gray-300">
                                                <div class="font-medium text-gray-500 uppercase tracking-wide mb-0.5 dark:text-gray-400">Alasan tolak</div>
                                                <div>{{ $payment->rejection_reason }}</div>
                                            </div>
                                        @endif
                                    </div>
                                    @if ($payment->proof_path)
                                        @php $proofUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path); @endphp
                                        <div class="shrink-0 text-center">
                                            <div class="text-xs text-gray-500 mb-1 dark:text-gray-400">Bukti Bayar</div>
                                            <a href="{{ $proofUrl }}" target="_blank" rel="noopener noreferrer"
                                               class="group block"
                                               title="Klik untuk lihat foto bukti bayar ukuran penuh">
                                                <img src="{{ $proofUrl }}" alt="Bukti bayar {{ $order->order_number }}"
                                                     loading="lazy"
                                                     class="h-24 w-24 rounded-lg object-cover ring-1 ring-gray-200 transition group-hover:ring-2 group-hover:ring-brand-400 dark:ring-gray-700">
                                                <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-brand-500 group-hover:text-brand-600 dark:text-brand-400">
                                                    <i data-lucide="external-link" class="h-3 w-3"></i>
                                                    Lihat / unduh
                                                </span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="shrink-0 text-xs italic text-gray-400 dark:text-gray-500">
                                            Belum upload bukti
                                        </div>
                                    @endif
                                </div>

                                @if ($payment->status === 'pending')
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="button"
                                                @click="showApprove = !showApprove; showReject = false"
                                                class="inline-flex items-center rounded-lg bg-success-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-success-600 transition">
                                            ✓ Approve
                                        </button>
                                        <button type="button"
                                                @click="showReject = !showReject; showApprove = false"
                                                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                            ✗ Reject
                                        </button>
                                    </div>

                                    {{-- Approve form --}}
                                    <form x-show="showApprove" x-cloak
                                          method="POST"
                                          action="{{ route('admin.orders.payments.approve', [$order, $payment]) }}"
                                          class="mt-3 rounded-xl border border-success-200 bg-success-50 p-4 space-y-3 dark:border-success-500/30 dark:bg-success-500/15">
                                        @csrf
                                        <div>
                                            <label for="approve-amount-{{ $payment->id }}" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">
                                                Nominal terverifikasi
                                            </label>
                                            <input id="approve-amount-{{ $payment->id }}"
                                                   type="number"
                                                   name="amount"
                                                   step="0.01"
                                                   min="0"
                                                   value="{{ $payment->amount }}"
                                                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Edit kalau jumlah aktual transfer beda dari yang diinput customer.
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white hover:bg-success-600 transition">
                                                Konfirmasi Approve
                                            </button>
                                            <button type="button" @click="showApprove = false"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                Batal
                                            </button>
                                        </div>
                                    </form>

                                    {{-- Reject form --}}
                                    <form x-show="showReject" x-cloak
                                          method="POST"
                                          action="{{ route('admin.orders.payments.reject', [$order, $payment]) }}"
                                          class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3 dark:border-gray-800 dark:bg-white/[0.03]">
                                        @csrf
                                        <div>
                                            <label for="reject-reason-{{ $payment->id }}" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">
                                                Alasan tolak <span class="text-error-500">*</span>
                                            </label>
                                            <textarea id="reject-reason-{{ $payment->id }}"
                                                      name="reason"
                                                      rows="3"
                                                      required
                                                      minlength="3"
                                                      maxlength="500"
                                                      class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                      placeholder="Mis. nominal tidak sesuai, bukti tidak jelas, transfer ke rekening yang salah..."></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white hover:bg-error-600 transition">
                                                Konfirmasi Reject
                                            </button>
                                            <button type="button" @click="showReject = false"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                Batal
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>
        </div>

        {{-- Right col: customer + shipping --}}
        <div class="space-y-6">
            <x-admin.card>
                <h2 class="text-sm font-semibold text-gray-700 mb-3 dark:text-gray-300">Customer</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama</dt>
                        <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">{{ $order->customer_name }}</dd>
                    </div>
                    @if ($order->phone)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Telepon / WA</dt>
                            <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $order->phone }}</dd>
                        </div>
                    @endif
                    @if ($order->email)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-0.5 text-gray-700 break-all dark:text-gray-300">{{ $order->email }}</dd>
                        </div>
                    @endif
                </dl>
            </x-admin.card>

            <x-admin.card>
                @php
                    $isCourseOrder = str_starts_with($order->order_number, 'COURSE-');
                    $registrationMeta = null;
                    if ($isCourseOrder && $order->ref_code) {
                        $decoded = json_decode($order->ref_code, true);
                        if (is_array($decoded)) {
                            $registrationMeta = $decoded;
                        }
                    }
                @endphp

                @if ($isCourseOrder)
                    <h2 class="text-sm font-semibold text-gray-700 mb-3 dark:text-gray-300">Data Pendaftaran</h2>
                    <dl class="space-y-3 text-sm">
                        @if ($registrationMeta && !empty($registrationMeta['occupation']))
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pekerjaan</dt>
                                <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $registrationMeta['occupation'] }}</dd>
                            </div>
                        @endif
                        @if ($registrationMeta && !empty($registrationMeta['motivation']))
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Motivasi</dt>
                                <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $registrationMeta['motivation'] }}</dd>
                            </div>
                        @endif
                        @if (!$registrationMeta || (empty($registrationMeta['occupation']) && empty($registrationMeta['motivation'])))
                            <p class="text-sm italic text-gray-500 dark:text-gray-400">Tidak ada data tambahan.</p>
                        @endif
                    </dl>
                @else
                    <h2 class="text-sm font-semibold text-gray-700 mb-3 dark:text-gray-300">Alamat Pengiriman</h2>
                    @if ($order->address)
                        <p class="text-sm text-gray-700 whitespace-pre-line dark:text-gray-300">{{ $order->address }}</p>
                    @else
                        <p class="text-sm italic text-gray-500 dark:text-gray-400">Alamat belum diisi.</p>
                    @endif

                    {{-- Alamat terstruktur (kolom diskrit — dipakai kurir & tarif) --}}
                    @if ($order->shipping_city || $order->shipping_province)
                        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                            @if ($order->shipping_province)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Provinsi</dt>
                                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $order->shipping_province }}</dd>
                                </div>
                            @endif
                            @if ($order->shipping_city)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kota/Kab.</dt>
                                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $order->shipping_city }}</dd>
                                </div>
                            @endif
                            @if ($order->shipping_district)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kecamatan</dt>
                                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $order->shipping_district }}</dd>
                                </div>
                            @endif
                            @if ($order->shipping_village)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Desa/Kel.</dt>
                                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $order->shipping_village }}</dd>
                                </div>
                            @endif
                            @if ($order->shipping_zipcode)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kode Pos</dt>
                                    <dd class="mt-0.5 font-mono text-gray-700 dark:text-gray-300">{{ $order->shipping_zipcode }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif

                    {{-- Metode pengiriman + ongkir yang dipilih customer --}}
                    @if ($order->shipping_courier || $order->shipping_cost)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <dt class="text-xs uppercase tracking-wide text-gray-500 mb-2 dark:text-gray-400">Pengiriman Dipilih</dt>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Kurir / Layanan</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">
                                        {{ strtoupper($order->shipping_courier ?? '—') }}
                                        @if ($order->shipping_service)
                                            <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $order->shipping_service }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Ongkir</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">Rp {{ number_format((float) $order->shipping_cost, 0, ',', '.') }}</dd>
                                </div>
                                @if ($order->shipping_etd)
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Estimasi</dt>
                                        <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $order->shipping_etd }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    {{-- Link ke halaman tracking yang dilihat customer --}}
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('track.show', ['order_number' => $order->order_number]) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                            <i data-lucide="map-pin" class="h-3 w-3"></i>
                            Lihat halaman tracking customer
                        </a>
                    </div>

                    @if ($order->ref_code)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kode Referral</dt>
                            <dd class="mt-0.5 font-mono text-gray-700 dark:text-gray-300">{{ $order->ref_code }}</dd>
                        </div>
                    @endif
                @endif
            </x-admin.card>

            {{-- Cicilan: jadwal angsuran + tombol Reminder Cicilan via WhatsApp --}}
            @if (! empty($installment))
            <x-admin.card>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Cicilan</h2>
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                        {{ $installment['paid_count'] }}/{{ $installment['total_count'] }} lunas
                    </span>
                </div>

                <ul class="space-y-2 text-sm">
                    @foreach ($installment['schedule'] as $step)
                        @php
                            $stepBadge = match ($step['status']) {
                                'verified' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                'rejected' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                                default => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                            };
                            $stepStatus = ['verified' => 'Lunas', 'rejected' => 'Ditolak', 'pending' => 'Belum'][$step['status']] ?? $step['status'];
                        @endphp
                        <li class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 {{ $step['is_next'] ? 'border-brand-300 bg-brand-50/60 dark:border-brand-500/40 dark:bg-brand-500/10' : 'border-gray-200 dark:border-gray-800' }}">
                            <div class="min-w-0">
                                <div class="font-medium text-gray-800 dark:text-white/90">
                                    {{ $step['label'] }}
                                    @if ($step['is_next'])
                                        <span class="ml-1 text-[10px] font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">Berikutnya</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Rp {{ number_format($step['amount'], 0, ',', '.') }}
                                    @if ($step['due_date'])
                                        · jatuh tempo {{ $step['due_date']->translatedFormat('d M Y') }}
                                    @endif
                                    @if ($step['overdue_days'] > 0)
                                        <span class="font-medium text-error-600 dark:text-error-500">· lewat {{ $step['overdue_days'] }} hari</span>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stepBadge }}">{{ $stepStatus }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3 text-sm dark:border-gray-800">
                    <span class="text-gray-500 dark:text-gray-400">Sisa belum dibayar</span>
                    <span class="font-semibold {{ $installment['remaining'] > 0 ? 'text-warning-700 dark:text-warning-500' : 'text-gray-400 dark:text-gray-500' }}">
                        Rp {{ number_format($installment['remaining'], 0, ',', '.') }}
                    </span>
                </div>

                @if ($installment['can_remind'])
                    <form method="POST" action="{{ route('admin.orders.remind-installment', $order) }}" class="mt-4"
                          onsubmit="return confirm('Kirim reminder cicilan via WhatsApp ke customer?');">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10">
                            <i data-lucide="bell-ring" class="h-4 w-4"></i>
                            Kirim Reminder Cicilan
                        </button>
                        <p class="mt-1 text-[11px] leading-snug text-gray-400">
                            Kirim WhatsApp ke {{ $order->phone }} berisi status cicilan, tagihan berikutnya, sisa, & link upload bukti bayar.
                        </p>
                    </form>
                @elseif (in_array($order->status, ['cancelled', 'refunded'], true))
                    <p class="mt-4 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                        Order {{ $order->status === 'refunded' ? 'sudah di-refund' : 'dibatalkan' }} — reminder cicilan dinonaktifkan.
                    </p>
                @else
                    <p class="mt-4 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-xs text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-500">
                        🎉 Semua cicilan sudah lunas — tidak perlu reminder.
                    </p>
                @endif
            </x-admin.card>
            @endif

            @if (!$isCourseOrder)
            <x-admin.card>
                <h2 class="text-sm font-semibold text-gray-700 mb-3 dark:text-gray-300">Aksi Pengiriman</h2>

                {{-- Fulfillment info (tampil di semua status kalau fulfillment_status terisi) --}}
                @if ($order->fulfillment_status)
                    <div class="mb-4 space-y-2 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status Fulfillment:</span>
                            @php
                                $fBadgeClass = match ($order->fulfillment_status) {
                                    'shipped' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                    'waiting_awb', 'pending_payment' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                                    'failed' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                                    default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $fBadgeClass }}">
                                {{ $fulfillmentLabel[$order->fulfillment_status] ?? $order->fulfillment_status }}
                            </span>
                        </div>
                        @if ($order->tracking_status)
                            <div>
                                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Tracking:</span>
                                <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $order->tracking_status }}</span>
                            </div>
                        @endif
                        @if ($order->shipping_resi)
                            <div>
                                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Resi:</span>
                                <span class="ml-1 font-mono text-gray-800 break-all dark:text-white/90">{{ $order->shipping_resi }}</span>
                            </div>
                        @endif
                        @if ($order->label_url)
                            <div>
                                <a href="{{ $order->label_url }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 underline dark:text-brand-400 dark:hover:text-brand-500">
                                    <i data-lucide="external-link" class="h-3 w-3"></i>
                                    Label Pengiriman
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($order->status === 'shipped' || $order->status === 'completed')
                    {{-- Order sudah dikirim — tampilkan info resi read-only --}}
                    <div class="space-y-3 text-sm">
                        @if ($order->status === 'completed')
                            <div class="rounded-xl bg-success-50 border border-success-200 p-3 dark:bg-success-500/15 dark:border-success-500/30">
                                <div class="flex items-center gap-2 text-success-700 font-semibold dark:text-success-500">
                                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                                    <span>Order Selesai</span>
                                </div>
                                <p class="mt-1 text-xs text-success-600 dark:text-success-400">
                                    Paket sudah diterima pembeli — siklus order ditutup.
                                    @if (data_get($order->order_meta, 'completed_manually'))
                                        (ditandai manual oleh admin)
                                    @endif
                                </p>
                            </div>
                        @else
                            <div class="rounded-xl bg-success-50 border border-success-200 p-3 dark:bg-success-500/15 dark:border-success-500/30">
                                <div class="flex items-center gap-2 text-success-700 font-semibold dark:text-success-500">
                                    <i data-lucide="truck" class="h-4 w-4"></i>
                                    <span>Sudah Dikirim</span>
                                </div>
                                @if ($order->shipped_at)
                                    <p class="mt-1 text-xs text-success-600 dark:text-success-400">
                                        {{ \Illuminate\Support\Carbon::parse($order->shipped_at)->translatedFormat('d M Y, H:i') }}
                                    </p>
                                @endif
                            </div>
                        @endif
                        <dl class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kurir</dt>
                                <dd class="mt-0.5 font-medium text-gray-800 dark:text-white/90">{{ ($couriers[$order->shipping_courier] ?? null) ?: ($order->shipping_courier ?? '—') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Nomor Resi</dt>
                                <dd class="mt-0.5 font-mono text-gray-800 break-all dark:text-white/90">{{ $order->shipping_resi ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if ($order->status === 'shipped')
                            {{-- Tutup siklus manual: 'shipped' → 'completed'. Perlu untuk alur
                                 resi-manual yang tak dapat callback AWB 'delivered'. --}}
                            <form method="POST" action="{{ route('admin.orders.complete', $order) }}"
                                  onsubmit="return confirm('Konfirmasi: tandai order ini SELESAI? Notifikasi WhatsApp terima kasih akan dikirim ke pembeli.');">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-success-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-success-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10"
                                >
                                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                                    Tandai Selesai
                                </button>
                                <p class="mt-1 text-[11px] leading-snug text-gray-400">
                                    Gunakan setelah paket dipastikan diterima pembeli. Atau cek resi di bawah — kalau kurir sudah "delivered", order otomatis selesai.
                                </p>
                            </form>
                        @endif

                        @if ($order->shipping_resi && $order->shipping_courier)
                            {{-- Opsi B: cek non-blocking apakah resi terdeteksi di sistem kurir --}}
                            <div class="pt-1">
                                <button
                                    type="button"
                                    id="btn-check-resi"
                                    data-url="{{ route('admin.orders.check-resi', $order) }}"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/[0.03]"
                                >
                                    <i data-lucide="radar" class="h-4 w-4"></i>
                                    Cek status resi di kurir
                                </button>
                                <div id="resi-check-result" class="mt-2 hidden rounded-lg border px-3 py-2 text-xs"></div>
                                <p class="mt-1 text-[11px] leading-snug text-gray-400">
                                    Non-blocking: resi baru bisa "belum terdeteksi" karena belum discan kurir.
                                </p>
                            </div>

                            <script>
                                (function () {
                                    const btn = document.getElementById('btn-check-resi');
                                    if (!btn) return;
                                    btn.addEventListener('click', async function () {
                                        const box = document.getElementById('resi-check-result');
                                        const original = btn.innerHTML;
                                        btn.disabled = true;
                                        btn.innerHTML = 'Mengecek…';
                                        box.className = 'mt-2 hidden rounded-lg border px-3 py-2 text-xs';
                                        try {
                                            const res = await fetch(btn.dataset.url, {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json',
                                                },
                                            });
                                            const data = await res.json();
                                            box.classList.remove('hidden');
                                            if (data.completed) {
                                                // Kurir sudah delivered → order otomatis selesai.
                                                // Reload supaya status & tombol di UI ikut ter-update.
                                                box.classList.add('border-success-200', 'bg-success-50', 'text-success-700');
                                                box.innerHTML = '🎉 ' + data.message + ' Memuat ulang…';
                                                setTimeout(function () { window.location.reload(); }, 1200);
                                                return;
                                            }
                                            if (data.detected) {
                                                box.classList.add('border-success-200', 'bg-success-50', 'text-success-700');
                                                box.innerHTML = '✅ ' + data.message + (data.status ? ('<br><span class="font-medium">Status terakhir:</span> ' + data.status) : '');
                                            } else {
                                                box.classList.add('border-warning-200', 'bg-warning-50', 'text-warning-700');
                                                box.innerHTML = '⚠️ ' + (data.message || 'Resi belum terdeteksi.');
                                            }
                                        } catch (e) {
                                            box.classList.remove('hidden');
                                            box.classList.add('border-error-200', 'bg-error-50', 'text-error-700');
                                            box.textContent = 'Gagal mengecek resi. Coba lagi.';
                                        } finally {
                                            btn.disabled = false;
                                            btn.innerHTML = original;
                                            if (window.lucide) lucide.createIcons();
                                        }
                                    });
                                })();
                            </script>
                        @endif
                    </div>
                @elseif ($canShip)
                    {{-- Order siap kirim (status=paid) — input kurir & nomor resi manual.
                         Resi diinput manual oleh admin (fitur generate resi otomatis
                         dinonaktifkan; butuh handshake rates/signed_key + saldo wallet). --}}
                    <p class="text-xs text-gray-500 mb-3 dark:text-gray-400">
                        Isi kurir &amp; nomor resi untuk menandai order ini sebagai
                        <span class="font-medium text-gray-700 dark:text-gray-300">Dikirim</span>.
                    </p>
                    <form
                        method="POST"
                        action="{{ route('admin.orders.ship', $order) }}"
                        class="space-y-3"
                        data-testid="form-input-resi"
                    >
                        @csrf
                        <div>
                            <label for="shipping_courier" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">
                                Kurir <span class="text-error-500">*</span>
                            </label>
                            <select
                                name="shipping_courier"
                                id="shipping_courier"
                                required
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 @error('shipping_courier') border-error-400 @enderror"
                            >
                                @php $selectedCourier = (string) old('shipping_courier', $order->shipping_courier); @endphp
                                <option value="">— Pilih kurir —</option>
                                @foreach ($couriers as $courierId => $courierLabel)
                                    <option value="{{ $courierId }}" @selected($selectedCourier === (string) $courierId)>{{ $courierLabel }}</option>
                                @endforeach
                            </select>
                            @error('shipping_courier')
                                <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="shipping_resi" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">
                                Nomor Resi / AWB <span class="text-error-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="shipping_resi"
                                id="shipping_resi"
                                value="{{ old('shipping_resi') }}"
                                required
                                minlength="4"
                                maxlength="64"
                                placeholder="cth. JNE1234567890"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm font-mono text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 @error('shipping_resi') border-error-400 @enderror"
                            >
                            @error('shipping_resi')
                                <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10"
                            onclick="return confirm('Konfirmasi: tandai order ini sebagai dikirim dengan resi yang diinput?');"
                        >
                            <i data-lucide="truck" class="h-4 w-4"></i>
                            Tandai Dikirim
                        </button>
                    </form>
                @else
                    {{-- Status belum siap kirim — info kondisi --}}
                    <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600 dark:bg-white/[0.03] dark:border-gray-800 dark:text-gray-400">
                        <div class="flex items-center gap-2 mb-1">
                            <i data-lucide="info" class="h-4 w-4 text-gray-500 dark:text-gray-400"></i>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Belum siap kirim</span>
                        </div>
                        <p>
                            Status sekarang: <span class="font-mono font-medium">{{ $order->status }}</span>.
                            Form input resi tersedia setelah pembayaran lunas terverifikasi (status =
                            <span class="font-mono">paid</span>).
                        </p>
                    </div>
                @endif
            </x-admin.card>
            @endif

            {{-- Refund action --}}
            @if ($canRefund)
            <x-admin.card>
                <h2 class="text-sm font-semibold text-gray-700 mb-3 dark:text-gray-300">Aksi Refund</h2>
                <p class="text-xs text-gray-500 mb-3 dark:text-gray-400">
                    Refund akan membatalkan order dan mengirim notifikasi ke sistem Affiliate
                    untuk pembatalan komisi. Tindakan ini tidak dapat dibatalkan.
                </p>
                <form
                    method="POST"
                    action="{{ route('admin.orders.refund', $order) }}"
                    data-testid="form-refund"
                >
                    @csrf
                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-rose-600 focus:outline-hidden focus:ring-3 focus:ring-rose-500/10"
                        onclick="return confirm('Konfirmasi: refund order ini? Tindakan tidak dapat dibatalkan.');"
                    >
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        Refund Order
                    </button>
                </form>
            </x-admin.card>
            @endif

            @if ($order->status === 'refunded')
            <x-admin.card>
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-3 dark:bg-rose-500/15 dark:border-rose-500/30">
                    <div class="flex items-center gap-2 text-rose-700 font-semibold dark:text-rose-400">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        <span>Order Di-refund</span>
                    </div>
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                        Order ini telah di-refund. Komisi affiliate terkait telah dibatalkan.
                    </p>
                </div>
            </x-admin.card>
            @endif

            {{-- Riwayat notifikasi WhatsApp --}}
            <x-admin.card :padded="false">
                <div class="border-b border-gray-200 px-5 py-3 flex items-center justify-between dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notifikasi WhatsApp</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order->waNotifications->count() }} pesan</span>
                </div>
                @php
                    $waLabel = [
                        'customer_order_created' => 'Pesanan dibuat',
                        'customer_payment_received' => 'Bukti diterima',
                        'customer_payment_verified' => 'Pembayaran diverifikasi',
                        'customer_payment_rejected' => 'Pembayaran ditolak',
                        'customer_order_shipped' => 'Pesanan dikirim',
                        'customer_order_completed' => 'Pesanan selesai',
                        'admin_payment_review_alert' => 'Alert admin (bukti baru)',
                        'course_registration_success' => 'Pendaftaran kelas',
                        'customer_installment_reminder' => 'Pengingat cicilan',
                    ];
                    $waTone = [
                        'sent' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                        'queued' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                        'failed' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                    ];
                    $waStatusLabel = ['sent' => 'Terkirim', 'queued' => 'Antre', 'failed' => 'Gagal'];
                @endphp
                @if ($order->waNotifications->isEmpty())
                    <div class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada notifikasi WhatsApp terkirim untuk order ini.
                    </div>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($order->waNotifications as $notif)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $waLabel[$notif->template] ?? $notif->template }}
                                    </span>
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $waTone[$notif->status] ?? 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400' }}">
                                        {{ $waStatusLabel[$notif->status] ?? $notif->status }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Ke {{ $notif->recipient }}
                                    @if ($notif->sent_at)
                                        · {{ $notif->sent_at->format('d M Y H:i') }}
                                    @endif
                                </div>
                                @if ($notif->status === 'failed' && $notif->error)
                                    <div class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $notif->error }}</div>
                                @endif

                                @if ($notif->template === 'customer_installment_reminder')
                                    {{-- Reminder cicilan memuat status + jadwal + upload URL bertenggat.
                                         Jangan replay payload lama (bisa basi / link kedaluwarsa) — arahkan
                                         admin ke tombol "Kirim Reminder Cicilan" yang selalu hitung ulang. --}}
                                    <p class="mt-2 text-[11px] leading-snug text-gray-400">
                                        Untuk kirim ulang, pakai tombol <span class="font-medium">Kirim Reminder Cicilan</span> di kartu Cicilan — datanya selalu diperbarui.
                                    </p>
                                @else
                                    {{-- Kirim ulang manual (mitigasi gagal kirim). Tersedia untuk semua
                                         status: gagal/antre jelas, "terkirim" pun kalau customer bilang
                                         tak menerima. --}}
                                    <div class="mt-2">
                                        <form method="POST" action="{{ route('admin.wa-notifications.resend', $notif) }}"
                                              onsubmit="return confirm('Kirim ulang notifikasi WhatsApp ini ke customer?');">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/[0.03]"
                                            >
                                                <i data-lucide="send" class="h-3.5 w-3.5"></i>
                                                Kirim ulang
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
