<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use App\Services\Shipping\AgenwebsiteClient;
use App\Services\XSenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Tab yang diizinkan.
     */
    protected const ALLOWED_TABS = ['store-info', 'bank-accounts', 'shipping', 'whatsapp'];

    /**
     * Daftar kurir yang tersedia.
     */
    protected const AVAILABLE_COURIERS = [
        'jne', 'jnt', 'sicepat', 'anteraja', 'pos', 'tiki', 'spx', 'lion', 'paxel',
    ];

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
            $viewData['availableCouriers'] = self::AVAILABLE_COURIERS;
        }

        if ($tab === 'whatsapp') {
            $viewData['whatsappData'] = $this->getWhatsappData();
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
     * site_url dan license tidak bisa diubah dari form — di-hardcode di .env.
     */
    public function updateShipping(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'origin' => ['required', 'string', 'max:100'],
            'origin_zipcode' => ['required', 'string', 'max:10'],
            'couriers' => ['nullable', 'array'],
            'couriers.*' => ['string', 'in:'.implode(',', self::AVAILABLE_COURIERS)],
            'service_markup' => ['nullable', 'string'],
            'shipping_enabled' => ['nullable'],
            'default_weight_kg' => ['required', 'numeric', 'min:0.1', 'max:100'],
        ], [
            'origin.required' => 'Kota asal wajib diisi.',
            'origin_zipcode.required' => 'Kode pos asal wajib diisi.',
            'default_weight_kg.required' => 'Berat default wajib diisi.',
            'default_weight_kg.min' => 'Berat default minimal 0.1 kg.',
            'default_weight_kg.max' => 'Berat default maksimal 100 kg.',
            'couriers.*.in' => 'Kurir tidak valid.',
        ]);

        // Simpan origin
        Settings::set('shipping.origin', $data['origin'], 'string');
        Settings::set('shipping.origin_zipcode', $data['origin_zipcode'], 'string');

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
     * Data untuk tab WhatsApp (XSender gateway).
     */
    protected function getWhatsappData(): array
    {
        return [
            'api_key' => Settings::get('xsender.api_key', config('services.xsender.api_key')),
            'sender' => Settings::get('xsender.sender', config('services.xsender.sender')),
            'endpoint' => Settings::get('xsender.endpoint', config('services.xsender.endpoint', 'https://xsender.id/id/send-message')),
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

        return redirect()
            ->route('admin.settings.index', ['tab' => 'whatsapp'])
            ->with('status', 'Pengaturan WhatsApp (XSender) berhasil diperbarui.');
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
