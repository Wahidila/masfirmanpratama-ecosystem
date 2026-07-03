<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Panel — Navigation Config
    |--------------------------------------------------------------------------
    |
    | Single source of truth untuk struktur nav admin panel. Dipakai oleh:
    | - resources/views/components/admin/sidebar.blade.php (desktop sidebar)
    | - resources/views/layouts/admin.blade.php (mobile drawer)
    |
    | `primary` = nav links utama (semua viewport)
    |
    */

    'primary' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'route' => 'admin.dashboard', 'enabled' => true],
        ['key' => 'products', 'label' => 'Produk', 'icon' => 'package', 'route' => 'admin.products.index', 'enabled' => true],
        ['key' => 'courses', 'label' => 'Kelas', 'icon' => 'graduation-cap', 'route' => 'admin.courses.index', 'enabled' => true],
        ['key' => 'posts', 'label' => 'Blog', 'icon' => 'file-text', 'route' => 'admin.posts.index', 'enabled' => true],
        ['key' => 'video-testimonials', 'label' => 'Testimoni Video', 'icon' => 'video', 'route' => 'admin.video-testimonials.index', 'enabled' => true],
        ['key' => 'orders', 'label' => 'Pesanan', 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'enabled' => true],
        ['key' => 'reports', 'label' => 'Laporan', 'icon' => 'bar-chart', 'route' => 'admin.reports.index', 'enabled' => true],
        ['key' => 'wa-notifications', 'label' => 'WA Notifikasi', 'icon' => 'message-square', 'route' => 'admin.wa-notifications.index', 'enabled' => true],
        ['key' => 'installments', 'label' => 'Skema Cicilan', 'icon' => 'layers', 'route' => 'admin.installment-schemes.index', 'enabled' => true],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings.index', 'enabled' => true],
    ],
];
