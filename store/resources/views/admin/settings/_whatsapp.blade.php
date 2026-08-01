@php
    // Tema admin (bukan storefront) — hanya utility yang ter-compile di admin.css.
    $inputClass = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
@endphp

<x-admin.card>
    <form method="POST" action="{{ route('admin.settings.whatsapp.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-admin.form-group label="API Key XSender" name="xsender_api_key" required
            hint="Dapatkan dari dashboard XSender (xsender.id).">
            <input type="password" id="xsender_api_key" name="xsender_api_key" autocomplete="off"
                value="{{ old('xsender_api_key', $whatsappData['api_key'] ?? '') }}"
                placeholder="Masukkan API Key"
                class="{{ $inputClass }}">
        </x-admin.form-group>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-admin.form-group label="Nomor WhatsApp Sender" name="xsender_sender" required
                hint="Nomor WA yang terhubung di XSender. Format: 628xxxxxxxxxx.">
                <input type="text" id="xsender_sender" name="xsender_sender" inputmode="numeric"
                    value="{{ old('xsender_sender', $whatsappData['sender'] ?? '') }}"
                    placeholder="628xxxxxxxxxx"
                    class="{{ $inputClass }}">
            </x-admin.form-group>

            <x-admin.form-group label="Nomor WhatsApp Admin" name="wa_admin_number"
                hint="Penerima alert (mis. bukti bayar baru). WAJIB nomor WA aktif — kalau kosong, alert admin gagal terkirim.">
                <input type="text" id="wa_admin_number" name="wa_admin_number" inputmode="numeric"
                    value="{{ old('wa_admin_number', $whatsappData['admin_number'] ?? '') }}"
                    placeholder="628xxxxxxxxxx"
                    class="{{ $inputClass }}">
            </x-admin.form-group>
        </div>

        <x-admin.form-group label="Endpoint URL" name="xsender_endpoint"
            hint="Default: https://xsender.id/id/send-message. Ubah jika pakai custom endpoint.">
            <input type="url" id="xsender_endpoint" name="xsender_endpoint"
                value="{{ old('xsender_endpoint', $whatsappData['endpoint'] ?? 'https://xsender.id/id/send-message') }}"
                placeholder="https://xsender.id/id/send-message"
                class="{{ $inputClass }}">
        </x-admin.form-group>

        {{-- Info: cara kerja --}}
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 font-semibold text-gray-800 dark:text-white/90">Cara kerja</p>
            <ol class="list-inside list-decimal space-y-1 text-gray-600 dark:text-gray-400">
                <li>Sistem mengirim pesan via API XSender ke nomor customer/admin.</li>
                <li>Pastikan device WhatsApp terhubung di dashboard XSender.</li>
                <li>Notifikasi dikirim saat: order baru, pembayaran diverifikasi, pesanan dikirim.</li>
            </ol>
        </div>

        {{-- Hasil test koneksi (di-toggle oleh JS) --}}
        <div id="xsender-test-result" class="hidden rounded-lg border p-4 text-sm"></div>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-end dark:border-gray-800">
            <x-admin.button type="button" id="btn-test-xsender" variant="outline">
                <i data-lucide="wifi" class="h-4 w-4"></i>
                Test Koneksi
            </x-admin.button>
            <x-admin.button type="submit">
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan Pengaturan
            </x-admin.button>
        </div>
    </form>

    <script>
        (function () {
            const btn = document.getElementById('btn-test-xsender');
            const resultDiv = document.getElementById('xsender-test-result');
            if (!btn || !resultDiv) return;

            const BASE = 'rounded-lg border p-4 text-sm ';
            const OK = 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400';
            const FAIL = 'border-error-200 bg-error-50 text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400';
            const original = btn.innerHTML;

            btn.addEventListener('click', async function () {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Testing…';
                resultDiv.className = BASE + 'hidden';

                try {
                    const response = await fetch('{{ route('admin.settings.whatsapp.test') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            api_key: document.querySelector('[name="xsender_api_key"]').value,
                            sender: document.querySelector('[name="xsender_sender"]').value,
                            endpoint: document.querySelector('[name="xsender_endpoint"]').value,
                        }),
                    });
                    const data = await response.json();
                    resultDiv.className = BASE + (data.ok ? OK : FAIL);
                    resultDiv.innerHTML = data.ok
                        ? '<p class="font-semibold">✅ Koneksi berhasil</p><p class="mt-1 text-xs">' + (data.message || 'Pesan test terkirim ke nomor sender.') + '</p>'
                        : '<p class="font-semibold">❌ Koneksi gagal</p><p class="mt-1 text-xs">' + (data.message || 'Periksa API Key dan status device.') + '</p>';
                } catch (e) {
                    resultDiv.className = BASE + FAIL;
                    resultDiv.innerHTML = '<p class="font-semibold">❌ Error</p><p class="mt-1 text-xs">' + e.message + '</p>';
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = original;
                    if (window.lucide) lucide.createIcons();
                }
            });
        })();
    </script>
</x-admin.card>
