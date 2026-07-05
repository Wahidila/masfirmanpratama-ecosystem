@props([
    'status' => '',
    'label' => null,
])

@php
    // Peta status → [variant badge, label default, ikon].
    // Mencakup: affiliator, komisi, withdrawal, referral, event, materi, reward.
    $map = [
        // affiliator
        'active' => ['success', 'Aktif', 'check-circle'],
        'pending' => ['warning', 'Menunggu', 'clock'],
        'suspended' => ['danger', 'Disuspend', 'ban'],
        'inactive' => ['neutral', 'Nonaktif', 'circle-slash'],
        // komisi
        'cooling' => ['warning', 'Cooling', 'hourglass'],
        'available' => ['success', 'Tersedia', 'wallet'],
        'withdrawn' => ['info', 'Ditarik', 'banknote'],
        'cancelled' => ['neutral', 'Dibatalkan', 'x-circle'],
        // withdrawal
        'processing' => ['info', 'Diproses', 'loader'],
        'approved' => ['success', 'Disetujui', 'check-circle'],
        'completed' => ['success', 'Selesai', 'check-circle'],
        'paid' => ['success', 'Dibayar', 'check-circle'],
        'rejected' => ['danger', 'Ditolak', 'x-circle'],
        // event
        'ended' => ['neutral', 'Berakhir', 'flag'],
        'upcoming' => ['info', 'Akan Datang', 'calendar-clock'],
        'draft' => ['neutral', 'Draft', 'file'],
        // generic boolean-ish
        'read' => ['neutral', 'Dibaca', 'check'],
        'unread' => ['primary', 'Baru', 'dot'],
        'claimed' => ['success', 'Diklaim', 'check-circle'],
        'unclaimed' => ['warning', 'Belum Diklaim', 'gift'],
    ];

    $key = strtolower(trim((string) $status));
    [$variant, $defaultLabel, $icon] = $map[$key] ?? ['neutral', ucfirst($key ?: '—'), null];
    $text = $label ?? $defaultLabel;
@endphp

<x-badge :variant="$variant" :icon="$icon" {{ $attributes }}>{{ $text }}</x-badge>
