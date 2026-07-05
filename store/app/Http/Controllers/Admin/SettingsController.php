<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use App\Services\Shipping\AgenwebsiteClient;
use App\Services\XSenderService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Tab yang diizinkan.
     */
    protected const ALLOWED_TABS = ['store-info', 'bank-accounts', 'shipping', 'whatsapp', 'footer'];

    /**
     * Static fallback bila API courier master tidak bisa di-fetch
     * (license invalid / network down / cache kosong).
     */
    protected const AVAILABLE_COURIERS = [
        'jne', 'jnt', 'sicepat', 'anteraja', 'pos', 'tiki', 'spx', 'lion', 'paxel',
    ];

    /**
     * Build live courier id ⇒ title map dari API (cached 24h). Fallback ke
     * static list bila API gagal — admin tetap bisa pilih kurir, sekadar
     * kehilangan label cantik dari API.
     *
     * @return array<string, string>
     */
    protected function liveCouriersMap(): array
    {
        try {
            $client = AgenwebsiteClient::fromConfig();
            $couriers = $client->couriers();
        } catch (\Throwable) {
            $couriers = [];
        }

        $map = [];
        foreach ($couriers as $c) {
            $id = $c['id'] ?? null;
            if (! $id || isset($map[$id])) {
                continue;
            }
            $map[$id] = (string) ($c['title'] ?? strtoupper($id));
        }

        if ($map === []) {
            foreach (self::AVAILABLE_COURIERS as $id) {
                $map[$id] = strtoupper($id);
            }
        }

        return $map;
    }

    /**
     * Halaman tunggal Settings dengan tab:
     * - store-info: nama/alamat/kota/telp/email/jam operasional
     * - bank-accounts: list rekening (CRUD inline)
     * - shipping: origin, couriers, markup, ongkir enable/disable
     *
     * Tab dipilih via query string ?tab=store-info|bank-accounts|shipping.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'store-info');
        if (! in_array($tab, self::ALLOWED_TABS, true)) {
            $tab = 'store-info';
        }

        $viewData = [
            'tab' => $tab,
            'storeInfo' => Settings::getStoreInfo(),
            'bankAccounts' => Settings::getBankAccounts(),
        ];

        if ($tab === 'shipping') {
            $viewData['shippingData'] = $this->getShippingData();
            $viewData['availableCouriers'] = $this->liveCouriersMap();
        }

        if ($tab === 'whatsapp') {
            $viewData['whatsappData'] = $this->getWhatsappData();
        }

        if ($tab === 'footer') {
            $viewData['footerData'] = Settings::getFooter();
        }

        return view('admin.settings.index', $viewData);
    }

    /**
     * Kumpulkan data shipping dari DB + fallback config.
     *
     * @return array<string, mixed>
     */
    protected function getShippingData(): array
    {
        $serviceMarkupRaw = Settings::get('shipping.service_markup', config('shipping.service_markup', []));

        $serviceMarkupLines = '';
        if (is_array($serviceMarkupRaw) && $serviceMarkupRaw !== []) {
            $lines = [];
            foreach ($serviceMarkupRaw as $service => $markup) {
                $lines[] = $service.':'.$markup;
            }
            $serviceMarkupLines = implode("\n", $lines);
        }

        $licenseStatus = null;
        try {
            $client = AgenwebsiteClient::fromConfig();
            $result = $client->activateLicense();
            $licenseStatus = $result;
        } catch (\Throwable $e) {
            $licenseStatus = ['status' => 'error', 'message' => 'Tidak dapat terhubung dengan server lisensi.', 'result' => null];
        }

        return [
            'origin' => Settings::get('shipping.origin', config('shipping.origin')),
            'origin_zipcode' => Settings::get('shipping.origin_zipcode', config('shipping.origin_zipcode')),
            'couriers' => Settings::get('shipping.couriers', config('shipping.couriers')),
            'service_markup_raw' => $serviceMarkupLines,
            'shipping_enabled' => Settings::get('shipping.shipping_enabled', true),
            'default_weight_kg' => Settings::get('shipping.default_weight_kg', config('shipping.default_weight_kg')),
            'license' => Settings::get('shipping.license', config('shipping.license')),
            'site_url' => Settings::get('shipping.site_url', config('shipping.site_url')),
            'license_status' => $licenseStatus,
        ];
    }

    /**
     * Update store info (tab 1). Single form, semua key di-set.
     */
    public function updateStoreInfo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'operating_hours' => ['nullable', 'string', 'max:200'],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        foreach ($data as $key => $value) {
            Settings::set('store.'.$key, $value ?? '', 'string');
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'store-info'])
            ->with('status', 'Store info berhasil diperbarui.');
    }

    /**
     * Replace seluruh list bank_accounts (full upsert pattern, simpler than
     * partial update — toh form admin selalu submit list lengkap).
     *
     * Format input: bank_accounts[] dengan sub-fields bank/number/holder/logo_color.
     */
    public function updateBankAccounts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank' => ['nullable', 'string', 'max:60'],
            'bank_accounts.*.number' => ['nullable', 'string', 'max:40'],
            'bank_accounts.*.holder' => ['nullable', 'string', 'max:120'],
            'bank_accounts.*.logo_color' => ['nullable', 'string', 'max:30'],
            'bank_accounts.*.primary' => ['nullable'],
        ]);

        // Validate inline: kalau partial-filled (bank tanpa number, dst), reject.
        foreach ($data['bank_accounts'] ?? [] as $idx => $acc) {
            $hasBank = ! empty($acc['bank']);
            $hasNumber = ! empty($acc['number']);
            if ($hasBank xor $hasNumber) {
                return back()->withInput()->withErrors([
                    "bank_accounts.{$idx}.bank" => 'Bank dan nomor rekening harus diisi keduanya, atau kosongkan keduanya.',
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $rawAccounts */
        $rawAccounts = $data['bank_accounts'] ?? [];

        $accounts = collect($rawAccounts)
            ->filter(fn ($acc) => ! empty($acc['bank']) && ! empty($acc['number']))
            ->map(fn ($acc) => [
                'bank' => $acc['bank'],
                'number' => $acc['number'],
                'holder' => $acc['holder'] ?? '',
                'logo_color' => $acc['logo_color'] ?? 'slate',
                'primary' => ! empty($acc['primary']),
            ])
            ->values()
            ->all();

        Settings::set('bank_accounts', $accounts, 'array');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'bank-accounts'])
            ->with('status', count($accounts).' rekening tersimpan.');
    }

    /**
     * Update shipping settings (tab shipping).
     *
     * Validasi + persist ke DB via Settings service.
     * License + site_url (domain) bisa diisi dari form — fallback ke .env bila
     * dikosongkan. Domain agenwebsite license-bound, jadi keduanya disimpan bareng.
     */
    public function updateShipping(Request $request): RedirectResponse
    {
        $allowedCourierIds = array_keys($this->liveCouriersMap());

        $data = $request->validate([
            'origin' => ['required', 'string', 'max:100'],
            'origin_zipcode' => ['required', 'string', 'max:10'],
            'couriers' => ['nullable', 'array'],
            'couriers.*' => ['string', 'in:'.implode(',', $allowedCourierIds)],
            'service_markup' => ['nullable', 'string'],
            'shipping_enabled' => ['nullable'],
            'default_weight_kg' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'license' => ['nullable', 'string', 'max:255'],
            'site_url' => ['nullable', 'url', 'max:255'],
        ], [
            'origin.required' => 'Kota asal wajib diisi.',
            'origin_zipcode.required' => 'Kode pos asal wajib diisi.',
            'default_weight_kg.required' => 'Berat default wajib diisi.',
            'default_weight_kg.min' => 'Berat default minimal 0.1 kg.',
            'default_weight_kg.max' => 'Berat default maksimal 100 kg.',
            'couriers.*.in' => 'Kurir tidak valid.',
            'site_url.url' => 'Domain harus berupa URL valid (mis. https://masfirmanpratama.com).',
        ]);

        // Simpan origin
        Settings::set('shipping.origin', $data['origin'], 'string');
        Settings::set('shipping.origin_zipcode', $data['origin_zipcode'], 'string');

        // Simpan license + domain (kosong = fallback ke .env). Bila berubah,
        // flush cache master (couriers/services) yang license/domain-bound.
        $oldLicense = Settings::get('shipping.license', config('shipping.license'));
        $oldSiteUrl = Settings::get('shipping.site_url', config('shipping.site_url'));
        Settings::set('shipping.license', $data['license'] ?? '', 'string');
        Settings::set('shipping.site_url', $data['site_url'] ?? '', 'string');

        if (($data['license'] ?? '') !== $oldLicense || ($data['site_url'] ?? '') !== $oldSiteUrl) {
            Cache::forget('shipping.couriers');
            foreach (['domestic', 'instant', 'international'] as $cat) {
                Cache::forget('shipping.services.'.$cat);
            }
        }

        // Simpan daftar kurir aktif
        Settings::set('shipping.couriers', $data['couriers'] ?? [], 'array');

        // Parse service_markup dari textarea (satu baris = service:markup)
        $markup = [];
        if (! empty($data['service_markup'])) {
            $lines = explode("\n", $data['service_markup']);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $service = trim($parts[0]);
                    $value = (int) trim($parts[1]);
                    if ($service !== '' && $value >= 0) {
                        $markup[$service] = $value;
                    }
                }
            }
        }
        Settings::set('shipping.service_markup', $markup, 'json');

        // Simpan shipping_enabled (toggle)
        $enabled = ! empty($data['shipping_enabled']);
        Settings::set('shipping.shipping_enabled', $enabled, 'bool');

        // Simpan default_weight_kg
        Settings::set('shipping.default_weight_kg', (float) $data['default_weight_kg'], 'string');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'shipping'])
            ->with('status', 'Pengaturan pengiriman berhasil diperbarui.');
    }

    /**
     * Test koneksi lisensi Agenwebsite — pakai license + site_url dari form
     * (bisa dites SEBELUM disimpan), bukan dari DB/config.
     */
    public function testShipping(Request $request): JsonResponse
    {
        $license = trim((string) $request->input('license'));
        $siteUrl = trim((string) $request->input('site_url'));

        // Kosong = pakai nilai .env/config saat ini (test config yang sedang aktif).
        $cfg = config('shipping');
        if ($license !== '') {
            $cfg['license'] = $license;
        }
        if ($siteUrl !== '') {
            $cfg['site_url'] = $siteUrl;
            $cfg['user_agent'] = 'WordPress/'.$cfg['wordpress_version'].'; '.$siteUrl;
        }

        if (($cfg['license'] ?? '') === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Kode lisensi belum diisi.',
            ]);
        }

        try {
            $client = new AgenwebsiteClient($cfg);
            $result = $client->activateLicense();

            if (($result['status'] ?? '') === 'success') {
                $data = is_array($result['result'] ?? null) ? $result['result'] : [];
                $detail = [];
                if (! empty($data['account_email'])) {
                    $detail[] = 'Akun: '.$data['account_email'];
                }
                if (! empty($data['type'])) {
                    $detail[] = 'Tipe: '.$data['type'];
                }
                if (! empty($data['expire_date'])) {
                    $detail[] = 'Berlaku hingga '.$data['expire_date'];
                }
                if (! empty($data['shipping_quota'])) {
                    $detail[] = 'Kuota: '.$data['shipping_quota'];
                }

                return response()->json([
                    'ok' => true,
                    'message' => $detail === [] ? 'Lisensi aktif & terhubung.' : implode(' · ', $detail),
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Lisensi tidak dapat diverifikasi.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Data untuk tab WhatsApp (XSender gateway).
     */
    protected function getWhatsappData(): array
    {
        $waAdmin = Settings::getWaAdmin();

        return [
            'api_key' => Settings::get('xsender.api_key', config('services.xsender.api_key')),
            'sender' => Settings::get('xsender.sender', config('services.xsender.sender')),
            'endpoint' => Settings::get('xsender.endpoint', config('services.xsender.endpoint', 'https://xsender.id/id/send-message')),
            'admin_number' => $waAdmin['number'] ?? '',
        ];
    }

    /**
     * Update WhatsApp XSender settings (tab whatsapp).
     */
    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'xsender_api_key' => ['required', 'string', 'max:255'],
            'xsender_sender' => ['required', 'string', 'max:30'],
            'xsender_endpoint' => ['nullable', 'url', 'max:255'],
            'wa_admin_number' => ['nullable', 'string', 'max:30'],
        ], [
            'xsender_api_key.required' => 'API Key XSender wajib diisi.',
            'xsender_sender.required' => 'Nomor WhatsApp XSender wajib diisi.',
            'xsender_endpoint.url' => 'Format URL endpoint tidak valid.',
        ]);

        Settings::set('xsender.api_key', $data['xsender_api_key'], 'string');
        Settings::set('xsender.sender', $data['xsender_sender'], 'string');

        if (! empty($data['xsender_endpoint'])) {
            Settings::set('xsender.endpoint', $data['xsender_endpoint'], 'string');
        }

        // Nomor WA admin = penerima alert (mis. "bukti bayar baru"). Kalau kosong,
        // fallback ke config placeholder yang TIDAK terdaftar di WA → alert gagal.
        if (! empty($data['wa_admin_number'])) {
            $current = Settings::getWaAdmin();
            Settings::set('wa_admin', [
                'number' => XSenderService::normalizePhone($data['wa_admin_number']),
                'label' => $current['label'] ?? 'Admin',
            ], 'array');
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'whatsapp'])
            ->with('status', 'Pengaturan WhatsApp (XSender) berhasil diperbarui.');
    }

    /**
     * Update konten footer storefront (tab footer). Satu form menyimpan semua:
     * brand/tagline/kontak/copyright (scalar) + socials/links/legal (list).
     * Href boleh relatif (/produk?...) atau absolut, jadi divalidasi sebagai string.
     */
    public function updateFooter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand_text' => ['nullable', 'string', 'max:60'],
            'brand_accent' => ['nullable', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:300'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'copyright' => ['nullable', 'string', 'max:200'],
            'socials' => ['nullable', 'array'],
            // Icon dari picker terkurasi (nama Lucide valid) — tolak nama bebas
            // supaya icon dijamin ter-render di footer storefront.
            'socials.*.icon' => ['nullable', 'string', 'max:40', 'in:'.implode(',', array_keys(Settings::FOOTER_SOCIAL_ICONS))],
            'socials.*.href' => ['nullable', 'string', 'max:300'],
            'socials.*.label' => ['nullable', 'string', 'max:60'],
            'links' => ['nullable', 'array'],
            'links.*.group' => ['nullable', 'string', 'max:60'],
            'links.*.label' => ['nullable', 'string', 'max:80'],
            'links.*.href' => ['nullable', 'string', 'max:300'],
            'legal' => ['nullable', 'array'],
            'legal.*.label' => ['nullable', 'string', 'max:80'],
            'legal.*.href' => ['nullable', 'string', 'max:300'],
        ], [
            'email.email' => 'Format email tidak valid.',
            'socials.*.icon.in' => 'Icon media sosial tidak dikenal — pilih dari daftar icon yang tersedia.',
        ]);

        // URL footer tampil di halaman publik → tolak skema eksekusi
        // (javascript:/data:/vbscript:) yang bisa jadi stored-XSS. Path relatif
        // & http(s)/mailto/tel tetap boleh.
        foreach (['socials', 'links', 'legal'] as $section) {
            foreach ($data[$section] ?? [] as $idx => $row) {
                if (! empty($row['href']) && ! HtmlSanitizer::isSafeUrl($row['href'])) {
                    return back()->withInput()->withErrors([
                        'footer_url' => 'URL "'.$row['href'].'" memakai skema berbahaya (javascript:/data:/vbscript:) dan tidak disimpan. Pakai http(s), mailto:, tel:, atau path relatif (mis. /blog).',
                    ]);
                }
            }
        }

        foreach (['brand_text', 'brand_accent', 'tagline', 'address', 'phone', 'email', 'copyright'] as $key) {
            Settings::set('footer.'.$key, $data[$key] ?? '', 'string');
        }

        // Socials: butuh icon + href; label default dari nama icon.
        $socials = collect($data['socials'] ?? [])
            ->filter(fn ($s) => ! empty($s['icon']) && ! empty($s['href']))
            ->map(fn ($s) => [
                'icon' => $s['icon'],
                'href' => $s['href'],
                'label' => $s['label'] ?: ucfirst($s['icon']),
            ])
            ->values()
            ->all();
        Settings::set('footer.socials', $socials, 'array');

        // Link kolom: butuh group + label + href.
        $links = collect($data['links'] ?? [])
            ->filter(fn ($l) => ! empty($l['group']) && ! empty($l['label']) && ! empty($l['href']))
            ->map(fn ($l) => ['group' => $l['group'], 'label' => $l['label'], 'href' => $l['href']])
            ->values()
            ->all();
        Settings::set('footer.links', $links, 'array');

        // Legal: butuh label + href.
        $legal = collect($data['legal'] ?? [])
            ->filter(fn ($l) => ! empty($l['label']) && ! empty($l['href']))
            ->map(fn ($l) => ['label' => $l['label'], 'href' => $l['href']])
            ->values()
            ->all();
        Settings::set('footer.legal', $legal, 'array');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'footer'])
            ->with('status', 'Footer berhasil diperbarui.');
    }

    /**
     * Test koneksi XSender — kirim pesan test ke nomor sender sendiri.
     */
    public function testWhatsapp(Request $request): JsonResponse
    {
        $apiKey = $request->input('api_key');
        $sender = $request->input('sender');
        $endpoint = $request->input('endpoint') ?: 'https://xsender.id/id/send-message';

        if (empty($apiKey) || empty($sender)) {
            return response()->json([
                'ok' => false,
                'message' => 'API Key dan Nomor Sender wajib diisi.',
            ]);
        }

        $sender = XSenderService::normalizePhone($sender);

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($endpoint, [
                    'api_key' => $apiKey,
                    'sender' => $sender,
                    'number' => $sender, // kirim ke diri sendiri
                    'message' => '✅ Test koneksi XSender berhasil! ('.now()->format('d/m/Y H:i:s').')',
                ]);

            if ($response->successful()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Pesan test berhasil dikirim ke '.$sender.'.',
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'API response: HTTP '.$response->status().' — '.mb_substr($response->body(), 0, 200),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ]);
        }
    }
}
