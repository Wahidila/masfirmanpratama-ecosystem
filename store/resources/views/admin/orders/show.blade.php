@extends('layouts.admin', ['active' => 'orders'])

@section('title', 'Pesanan ' . $order->order_number . ' · Admin')

@php
    $statusToneMap = [
        'pending' => 'amber',
        'partial_paid' => 'amber',
        'paid' => 'primary',
        'shipped' => 'primary',
        'completed' => 'secondary',
        'cancelled' => 'slate',
        'refunded' => 'slate',
    ];
    $statusLabel = [
        'pending' => 'Pending',
        'partial_paid' => 'Cicilan',
        'paid' => 'Lunas',
        'shipped' => 'Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
        'refunded' => 'Refund',
    ];
    $paymentToneMap = [
        'pending' => 'amber',
        'verified' => 'secondary',
        'rejected' => 'slate',
    ];
    $paymentLabel = [
        'pending' => 'Menunggu',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];
    $tone = $statusToneMap[$order->status] ?? 'slate';
    $toneClass = match ($tone) {
        'amber' => 'bg-accent-50 text-accent-800 ring-accent-200',
        'primary' => 'bg-primary-50 text-primary-800 ring-primary-200',
        'secondary' => 'bg-secondary-50 text-secondary-800 ring-secondary-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

@section('content')
    <x-admin.page-header
        :title="'Pesanan ' . $order->order_number"
        :subtitle="'Dibuat ' . $order->created_at?->format('d M Y · H:i') . ' WIB'">
        <x-slot:actions>
            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50 transition">
                ← Kembali
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Status & total summary strip --}}
    <section class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-slate-500">Status</div>
            <div class="mt-2">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset {{ $toneClass }}">
                    {{ $statusLabel[$order->status] ?? $order->status }}
                </span>
            </div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-slate-500">Total Pesanan</div>
            <div class="mt-2 text-2xl font-semibold text-slate-900">
                Rp {{ number_format((float) $order->total, 0, ',', '.') }}
            </div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-slate-500">Sudah Lunas</div>
            <div class="mt-2 text-2xl font-semibold text-secondary-700">
                Rp {{ number_format($totalPaid, 0, ',', '.') }}
            </div>
            @if ($totalPending > 0)
                <div class="mt-1 text-xs text-accent-600">
                    + Rp {{ number_format($totalPending, 0, ',', '.') }} menunggu verifikasi
                </div>
            @endif
        </x-admin.card>
        <x-admin.card>
            <div class="text-xs uppercase tracking-wide text-slate-500">Sisa</div>
            <div class="mt-2 text-2xl font-semibold {{ $remaining > 0 ? 'text-accent-700' : 'text-slate-400' }}">
                Rp {{ number_format($remaining, 0, ',', '.') }}
            </div>
        </x-admin.card>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left col: items + payments --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Items --}}
            <x-admin.card :padded="false">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Item Pesanan</h2>
                </div>
                @if ($order->items->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                        Belum ada item di pesanan ini.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/60 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-2 text-left font-medium">Produk</th>
                                <th class="px-5 py-2 text-right font-medium">Qty</th>
                                <th class="px-5 py-2 text-right font-medium">Harga</th>
                                <th class="px-5 py-2 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-slate-900">
                                            {{ $item->product?->title ?? '(produk dihapus)' }}
                                        </div>
                                        @if ($item->product?->slug)
                                            <div class="text-xs text-slate-500 font-mono">{{ $item->product->slug }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">{{ $item->qty }}</td>
                                    <td class="px-5 py-3 text-right">
                                        Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium">
                                        Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50/60 text-sm">
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right text-slate-500">Total</td>
                                <td class="px-5 py-3 text-right font-semibold text-slate-900">
                                    Rp {{ number_format((float) $order->total, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </x-admin.card>

            {{-- Payments timeline --}}
            <x-admin.card :padded="false">
                <div class="border-b border-slate-100 px-5 py-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Pembayaran</h2>
                    <span class="text-xs text-slate-500">{{ $order->payments->count() }} entri</span>
                </div>
                @if ($order->payments->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                        Belum ada bukti bayar yang di-upload customer.
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($order->payments as $payment)
                            @php
                                $pTone = $paymentToneMap[$payment->status] ?? 'slate';
                                $pToneClass = match ($pTone) {
                                    'amber' => 'bg-accent-50 text-accent-800 ring-accent-200',
                                    'secondary' => 'bg-secondary-50 text-secondary-800 ring-secondary-200',
                                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                };
                            @endphp
                            <li class="px-5 py-4 flex items-start gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base font-semibold text-slate-900">
                                            Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $pToneClass }}">
                                            {{ $paymentLabel[$payment->status] ?? $payment->status }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Metode: <span class="text-slate-700 font-medium">{{ ucfirst($payment->method) }}</span>
                                        @if ($payment->paid_at)
                                            · Dibayar {{ $payment->paid_at->format('d M Y H:i') }}
                                        @endif
                                    </div>
                                    @if ($payment->verified_at)
                                        <div class="mt-1 text-xs text-secondary-700">
                                            Diverifikasi {{ $payment->verified_at->format('d M Y H:i') }}
                                            @if ($payment->verifier)
                                                oleh {{ $payment->verifier->name }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if ($payment->proof_path)
                                    <div class="text-xs">
                                        <span class="text-slate-500">Bukti:</span>
                                        <span class="font-mono text-slate-700">{{ basename($payment->proof_path) }}</span>
                                    </div>
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
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Customer</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Nama</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $order->customer_name }}</dd>
                    </div>
                    @if ($order->phone)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Telepon / WA</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $order->phone }}</dd>
                        </div>
                    @endif
                    @if ($order->email)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Email</dt>
                            <dd class="mt-0.5 text-slate-700 break-all">{{ $order->email }}</dd>
                        </div>
                    @endif
                </dl>
            </x-admin.card>

            <x-admin.card>
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Pengiriman</h2>
                @if ($order->address)
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $order->address }}</p>
                @else
                    <p class="text-sm italic text-slate-500">Alamat belum diisi.</p>
                @endif
                @if ($order->ref_code)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Kode Referral</dt>
                        <dd class="mt-0.5 font-mono text-slate-700">{{ $order->ref_code }}</dd>
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card>
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Aksi</h2>
                <p class="text-xs text-slate-500">
                    Verifikasi bayar &amp; transition status (approve/reject, input resi)
                    akan tersedia di task berikutnya. Saat ini halaman ini read-only.
                </p>
            </x-admin.card>
        </div>
    </div>
@endsection
