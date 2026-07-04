@php
    /** @var string $orderNumber */
    /** @var \App\Models\Order|null $dbOrder */
    $dbOrder = $dbOrder ?? null;
    $trackingHistory = $trackingHistory ?? null;

    /*
    |--------------------------------------------------------------------------
    | Track order — M1 dummy data
    |--------------------------------------------------------------------------
    |
    | M1 = view-only, semua data di-derive dari `order_number` supaya QA dan
    | stakeholder bisa lihat 6 varian status tanpa wire DB. M2 akan ganti
    | dengan TrackController@show → fetch dari `orders`, `order_payments`,
    | dan `order_items`.
    |
    | Heuristik dummy:
    |   - suffix terakhir order_number (uppercase A–Z) menentukan status.
    |   - 'A','B'  → unpaid
    |   - 'C','D'  → waiting_confirmation
    |   - 'E','F'  → paid
    |   - 'G','H'  → partial_paid (cicilan jalan)
    |   - 'I','J','K','L','M','N','O','P','Q','R'  → processing
    |   - 'S','T','U','V','W','X','Y','Z' (default) → completed
    |
    | Order spesimen `MFP-20260516-ABC123` → suffix '3' → fallback 'completed'.
    | Buat status lain, tinggal pakai order number yang ujungnya sesuai huruf.
    |
    */

    $statusKeys = [
        'unpaid',
        'waiting_confirmation',
        'paid',
        'partial_paid',
        'processing',
        'completed',
    ];

    $statusMeta = [
        'unpaid' => [
            'label' => 'Menunggu Pembayaran',
            'desc' => 'Pesanan dibuat, belum ada bukti bayar yang diupload.',
            'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'dot' => 'bg-amber-500',
            'icon' => 'wallet',
        ],
        'waiting_confirmation' => [
            'label' => 'Bukti Diupload — Menunggu Verifikasi',
            'desc' => 'Bukti bayar sudah masuk, tim kami sedang verifikasi (1×24 jam kerja).',
            'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'dot' => 'bg-sky-500',
            'icon' => 'clock-3',
        ],
        'paid' => [
            'label' => 'Lunas — Menunggu Diproses',
            'desc' => 'Pembayaran lunas terverifikasi. Pesanan akan segera diproses tim.',
            'badge' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
            'dot' => 'bg-secondary-500',
            'icon' => 'badge-check',
        ],
        'partial_paid' => [
            'label' => 'Cicilan Berjalan',
            'desc' => 'DP terverifikasi. Lanjutkan pembayaran cicilan sesuai jadwal di bawah.',
            'badge' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'dot' => 'bg-indigo-500',
            'icon' => 'calendar-clock',
        ],
        'processing' => [
            'label' => 'Sedang Diproses',
            'desc' => 'Pesanan sedang disiapkan untuk pengiriman.',
            'badge' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'dot' => 'bg-violet-500',
            'icon' => 'package',
        ],
        'shipped' => [
            'label' => 'Sedang Dikirim',
            'desc' => 'Pesanan sudah diserahkan ke kurir. Lacak dengan nomor resi di bawah.',
            'badge' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'dot' => 'bg-blue-500',
            'icon' => 'truck',
        ],
        'completed' => [
            'label' => 'Pesanan Selesai',
            'desc' => 'Pesanan sudah selesai. Terima kasih sudah berbelanja di Firman Pratama.',
            'badge' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
            'dot' => 'bg-secondary-500',
            'icon' => 'check-circle-2',
        ],
        'cancelled' => [
            'label' => 'Pesanan Dibatalkan',
            'desc' => 'Pesanan ini dibatalkan. Hubungi admin bila ini keliru.',
            'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'dot' => 'bg-rose-500',
            'icon' => 'x-circle',
        ],
        'refunded' => [
            'label' => 'Dana Dikembalikan',
            'desc' => 'Pembayaran pesanan ini sudah direfund.',
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-300',
            'dot' => 'bg-slate-500',
            'icon' => 'rotate-ccw',
        ],
    ];

    /*
    | Pilih status dari suffix.
    */
    $rawSuffix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $orderNumber) ?: 'Z', -1));
    $suffix = $rawSuffix !== '' ? $rawSuffix : 'Z';
    $statusBucket = match (true) {
        in_array($suffix, ['A', 'B'], true) => 'unpaid',
        in_array($suffix, ['C', 'D'], true) => 'waiting_confirmation',
        in_array($suffix, ['E', 'F'], true) => 'paid',
        in_array($suffix, ['G', 'H'], true) => 'partial_paid',
        in_array($suffix, ['I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'], true) => 'processing',
        default => 'completed',
    };

    $isInstallment = in_array($statusBucket, ['partial_paid'], true);
    $hasPhysicalShipping = in_array($statusBucket, ['processing', 'completed'], true);

    /*
    | Timeline 6 step — semantic key untuk visual:
    |   pending_done = grey checkmark, sudah lewat
    |   active       = warna primer + dot pulse, step yang sedang berjalan
    |   pending      = abu, belum dilakukan
    */
    $stepDefs = [
        ['key' => 'created', 'label' => 'Pesanan Dibuat', 'icon' => 'shopping-bag'],
        ['key' => 'uploaded', 'label' => 'Bukti Diupload', 'icon' => 'upload-cloud'],
        ['key' => 'verified', 'label' => 'Pembayaran Diverifikasi', 'icon' => 'badge-check'],
        ['key' => 'processing', 'label' => 'Pesanan Diproses', 'icon' => 'package'],
        ['key' => 'shipped', 'label' => 'Dikirim', 'icon' => 'truck'],
        ['key' => 'completed', 'label' => 'Selesai', 'icon' => 'check-circle-2'],
    ];

    /*
    | Mapping status → posisi pointer di timeline (1-based).
    */
    $stepPointer = match ($statusBucket) {
        'unpaid' => 1,                  // baru dibuat
        'waiting_confirmation' => 2,    // bukti masuk, lagi diverifikasi
        'paid' => 3,                    // verified, belum diproses
        'partial_paid' => 3,            // dp verified, belum diproses
        'processing' => 4,              // sedang dikemas / disiapkan
        'completed' => 6,               // selesai
        default => 1,
    };

    /*
    | Anchor tanggal: tanggal “hari ini” digeser mundur supaya step terakhir
    | jatuh di tanggal yang masuk akal. Pakai now() untuk dummy.
    */
    $anchor = \Illuminate\Support\Carbon::now();
    $stepDates = [
        1 => $anchor->copy()->subDays(7),
        2 => $anchor->copy()->subDays(6),
        3 => $anchor->copy()->subDays(5),
        4 => $anchor->copy()->subDays(3),
        5 => $anchor->copy()->subDays(2),
        6 => $anchor->copy()->subDays(1),
    ];

    /*
    | Order items dummy. Kombinasi kelas + buku biar timeline pengiriman
    | masuk akal di sebagian status.
    */
    $orderItems = [
        [
            'name' => 'Kelas Reguler AMC',
            'category' => 'Kelas',
            'qty' => 1,
            'price' => 4500000,
            'image' => asset('images/placeholders/produk-kelas-amc.webp'),
            'physical' => false,
        ],
        [
            'name' => 'Buku — Mind Power & Life Mastery',
            'category' => 'Buku',
            'qty' => 1,
            'price' => 185000,
            'image' => asset('images/placeholders/produk-buku-mpl.webp'),
            'physical' => true,
        ],
    ];
    $itemsSubtotal = collect($orderItems)->sum(fn ($i) => $i['qty'] * $i['price']);
    $shippingCost = collect($orderItems)->contains(fn ($i) => $i['physical']) ? 25000 : 0;
    $grandTotal = $itemsSubtotal + $shippingCost;

    /*
    | Payment history dummy. Status per cicilan:
    |   - pending   : belum diupload
    |   - confirmed : sudah verified
    |   - rejected  : ditolak (mis: gambar tidak terbaca)
    */
    $paymentHistory = match ($statusBucket) {
        'unpaid' => [],
        'waiting_confirmation' => [
            [
                'sequence' => 1,
                'label' => 'Pembayaran Lunas',
                'amount' => $grandTotal,
                'uploaded_at' => $stepDates[2],
                'status' => 'pending',
                'proof_url' => '#',
                'note' => null,
            ],
        ],
        'paid' => [
            [
                'sequence' => 1,
                'label' => 'Pembayaran Lunas',
                'amount' => $grandTotal,
                'uploaded_at' => $stepDates[2],
                'status' => 'confirmed',
                'proof_url' => '#',
                'note' => 'Diverifikasi otomatis (3 digit cocok).',
            ],
        ],
        'partial_paid' => [
            [
                'sequence' => 1,
                'label' => 'Down Payment (30%)',
                'amount' => (int) round($grandTotal * 0.30),
                'uploaded_at' => $stepDates[2],
                'status' => 'confirmed',
                'proof_url' => '#',
                'note' => null,
            ],
            [
                'sequence' => 2,
                'label' => 'Cicilan ke-1 dari 2',
                'amount' => (int) round($grandTotal * 0.35),
                'uploaded_at' => null,
                'status' => 'pending',
                'proof_url' => null,
                'note' => 'Jatuh tempo '.$anchor->copy()->addDays(20)->translatedFormat('d M Y'),
            ],
            [
                'sequence' => 3,
                'label' => 'Cicilan ke-2 dari 2',
                'amount' => (int) round($grandTotal * 0.35),
                'uploaded_at' => null,
                'status' => 'pending',
                'proof_url' => null,
                'note' => 'Jatuh tempo '.$anchor->copy()->addDays(50)->translatedFormat('d M Y'),
            ],
        ],
        'processing' => [
            [
                'sequence' => 1,
                'label' => 'Pembayaran Lunas',
                'amount' => $grandTotal,
                'uploaded_at' => $stepDates[2],
                'status' => 'confirmed',
                'proof_url' => '#',
                'note' => null,
            ],
        ],
        'completed' => [
            [
                'sequence' => 1,
                'label' => 'Pembayaran Lunas',
                'amount' => $grandTotal,
                'uploaded_at' => $stepDates[2],
                'status' => 'confirmed',
                'proof_url' => '#',
                'note' => null,
            ],
        ],
        default => [],
    };

    /*
    | Pengiriman dummy untuk status processing/completed (kalau ada item fisik).
    | Resi format JNE-style biar terbaca masuk akal di mata stakeholder.
    */
    $shipment = null;
    if ($hasPhysicalShipping) {
        $resi = 'JNE'.strtoupper(substr(md5($orderNumber), 0, 10));
        $shipment = [
            'courier_label' => 'JNE Reguler',
            'resi' => $resi,
            'tracking_url' => 'https://www.jne.co.id/id/tracking/trace/awb/'.$resi,
            'shipped_at' => $stepDates[5],
            'eta_label' => $statusBucket === 'completed'
                ? 'Diterima '.$stepDates[6]->translatedFormat('d M Y')
                : 'Estimasi tiba '.$anchor->copy()->addDays(3)->translatedFormat('d M Y'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Real-DB derivation
    |--------------------------------------------------------------------------
    | Kalau order ADA di DB → SEMUA data (status, timeline, pembayaran, item,
    | pengiriman) di-derive dari order asli, bukan dummy suffix. Dummy di atas
    | hanya fallback saat order tidak ditemukan (URL demo/QA).
    */
    if ($dbOrder) {
        $dbOrder->loadMissing([
            'payments' => fn ($q) => $q->orderBy('id'),
            'items.product',
            'items.course',
        ]);

        $payments = $dbOrder->payments;
        $paymentCount = $payments->count();
        $hasProof = $payments->contains(fn ($p) => ! empty($p->proof_path));
        $isInstallment = $paymentCount > 1;

        // Status bucket dari status order + ada/tidaknya bukti bayar.
        $statusBucket = match ($dbOrder->status) {
            'pending' => $hasProof ? 'waiting_confirmation' : 'unpaid',
            'partial_paid' => 'partial_paid',
            'paid' => 'paid',
            'shipped' => 'shipped',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'unpaid',
        };

        // Kelas/kursus = ada item course & tak ada produk fisik berkurir →
        // timeline tanpa "diproses/dikirim". Order tanpa item / campur produk
        // fisik tetap pakai timeline default 6-step.
        $hasPhysicalShipping = $dbOrder->items->contains(
            fn ($i) => $i->product && (bool) $i->product->is_shippable
        );
        $isCourseOrder = ! $hasPhysicalShipping
            && $dbOrder->items->contains(fn ($i) => $i->course_id !== null);

        // Tanggal real per step (fallback ke created_at supaya tidak pernah null).
        $created = \Illuminate\Support\Carbon::parse($dbOrder->created_at);
        $updatedAt = \Illuminate\Support\Carbon::parse($dbOrder->updated_at);
        $firstProof = $payments->first(fn ($p) => ! empty($p->proof_path));
        $uploadedAt = $firstProof && $firstProof->paid_at
            ? \Illuminate\Support\Carbon::parse($firstProof->paid_at)
            : $created;
        $shippedAt = $dbOrder->shipped_at ? \Illuminate\Support\Carbon::parse($dbOrder->shipped_at) : null;

        if (! $isCourseOrder) {
            // Order fisik (buku) / default — timeline 6 step termasuk diproses & dikirim.
            $stepPointer = match ($statusBucket) {
                'unpaid' => 1,
                'waiting_confirmation' => 2,
                'paid', 'partial_paid' => 3,
                'shipped' => 5,
                'completed' => 6,
                default => 0,
            };
            $stepDates = [
                1 => $created,
                2 => $uploadedAt,
                3 => $uploadedAt,
                4 => $shippedAt ?? $updatedAt,
                5 => $shippedAt ?? $updatedAt,
                6 => $updatedAt,
            ];
        } else {
            // Kelas/kursus — non-fisik. Timeline: dibuat → bukti diupload → kelas aktif.
            // Tak ada diproses/dikirim/kurir/resi.
            $stepDefs = [
                ['key' => 'created', 'label' => 'Pendaftaran Dibuat', 'icon' => 'clipboard-list'],
                ['key' => 'uploaded', 'label' => 'Bukti Diupload', 'icon' => 'upload-cloud'],
                ['key' => 'active', 'label' => 'Kelas Aktif', 'icon' => 'graduation-cap'],
            ];
            $stepPointer = match ($statusBucket) {
                'unpaid' => 1,
                'waiting_confirmation' => 2,
                'paid', 'partial_paid', 'shipped', 'completed' => 3,
                default => 0,
            };
            $stepDates = [
                1 => $created,
                2 => $uploadedAt,
                3 => $updatedAt,
            ];

            // Label status yang menyebut pengiriman → versi kelas.
            $statusMeta['paid'] = [
                'label' => 'Lunas — Kelas Aktif',
                'desc' => 'Pembayaran terverifikasi. Akses kelas kamu sudah aktif. 🎉',
                'badge' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
                'dot' => 'bg-secondary-500',
                'icon' => 'graduation-cap',
            ];
            $statusMeta['completed'] = [
                'label' => 'Kelas Selesai',
                'desc' => 'Pendaftaran kelas selesai. Terima kasih sudah bergabung! 🙏',
                'badge' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
                'dot' => 'bg-secondary-500',
                'icon' => 'check-circle-2',
            ];
        }

        // Item pesanan dari DB.
        $orderItems = $dbOrder->items->map(function ($item) {
            $entity = $item->product ?? $item->course;
            $isPhysical = $item->product ? (bool) $item->product->is_shippable : false;

            return [
                'name' => $entity->title ?? 'Item',
                'category' => $item->product ? 'Buku' : 'Kelas',
                'qty' => (int) $item->qty,
                'price' => (int) $item->unit_price,
                'image' => asset($entity->image_path ?? 'images/placeholder.webp'),
                'physical' => $isPhysical,
            ];
        })->values()->all();
        $itemsSubtotal = (int) $dbOrder->items->sum(fn ($i) => (int) $i->subtotal);
        $shippingCost = (int) ($dbOrder->shipping_cost ?? 0);
        $grandTotal = (int) $dbOrder->total;
        $hasPhysicalShipping = collect($orderItems)->contains(fn ($i) => $i['physical']);

        // Riwayat pembayaran dari DB.
        $paymentHistory = $payments->values()->map(function ($p, $idx) use ($paymentCount) {
            $label = $paymentCount <= 1
                ? 'Pembayaran Lunas'
                : ($idx === 0 ? 'Down Payment' : 'Cicilan ke-'.$idx.' dari '.($paymentCount - 1));
            $tone = match ($p->status) {
                'verified' => 'confirmed',
                'rejected' => 'rejected',
                default => 'pending',
            };

            return [
                'sequence' => $idx + 1,
                'label' => $label,
                'amount' => (int) $p->amount,
                'uploaded_at' => $p->paid_at ? \Illuminate\Support\Carbon::parse($p->paid_at) : null,
                'status' => $tone,
                'proof_url' => $p->proof_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($p->proof_path) : null,
                'note' => $p->rejection_reason,
            ];
        })->all();

        // Pengiriman dari DB (hanya setelah resi terbit).
        $shipment = null;
        if ($dbOrder->shipping_resi) {
            $courierKey = strtolower(trim((string) $dbOrder->shipping_courier));
            $courierLabels = [
                'jne' => 'JNE', 'jnt' => 'J&T Express', 'j&t' => 'J&T Express',
                'sicepat' => 'SiCepat', 'pos' => 'Pos Indonesia', 'tiki' => 'TIKI',
                'anteraja' => 'AnterAja', 'spx' => 'SPX', 'lion' => 'Lion Parcel',
                'paxel' => 'Paxel', 'gosend' => 'GoSend', 'jtc' => 'J&T Cargo',
            ];
            $courierTrackUrls = [
                'jne' => 'https://www.jne.co.id/id/tracking/trace/awb/',
                'jnt' => 'https://www.jet.co.id/track/',
                'sicepat' => 'https://www.sicepat.com/checkAwb/',
                'pos' => 'https://www.posindonesia.co.id/id/tracking?awb=',
            ];
            $shipment = [
                'courier_label' => $courierLabels[$courierKey] ?? ($dbOrder->shipping_courier ?: 'Kurir'),
                'resi' => $dbOrder->shipping_resi,
                'tracking_url' => isset($courierTrackUrls[$courierKey]) ? $courierTrackUrls[$courierKey].$dbOrder->shipping_resi : null,
                'shipped_at' => $shippedAt ?? $updatedAt,
                'eta_label' => $dbOrder->status === 'completed'
                    ? 'Diterima'
                    : ($dbOrder->shipping_etd ? 'Estimasi '.$dbOrder->shipping_etd : 'Dalam perjalanan'),
            ];
        }
    }

    /*
    | Status meta untuk badge atas.
    */
    $meta = $statusMeta[$statusBucket];

    /*
    | Helper format Rupiah.
    */
    $rp = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');

    $waAdmin = \App\Services\Settings::getWaAdmin();
    $waText = rawurlencode("Halo Admin, mau tanya status order {$orderNumber}.");
    $waLink = "https://wa.me/{$waAdmin['number']}?text={$waText}";

    /*
    | Map payment status → tone classes (Tailwind kelas penuh, supaya purge
    | tetap pickup).
    */
    $paymentStatusTone = [
        'pending' => [
            'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'label' => 'Pending',
            'icon' => 'clock-3',
        ],
        'confirmed' => [
            'badge' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
            'label' => 'Diverifikasi',
            'icon' => 'badge-check',
        ],
        'rejected' => [
            'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'label' => 'Ditolak',
            'icon' => 'x-circle',
        ],
    ];
@endphp

<x-layouts.store
    :title="'Lacak Pesanan — '.$orderNumber"
    description="Lacak status pesanan dan pembayaran Anda di Firman Pratama."
    bodyClass="relative"
>
    {{-- Decorative blobs (mengikuti styling halaman lain di flow) --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-24 -top-20 h-80 w-80 rounded-full bg-primary-200/70 blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-secondary-200/70 blur-3xl animate-blob"></div>
    </div>

    <section
        id="trackOrderPage"
        class="mx-auto w-full max-w-5xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
        x-data="trackOrderPage({ orderNumber: @js($orderNumber) })"
    >
        {{-- ================================================================ --}}
        {{-- Header: order number + status                                    --}}
        {{-- ================================================================ --}}
        <header class="text-center sm:text-left">
            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-primary-600">Lacak Pesanan</p>
            <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl md:text-5xl">
                        Order
                        <span
                            class="break-all font-mono text-primary-700"
                            data-testid="order-number"
                        >{{ $orderNumber }}</span>
                    </h1>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 sm:text-lg">
                        {{ $meta['desc'] }}
                    </p>
                </div>
                <div class="shrink-0">
                    <span
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $meta['badge'] }}"
                        data-testid="status-badge"
                        data-status="{{ $statusBucket }}"
                    >
                        <i data-lucide="{{ $meta['icon'] }}" class="h-4 w-4"></i>
                        <span>{{ $meta['label'] }}</span>
                    </span>
                </div>
            </div>
        </header>

        {{-- Salin nomor pesanan + tombol bantuan --}}
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button
                type="button"
                @click="copyOrderNumber()"
                :disabled="copied"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-600 disabled:cursor-not-allowed disabled:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
                :aria-label="copied ? 'Nomor pesanan tersalin' : 'Salin nomor pesanan'"
            >
                <i :data-lucide="copied ? 'check' : 'copy'" class="h-4 w-4"></i>
                <span x-text="copied ? 'Tersalin!' : 'Salin nomor pesanan'"></span>
            </button>
            <a
                href="{{ $waLink }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-full border border-secondary-200 bg-secondary-50 px-4 py-2 text-sm font-semibold text-secondary-700 transition hover:border-secondary-300 hover:bg-secondary-100"
            >
                <i data-lucide="message-circle" class="h-4 w-4"></i>
                Tanya Admin
            </a>
            @if (in_array($statusBucket, ['unpaid', 'partial_paid'], true))
                <a
                    href="{{ route('upload.show', ['order_number' => $orderNumber]) }}"
                    class="ripple inline-flex min-h-[44px] items-center gap-2 rounded-full bg-primary-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700"
                    data-testid="cta-upload"
                >
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                    Upload bukti bayar
                </a>
            @endif
        </div>

        {{-- ================================================================ --}}
        {{-- Timeline 6 step                                                  --}}
        {{-- ================================================================ --}}
        <section
            id="trackTimeline"
            class="panel-card glass mt-10 rounded-3xl border border-white/60 p-6 sm:p-8"
            aria-labelledby="trackTimelineLabel"
            data-testid="status-timeline"
        >
            <header class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                    <i data-lucide="route" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 id="trackTimelineLabel" class="text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                        Status Perjalanan Pesanan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ count($stepDefs) }} tahap dari pesanan dibuat sampai {{ ($isCourseOrder ?? false) ? 'kelas aktif' : 'selesai diterima' }}.
                    </p>
                </div>
            </header>

            {{-- Desktop: horizontal timeline ──────────────────────── --}}
            {{-- Kolom mengikuti jumlah step ($stepDefs) — kelas 3 step, fisik 6 step --}}
            <ol
                class="mt-8 hidden gap-2 md:grid"
                style="grid-template-columns: repeat({{ count($stepDefs) }}, minmax(0, 1fr));"
                role="list"
                aria-label="Timeline status pesanan"
            >
                @foreach ($stepDefs as $i => $step)
                    @php
                        $idx = $i + 1;
                        $isDone = $idx < $stepPointer;
                        $isActive = $idx === $stepPointer;
                        $isPending = $idx > $stepPointer;
                        // Connector ke step berikutnya: filled jika step ini done.
                        $connectorFilled = $idx < $stepPointer;
                    @endphp
                    <li class="relative flex flex-col items-center text-center" data-step="{{ $step['key'] }}">
                        {{-- Connector line kiri --}}
                        @if ($idx > 1)
                            <span
                                class="absolute left-0 top-5 -ml-1 h-0.5 w-1/2 {{ $idx <= $stepPointer ? 'bg-primary-500' : 'bg-slate-200' }}"
                                aria-hidden="true"
                            ></span>
                        @endif
                        {{-- Connector line kanan --}}
                        @if ($idx < count($stepDefs))
                            <span
                                class="absolute right-0 top-5 -mr-1 h-0.5 w-1/2 {{ $connectorFilled ? 'bg-primary-500' : 'bg-slate-200' }}"
                                aria-hidden="true"
                            ></span>
                        @endif

                        <span
                            @class([
                                'relative z-10 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-4 ring-white transition',
                                'bg-primary-600 text-white shadow-md shadow-primary-500/30' => $isDone,
                                'bg-primary-600 text-white shadow-lg shadow-primary-500/40 animate-pulse' => $isActive,
                                'bg-slate-100 text-slate-500' => $isPending,
                            ])
                            :aria-current="{{ $isActive ? "'step'" : "null" }}"
                        >
                            @if ($isDone)
                                <i data-lucide="check" class="h-5 w-5"></i>
                            @else
                                <i data-lucide="{{ $step['icon'] }}" class="h-5 w-5"></i>
                            @endif
                        </span>
                        <p @class([
                            'mt-3 text-xs font-bold leading-tight sm:text-sm',
                            'text-slate-900' => $isDone || $isActive,
                            'text-slate-500' => $isPending,
                        ])>{{ $step['label'] }}</p>
                        <p @class([
                            'mt-1 text-[11px] sm:text-xs',
                            'text-slate-600' => $isDone || $isActive,
                            'text-slate-500' => $isPending,
                        ])>
                            @if ($isDone || $isActive)
                                {{ $stepDates[$idx]->translatedFormat('d M Y') }}
                            @else
                                &mdash;
                            @endif
                        </p>
                    </li>
                @endforeach
            </ol>

            {{-- Mobile: vertical timeline ──────────────────────────── --}}
            <ol class="mt-8 space-y-0 md:hidden" role="list" aria-label="Timeline status pesanan">
                @foreach ($stepDefs as $i => $step)
                    @php
                        $idx = $i + 1;
                        $isDone = $idx < $stepPointer;
                        $isActive = $idx === $stepPointer;
                        $isPending = $idx > $stepPointer;
                        $isLast = $idx === count($stepDefs);
                    @endphp
                    <li class="relative flex gap-4 pb-6 last:pb-0" data-step="{{ $step['key'] }}">
                        {{-- Vertical connector --}}
                        @unless ($isLast)
                            <span
                                class="absolute left-5 top-10 h-full w-0.5 {{ $idx < $stepPointer ? 'bg-primary-500' : 'bg-slate-200' }}"
                                aria-hidden="true"
                            ></span>
                        @endunless
                        <span
                            @class([
                                'relative z-10 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-4 ring-white',
                                'bg-primary-600 text-white shadow-md shadow-primary-500/30' => $isDone,
                                'bg-primary-600 text-white shadow-lg shadow-primary-500/40 animate-pulse' => $isActive,
                                'bg-slate-100 text-slate-500' => $isPending,
                            ])
                            :aria-current="{{ $isActive ? "'step'" : "null" }}"
                        >
                            @if ($isDone)
                                <i data-lucide="check" class="h-5 w-5"></i>
                            @else
                                <i data-lucide="{{ $step['icon'] }}" class="h-5 w-5"></i>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1 pt-1">
                            <p @class([
                                'text-sm font-bold leading-tight',
                                'text-slate-900' => $isDone || $isActive,
                                'text-slate-500' => $isPending,
                            ])>{{ $step['label'] }}</p>
                            <p @class([
                                'mt-1 text-xs',
                                'text-slate-600' => $isDone || $isActive,
                                'text-slate-500' => $isPending,
                            ])>
                                @if ($isDone || $isActive)
                                    {{ $stepDates[$idx]->translatedFormat('d M Y') }}
                                @else
                                    Belum dilakukan
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ================================================================ --}}
        {{-- Order items                                                      --}}
        {{-- ================================================================ --}}
        <section
            id="orderItemsCard"
            class="panel-card glass mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
            aria-labelledby="orderItemsLabel"
            data-testid="order-items"
        >
            <header class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                    <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 id="orderItemsLabel" class="text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                        Item Pesanan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ count($orderItems) }} item dalam pesanan ini.
                    </p>
                </div>
            </header>

            <ul class="mt-6 space-y-4" role="list">
                @foreach ($orderItems as $item)
                    <li class="flex flex-col gap-4 rounded-2xl border border-slate-100 bg-white/90 p-4 sm:flex-row sm:items-center sm:p-5">
                        <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:h-28 sm:w-28">
                            <img
                                src="{{ $item['image'] }}"
                                alt="{{ $item['name'] }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');"
                            >
                            <div class="hidden h-full w-full items-center justify-center text-slate-300">
                                <i data-lucide="image" class="h-10 w-10"></i>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">
                                {{ $item['category'] }}
                                @if ($item['physical'])
                                    <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200">
                                        <i data-lucide="package" class="h-3 w-3"></i>
                                        Fisik
                                    </span>
                                @endif
                            </p>
                            <h3 class="mt-1 text-base font-bold leading-tight text-slate-900 sm:text-lg">{{ $item['name'] }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Qty {{ $item['qty'] }} &times; {{ $rp($item['price']) }}
                            </p>
                        </div>
                        <p class="text-right text-lg font-extrabold text-slate-900 sm:text-xl">
                            {{ $rp($item['qty'] * $item['price']) }}
                        </p>
                    </li>
                @endforeach
            </ul>

            <dl class="mt-6 grid gap-2 rounded-2xl bg-slate-50/70 px-5 py-4 text-sm sm:grid-cols-2">
                <div class="flex items-center justify-between sm:flex-col sm:items-start">
                    <dt class="font-semibold text-slate-500">Subtotal Produk</dt>
                    <dd class="font-bold text-slate-900">{{ $rp($itemsSubtotal) }}</dd>
                </div>
                <div class="flex items-center justify-between sm:flex-col sm:items-start">
                    <dt class="font-semibold text-slate-500">Ongkir</dt>
                    <dd class="font-bold text-slate-900">{{ $shippingCost > 0 ? $rp($shippingCost) : '—' }}</dd>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-3 sm:col-span-2">
                    <dt class="text-base font-bold text-slate-900">Total Pesanan</dt>
                    <dd class="text-lg font-extrabold text-primary-700">{{ $rp($grandTotal) }}</dd>
                </div>
            </dl>
        </section>

        {{-- ================================================================ --}}
        {{-- Payment history                                                  --}}
        {{-- ================================================================ --}}
        <section
            id="paymentHistoryCard"
            class="panel-card glass mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
            aria-labelledby="paymentHistoryLabel"
            data-testid="payment-history"
        >
            <header class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                    <i data-lucide="receipt" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="paymentHistoryLabel" class="text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                        Riwayat Pembayaran
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($isInstallment)
                            Cicilan terdaftar untuk pesanan ini. Reminder otomatis dikirim H-3.
                        @else
                            Status pembayaran untuk pesanan ini.
                        @endif
                    </p>
                </div>
            </header>

            @if (count($paymentHistory) === 0)
                <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-5 py-8 text-center">
                    <i data-lucide="inbox" class="mx-auto h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada bukti bayar yang diupload.</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Setelah transfer, lanjutkan dengan upload bukti bayar agar pesanan bisa diverifikasi.
                    </p>
                    <a
                        href="{{ route('upload.show', ['order_number' => $orderNumber]) }}"
                        class="mt-5 inline-flex items-center gap-2 rounded-full bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700"
                    >
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                        Upload bukti bayar
                    </a>
                </div>
            @else
                {{-- Desktop table ─────────────────────────────────────── --}}
                <div class="mt-6 hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm" data-testid="payment-history-table">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-2.5">Cicilan</th>
                                <th class="px-4 py-2.5">Nominal</th>
                                <th class="px-4 py-2.5">Tanggal Upload</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($paymentHistory as $row)
                                @php
                                    $tone = $paymentStatusTone[$row['status']] ?? $paymentStatusTone['pending'];
                                @endphp
                                <tr class="bg-white" data-testid="payment-row" data-status="{{ $row['status'] }}">
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-slate-900">{{ $row['label'] }}</p>
                                        @if (! empty($row['note']))
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $row['note'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top font-bold text-slate-900">{{ $rp($row['amount']) }}</td>
                                    <td class="px-4 py-3 align-top text-slate-600">
                                        @if (! empty($row['uploaded_at']))
                                            {{ $row['uploaded_at']->translatedFormat('d M Y H:i') }}
                                        @else
                                            <span class="text-slate-500">Belum diupload</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $tone['badge'] }}">
                                            <i data-lucide="{{ $tone['icon'] }}" class="h-3.5 w-3.5"></i>
                                            {{ $tone['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if (! empty($row['proof_url']))
                                            <a
                                                href="{{ $row['proof_url'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:underline"
                                            >
                                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                                Lihat bukti
                                            </a>
                                        @else
                                            <a
                                                href="{{ route('upload.show', ['order_number' => $orderNumber]) }}"
                                                class="inline-flex items-center gap-1 text-sm font-semibold text-amber-700 hover:underline"
                                            >
                                                <i data-lucide="upload-cloud" class="h-3.5 w-3.5"></i>
                                                Upload
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards (table → cards) ───────────────────────── --}}
                <ul class="mt-6 space-y-3 md:hidden" role="list">
                    @foreach ($paymentHistory as $row)
                        @php
                            $tone = $paymentStatusTone[$row['status']] ?? $paymentStatusTone['pending'];
                        @endphp
                        <li
                            class="rounded-2xl border border-slate-100 bg-white/95 p-4"
                            data-testid="payment-row-mobile"
                            data-status="{{ $row['status'] }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900">{{ $row['label'] }}</p>
                                    <p class="mt-0.5 text-base font-extrabold text-slate-900">{{ $rp($row['amount']) }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $tone['badge'] }}">
                                    <i data-lucide="{{ $tone['icon'] }}" class="h-3.5 w-3.5"></i>
                                    {{ $tone['label'] }}
                                </span>
                            </div>
                            <dl class="mt-3 space-y-1 text-xs">
                                <div class="flex items-center justify-between">
                                    <dt class="font-semibold text-slate-500">Tanggal upload</dt>
                                    <dd class="text-slate-700">
                                        @if (! empty($row['uploaded_at']))
                                            {{ $row['uploaded_at']->translatedFormat('d M Y H:i') }}
                                        @else
                                            <span class="text-slate-500">Belum diupload</span>
                                        @endif
                                    </dd>
                                </div>
                                @if (! empty($row['note']))
                                    <p class="text-slate-500">{{ $row['note'] }}</p>
                                @endif
                            </dl>
                            <div class="mt-3">
                                @if (! empty($row['proof_url']))
                                    <a
                                        href="{{ $row['proof_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:border-primary-300 hover:text-primary-600"
                                    >
                                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        Lihat bukti
                                    </a>
                                @else
                                    <a
                                        href="{{ route('upload.show', ['order_number' => $orderNumber]) }}"
                                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-full bg-primary-600 px-4 py-2 text-xs font-bold text-white"
                                    >
                                        <i data-lucide="upload-cloud" class="h-3.5 w-3.5"></i>
                                        Upload bukti
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- ================================================================ --}}
        {{-- Pengiriman (kalau ada item fisik dan sudah dikirim/selesai)      --}}
        {{-- ================================================================ --}}
        @if ($shipment)
            <section
                id="shipmentCard"
                class="panel-card glass mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
                aria-labelledby="shipmentLabel"
                data-testid="shipment-card"
            >
                <header class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                        <i data-lucide="truck" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 id="shipmentLabel" class="text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                            Pengiriman
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $shipment['eta_label'] }}.
                        </p>
                    </div>
                </header>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-white/95 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kurir</p>
                        <p class="mt-1 text-base font-bold text-slate-900">{{ $shipment['courier_label'] }}</p>
                        <p class="mt-2 text-xs text-slate-500">
                            Dikirim {{ $shipment['shipped_at']->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white/95 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Nomor Resi</p>
                        <div class="mt-1 flex items-center gap-2">
                            <p
                                class="font-mono text-base font-bold tracking-wider text-slate-900"
                                data-testid="resi-number"
                            >{{ $shipment['resi'] }}</p>
                            <button
                                type="button"
                                @click="copyResi(@js($shipment['resi']))"
                                class="inline-flex min-h-[44px] shrink-0 items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:border-primary-300 hover:text-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
                                :aria-label="resiCopied ? 'Resi tersalin' : 'Salin resi'"
                            >
                                <i :data-lucide="resiCopied ? 'check' : 'copy'" class="h-3 w-3"></i>
                                <span x-text="resiCopied ? 'Tersalin' : 'Salin'"></span>
                            </button>
                        </div>
                        @if (! empty($shipment['tracking_url']))
                            <a
                                href="{{ $shipment['tracking_url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:underline"
                                data-testid="tracking-link"
                            >
                                <i data-lucide="external-link" class="h-4 w-4"></i>
                                Lacak di situs kurir
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- ================================================================ --}}
        {{-- Tracking History (live dari Agenwebsite API) — hanya order fisik. --}}
        {{-- Kelas/kursus tidak punya paket/kurir → sembunyikan sepenuhnya.    --}}
        {{-- ================================================================ --}}
        @if (! ($isCourseOrder ?? false))
        @if (!is_null($trackingHistory))
            <section
                id="trackingHistoryCard"
                class="panel-card glass mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
                aria-labelledby="trackingHistoryLabel"
                data-testid="tracking-history-card"
            >
                <header class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                        <i data-lucide="truck" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 id="trackingHistoryLabel" class="text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                            Riwayat Lacak Paket
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Update terkini pengiriman paket Anda.
                        </p>
                    </div>
                </header>

                @if (count($trackingHistory) > 0)
                    <ol class="mt-6 space-y-0" role="list" aria-label="Tracking history timeline">
                        @foreach ($trackingHistory as $i => $entry)
                            @php
                                $isFirst = $i === 0;
                                $isLast = $i === count($trackingHistory) - 1;
                                $datetime = $entry['datetime'] ?? $entry['date'] ?? $entry['time'] ?? null;
                                $status = $entry['status'] ?? $entry['description'] ?? '';
                                $location = $entry['location'] ?? $entry['city'] ?? null;
                            @endphp
                            <li class="relative flex gap-4 pb-6 last:pb-0" data-testid="tracking-entry">
                                @unless ($isLast)
                                    <span class="absolute left-5 top-10 h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                @endunless
                                <span
                                    @class([
                                        'relative z-10 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-4 ring-white',
                                        'bg-secondary-600 text-white' => $isFirst,
                                        'bg-slate-100 text-slate-500' => !$isFirst,
                                    ])
                                >
                                    @if ($isFirst)
                                        <i data-lucide="map-pin" class="h-5 w-5"></i>
                                    @else
                                        <i data-lucide="circle" class="h-3 w-3"></i>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1 pt-1">
                                    <p class="text-sm font-bold leading-tight text-slate-900">{{ $status }}</p>
                                    @if ($datetime)
                                        <p class="mt-0.5 text-xs text-slate-600">{{ $datetime }}</p>
                                    @endif
                                    @if ($location)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $location }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-5 py-8 text-center">
                        <i data-lucide="package-search" class="mx-auto h-8 w-8 text-slate-300"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Melacak paket...</p>
                        <p class="mt-1 text-sm text-slate-500">
                            Data pelacakan sedang diperbarui. Coba lagi beberapa saat.
                        </p>
                    </div>
                @endif
            </section>
        @else
            <section
                id="trackingHistoryCard"
                class="panel-card glass mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
                aria-labelledby="trackingHistoryLabel"
                data-testid="tracking-history-card"
            >
                <header class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                        <i data-lucide="truck" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 id="trackingHistoryLabel" class="text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                            Riwayat Lacak Paket
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Update terkini pengiriman paket Anda.
                        </p>
                    </div>
                </header>
                <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-5 py-8 text-center">
                    <i data-lucide="package-search" class="mx-auto h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Resi belum tersedia</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Nomor resi akan muncul setelah admin menginput pengiriman.
                    </p>
                </div>
            </section>
        @endif
        @endif {{-- /order fisik --}}

        {{-- ================================================================ --}}
        {{-- Footer note                                                      --}}
        {{-- ================================================================ --}}
        <p class="mt-10 text-center text-sm text-slate-500">
            Butuh bantuan? <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary-600 hover:underline">Chat admin di WhatsApp</a> atau
            <a href="{{ route('home') }}" class="font-semibold text-primary-600 hover:underline">kembali ke beranda</a>.
        </p>
    </section>

    {{-- ────────────────────────────────────────────────────────── --}}
    {{-- Alpine page component                                       --}}
    {{-- ────────────────────────────────────────────────────────── --}}
    <x-slot name="scripts">
        <script>
            window.trackOrderPage = function (cfg) {
                return {
                    orderNumber: cfg.orderNumber || '',
                    copied: false,
                    resiCopied: false,
                    _orderTimer: null,
                    _resiTimer: null,

                    init() {
                        this.$watch('copied', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
                        this.$watch('resiCopied', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
                    },

                    async copyOrderNumber() {
                        await this._copyToClipboard(this.orderNumber);
                        this.copied = true;
                        clearTimeout(this._orderTimer);
                        this._orderTimer = setTimeout(() => { this.copied = false; }, 2000);
                    },

                    async copyResi(resi) {
                        await this._copyToClipboard(String(resi || ''));
                        this.resiCopied = true;
                        clearTimeout(this._resiTimer);
                        this._resiTimer = setTimeout(() => { this.resiCopied = false; }, 2000);
                    },

                    async _copyToClipboard(value) {
                        const text = String(value || '');
                        if (!text) return;
                        try {
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(text);
                                return;
                            }
                        } catch (e) {
                            // fall through
                        }
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        ta.setAttribute('readonly', '');
                        ta.style.position = 'absolute';
                        ta.style.left = '-9999px';
                        document.body.appendChild(ta);
                        ta.select();
                        try {
                            document.execCommand('copy');
                        } catch (e) {
                            window.prompt('Salin manual:', text);
                        } finally {
                            document.body.removeChild(ta);
                        }
                    },
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
