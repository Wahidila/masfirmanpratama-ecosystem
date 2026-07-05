<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Settings facade-like service. Read-through cache (5 menit) supaya halaman
 * publik (checkout, upload, track) ngga hit DB tiap request.
 *
 * Pakai:
 *   Settings::get('bank_accounts', config('store.bank_accounts', []))
 *   Settings::set('bank_accounts', $array, 'array')
 *   Settings::getStoreInfo()  // shortcut bundling
 *
 * Cache di-flush otomatis tiap kali set() dipanggil. Test environment
 * (SQLite in-memory) bypass cache untuk konsistensi.
 */
class Settings
{
    public const CACHE_TTL = 300; // 5 menit

    public const CACHE_PREFIX = 'settings:';

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (app()->environment('testing')) {
                return Setting::getValue($key, $default);
            }

            return Cache::remember(
                self::CACHE_PREFIX.$key,
                self::CACHE_TTL,
                fn () => Setting::getValue($key, $default)
            );
        } catch (\Throwable) {
            // DB belum ter-migrate (test feature lama tanpa RefreshDatabase, atau
            // first-time install) — return default biar render pages tetap jalan.
            return $default;
        }
    }

    public static function set(string $key, mixed $value, ?string $type = null): Setting
    {
        $row = Setting::setValue($key, $value, $type);

        Cache::forget(self::CACHE_PREFIX.$key);

        return $row;
    }

    public static function forget(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * Bundle setting "store info" dipakai di footer / contact / receipts.
     *
     * @return array<string, mixed>
     */
    public static function getStoreInfo(): array
    {
        return [
            'name' => self::get('store.name', config('app.name', 'MasFirmanPratama')),
            'tagline' => self::get('store.tagline', 'Mind Power & Life Mastery'),
            'address' => self::get('store.address', ''),
            'city' => self::get('store.city', 'Jakarta'),
            'phone' => self::get('store.phone', ''),
            'email' => self::get('store.email', ''),
            'operating_hours' => self::get('store.operating_hours', 'Senin-Jumat 09:00-17:00 WIB'),
        ];
    }

    /**
     * Bank accounts list. Fallback ke config('store.bank_accounts') untuk
     * backward compat selama M2 transition (sampai admin isi semua).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getBankAccounts(): array
    {
        $accounts = self::get('bank_accounts');

        if (is_array($accounts) && count($accounts) > 0) {
            return $accounts;
        }

        // Fallback config (M1 dummy)
        return config('store.bank_accounts', []);
    }

    /**
     * WhatsApp admin contact.
     *
     * @return array{number: string, label: string}
     */
    public static function getWaAdmin(): array
    {
        $wa = self::get('wa_admin');

        if (is_array($wa) && isset($wa['number'])) {
            return $wa;
        }

        return config('store.wa_admin', [
            'number' => '6281234567890',
            'label' => 'Admin',
        ]);
    }

    /**
     * Icon media sosial yang tersedia di picker footer (nama Lucide => label
     * default). Sumber tunggal untuk picker admin + validasi updateFooter —
     * menjamin nama icon selalu valid saat dirender lucide.js di storefront.
     *
     * @var array<string, string>
     */
    public const FOOTER_SOCIAL_ICONS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'twitter' => 'Twitter / X',
        'linkedin' => 'LinkedIn',
        'send' => 'Telegram',
        'message-circle' => 'WhatsApp',
        'music-2' => 'TikTok',
        'at-sign' => 'Threads',
        'github' => 'GitHub',
        'twitch' => 'Twitch',
        'dribbble' => 'Dribbble',
        'globe' => 'Website',
        'mail' => 'Email',
        'phone' => 'Telepon',
        'rss' => 'RSS / Blog',
    ];

    /** @var list<array{icon: string, href: string, label: string}> */
    public const DEFAULT_FOOTER_SOCIALS = [
        ['icon' => 'facebook', 'href' => 'https://facebook.com/wahanasejati', 'label' => 'Facebook'],
        ['icon' => 'youtube', 'href' => 'https://youtube.com/@CahayaKehidupan', 'label' => 'YouTube'],
        ['icon' => 'instagram', 'href' => 'https://instagram.com/firmanpratama_pakarpikiran', 'label' => 'Instagram'],
    ];

    /**
     * Flat link list — footer mengelompokkannya per `group` (kolom sitemap).
     *
     * @var list<array{group: string, label: string, href: string}>
     */
    public const DEFAULT_FOOTER_LINKS = [
        ['group' => 'Layanan', 'label' => 'Kelas Biasa AMC', 'href' => '/produk?kategori=kelas'],
        ['group' => 'Layanan', 'label' => 'Kelas Privat AMC', 'href' => '/produk?kategori=privat'],
        ['group' => 'Layanan', 'label' => 'Kelas Platinum', 'href' => '/produk?kategori=platinum'],
        ['group' => 'Layanan', 'label' => 'Pembelian Karya', 'href' => '/produk?kategori=buku'],
        ['group' => 'Komunitas', 'label' => 'Profil Pribadi', 'href' => '/tentang'],
        ['group' => 'Komunitas', 'label' => 'Testimoni Alumni', 'href' => '/#testimoni'],
        ['group' => 'Komunitas', 'label' => 'Artikel Keajaiban', 'href' => '/blog'],
        ['group' => 'Komunitas', 'label' => 'Afiliasi Program', 'href' => 'https://affiliate.masfirmanpratama.com'],
    ];

    /** @var list<array{label: string, href: string}> */
    public const DEFAULT_FOOTER_LEGAL = [
        ['label' => 'Kebijakan Privasi', 'href' => '/privacy'],
        ['label' => 'Syarat & Ketentuan', 'href' => '/terms'],
    ];

    /**
     * Bundle konten footer storefront (dipakai <x-footer> + tab Settings→Footer).
     * Semua field jatuh ke default hardcode selama admin belum menyimpannya, jadi
     * footer tetap tampil lengkap out-of-the-box.
     *
     * @return array<string, mixed>
     */
    public static function getFooter(): array
    {
        return [
            'brand_text' => self::get('footer.brand_text', 'Firman'),
            'brand_accent' => self::get('footer.brand_accent', 'Pratama'),
            'tagline' => self::get('footer.tagline', 'Pakar Pikiran No.1 Indonesia. Penulis Buku, Konsultan Bisnis & Pencipta Metode AMC.'),
            'address' => self::get('footer.address', 'Wahana Sejati, Jakarta - Surabaya HQ'),
            'phone' => self::get('footer.phone', '081.2306.33.464'),
            'email' => self::get('footer.email', 'admin@masfirmanpratama.com'),
            'copyright' => self::get('footer.copyright', '© {year} Firman Pratama - AMC. All rights reserved.'),
            'socials' => self::arrayOrDefault('footer.socials', self::DEFAULT_FOOTER_SOCIALS),
            'links' => self::arrayOrDefault('footer.links', self::DEFAULT_FOOTER_LINKS),
            'legal' => self::arrayOrDefault('footer.legal', self::DEFAULT_FOOTER_LEGAL),
        ];
    }

    /**
     * Return the stored array setting, or the default when it was NEVER saved.
     * A deliberately-saved empty array ([]) is respected (admin bisa mengosongkan
     * daftar) — hanya key yang belum pernah diisi yang jatuh ke default.
     *
     * @param  list<array<string, mixed>>  $default
     * @return list<array<string, mixed>>
     */
    protected static function arrayOrDefault(string $key, array $default): array
    {
        $value = self::get($key); // null = belum pernah disimpan

        if ($value === null) {
            return $default;
        }

        return is_array($value) ? $value : $default;
    }
}
