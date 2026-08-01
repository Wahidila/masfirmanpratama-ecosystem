<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Panel — Navigation Config
    |--------------------------------------------------------------------------
    |
    | Single source of truth untuk struktur nav admin panel. Dipakai oleh:
    | - resources/views/layouts/partials/admin-sidebar.blade.php (via MenuHelper::getMenuGroups, dikelompokkan)
    | - resources/views/components/admin/sidebar.blade.php + _nav-links (flat, pakai `primary`)
    |
    | `primary` = definisi tiap item (satu sumber props: label/icon/route).
    | `groups`  = pengelompokan sidebar; tiap grup punya judul (jadi header <h2>
    |             seperti "MENU") + daftar `key` yang merujuk ke `primary`.
    |
    */

    'primary' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'route' => 'admin.dashboard', 'enabled' => true],
        ['key' => 'products', 'label' => 'Produk', 'icon' => 'package', 'route' => 'admin.products.index', 'enabled' => true],
        ['key' => 'courses', 'label' => 'Kelas', 'icon' => 'graduation-cap', 'route' => 'admin.courses.index', 'enabled' => true],
        ['key' => 'participants', 'label' => 'Peserta Kursus', 'icon' => 'users', 'route' => 'admin.participants.index', 'enabled' => true],
        ['key' => 'posts', 'label' => 'Blog', 'icon' => 'file-text', 'route' => 'admin.posts.index', 'enabled' => true],
        ['key' => 'video-testimonials', 'label' => 'Testimoni Video', 'icon' => 'video', 'route' => 'admin.video-testimonials.index', 'enabled' => true],
        ['key' => 'promo-banners', 'label' => 'Banner Promo', 'icon' => 'image', 'route' => 'admin.promo-banners.index', 'enabled' => true],
        ['key' => 'orders', 'label' => 'Pesanan', 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'enabled' => true],
        ['key' => 'reports', 'label' => 'Laporan', 'icon' => 'bar-chart', 'route' => 'admin.reports.index', 'enabled' => true],
        ['key' => 'wa-notifications', 'label' => 'WA Notifikasi', 'icon' => 'message-square', 'route' => 'admin.wa-notifications.index', 'enabled' => true],
        ['key' => 'installments', 'label' => 'Skema Cicilan', 'icon' => 'layers', 'route' => 'admin.installment-schemes.index', 'enabled' => true],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings.index', 'enabled' => true],
    ],

    // Pengelompokan sidebar berdasarkan fungsi/kepentingan. Urutan grup & item
    // = urutan tampil. Item yang enabled=false atau key tak dikenal dilewati;
    // grup yang jadi kosong tidak dirender.
    'groups' => [
        ['title' => 'Utama', 'items' => ['dashboard', 'orders', 'reports']],
        ['title' => 'Katalog', 'items' => ['products', 'courses', 'participants', 'installments']],
        ['title' => 'Konten & Promosi', 'items' => ['posts', 'video-testimonials', 'promo-banners']],
        ['title' => 'Sistem', 'items' => ['wa-notifications', 'settings']],
    ],
];
