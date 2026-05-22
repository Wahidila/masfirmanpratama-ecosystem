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
    | `coming_soon` = placeholder disabled (akan jadi link saat fase berikutnya)
    |
    */

    'primary' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'route' => 'admin.dashboard', 'enabled' => true],
        ['key' => 'products', 'label' => 'Produk', 'icon' => 'package', 'route' => 'admin.products.index', 'enabled' => true],
        ['key' => 'orders', 'label' => 'Pesanan', 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'enabled' => true],
        ['key' => 'wa-notifications', 'label' => 'WA Notifikasi', 'icon' => 'message-square', 'route' => 'admin.wa-notifications.index', 'enabled' => true],
        ['key' => 'installments', 'label' => 'Skema Cicilan', 'icon' => 'layers', 'route' => 'admin.installment-schemes.index', 'enabled' => true],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings.index', 'enabled' => true],
    ],

    'coming_soon' => [],
];
