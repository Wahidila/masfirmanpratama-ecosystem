<?php

return [
    'api_url' => env('AGENWEBSITE_SHIPPING_API_URL', 'https://api-v2.agenwebsite.com/v2'),
    'license' => env('AGENWEBSITE_SHIPPING_LICENSE', ''),
    'product' => 'agenwebsite-shipping',
    // Header replikasi wp_remote_post — WAJIB supaya API tidak balik 500.
    // PENTING: site_url HARUS domain terdaftar di license (masfirmanpratama.com),
    // BUKAN APP_URL. License agenwebsite domain-bound — localhost:8052 → HTTP 401
    // "Domain yang Anda gunakan salah". Decoupled dari APP_URL supaya rate jalan
    // di dev/preview/tunnel. Override via AGENWEBSITE_SHIPPING_SITE_URL bila domain ganti.
    'site_url' => env('AGENWEBSITE_SHIPPING_SITE_URL', 'https://masfirmanpratama.com'),
    'user_agent' => env('AGENWEBSITE_SHIPPING_UA', 'WordPress/6.8.3; '.env('AGENWEBSITE_SHIPPING_SITE_URL', 'https://masfirmanpratama.com')),
    'plugin_version' => '2.3.11',
    'wordpress_version' => '6.8.3',
    'woocommerce_version' => '10.0',
    'timeout' => 30,

    // Origin pengiriman (Surabaya)
    'origin' => env('SHIPPING_ORIGIN', 'surabaya'),
    'origin_zipcode' => env('SHIPPING_ORIGIN_ZIPCODE', '60111'),

    // Kurir domestik yang diaktifkan (interseksi dgn service API)
    'couriers' => ['jne', 'jnt', 'sicepat', 'anteraja', 'pos'],

    // Label tampilan per courier_id (dipakai dropdown admin "Tandai Dikirim").
    // Sumber daftar kurir = 'couriers' di atas / Settings 'shipping.couriers';
    // map ini hanya untuk label. id tak dikenal → fallback strtoupper(id).
    // Selaras dengan SettingsController::AVAILABLE_COURIERS (kurir yang bisa
    // diaktifkan admin di tab Shipping). id di luar map ini tetap tampil dgn
    // label strtoupper(id).
    'courier_labels' => [
        'jne' => 'JNE',
        'jnt' => 'J&T Express',
        'sicepat' => 'SiCepat',
        'anteraja' => 'AnterAja',
        'pos' => 'POS Indonesia',
        'tiki' => 'TIKI',
        'spx' => 'Shopee Express',
        'lion' => 'Lion Parcel',
        'paxel' => 'Paxel',
    ],

    // Berat & dimensi default produk (kg & cm) bila produk tak punya data
    'default_weight_kg' => 1,
    'default_dimensions_cm' => ['length' => 10, 'width' => 10, 'height' => 5],

    // Cache TTL (detik): master data couriers/services 24 jam; rate harga pendek.
    'cache_master_ttl' => 86400,
    'cache_rate_ttl' => 1800,
    // Tracking history: pendek supaya /track terasa realtime tanpa spam API.
    // Turunkan (mis. 60) untuk lebih fresh, naikkan untuk hemat kuota API.
    'cache_tracking_ttl' => env('SHIPPING_TRACKING_TTL', 300),

    // Markup per service (extra_cost), key = service_id. Kosong = tanpa markup.
    'service_markup' => [],

    // Tampilkan service premium di checkout domestik. Default true = samakan
    // dengan plugin WP (tidak menyaring premium). Set false untuk sembunyikan.
    'allow_premium' => env('SHIPPING_ALLOW_PREMIUM', true),

    // Daftar provinsi tujuan — penamaan KANONIK sesuai API Agenwebsite
    // (legacy 34 provinsi, mis. "Nanggroe Aceh Darussalam", "Daerah Istimewa
    // Yogyakarta"). Dipakai dropdown provinsi di checkout untuk memfilter
    // autocomplete kota/kecamatan. Provinsi yang dikirim ke API tetap diambil
    // dari hasil pilih autocomplete (kanonik), dropdown hanya filter + struktur.
    'destination_provinces' => [
        'Nanggroe Aceh Darussalam',
        'Sumatera Utara',
        'Sumatera Barat',
        'Riau',
        'Kepulauan Riau',
        'Jambi',
        'Sumatera Selatan',
        'Bangka Belitung',
        'Bengkulu',
        'Lampung',
        'DKI Jakarta',
        'Banten',
        'Jawa Barat',
        'Jawa Tengah',
        'Daerah Istimewa Yogyakarta',
        'Jawa Timur',
        'Bali',
        'Nusa Tenggara Barat',
        'Nusa Tenggara Timur',
        'Kalimantan Barat',
        'Kalimantan Tengah',
        'Kalimantan Selatan',
        'Kalimantan Timur',
        'Kalimantan Utara',
        'Sulawesi Utara',
        'Gorontalo',
        'Sulawesi Tengah',
        'Sulawesi Barat',
        'Sulawesi Selatan',
        'Sulawesi Tenggara',
        'Maluku',
        'Maluku Utara',
        'Papua',
        'Papua Barat',
    ],
];
