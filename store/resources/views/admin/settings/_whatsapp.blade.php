{{-- Tab: WhatsApp Gateway (XSender) --}}
<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-slate-900">WhatsApp Gateway (XSender)</h2>
        <p class="mt-1 text-sm text-slate-500">
            Konfigurasi integrasi XSender untuk mengirim notifikasi WhatsApp otomatis ke customer dan admin.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('admin.settings.whatsapp.update') }}"
        class="space-y-5"
    >
        @csrf
        @method('PUT')

        {{-- API Key --}}
        <x-admin.form-group label="API Key XSender" hint="Dapatkan dari dashboard XSender (xsender.id).">
            <input
                type="password"
                name="xsender_api_key"
                value="{{ old('xsender_api_key', $whatsappData['api_key'] ?? '') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition"
                placeholder="Masukkan API Key"
                autocomplete="off"
            >
            @error('xsender_api_key')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </x-admin.form-group>

        {{-- Nomor WhatsApp Sender --}}
        <x-admin.form-group label="Nomor WhatsApp Sender" hint="Nomor WA yang terhubung di XSender. Format: 628xxxxxxxxxx.">
            <input
                type="text"
                name="xsender_sender"
                value="{{ old('xsender_sender', $whatsappData['sender'] ?? '') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition"
                placeholder="628xxxxxxxxxx"
                inputmode="numeric"
            >
            @error('xsender_sender')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </x-admin.form-group>

        {{-- Nomor WhatsApp Admin (penerima alert) --}}
        <x-admin.form-group label="Nomor WhatsApp Admin (penerima notifikasi)" hint="Nomor WA admin untuk menerima alert (mis. bukti bayar baru masuk). WAJIB nomor WA aktif — kalau kosong/placeholder, alert admin akan gagal terkirim.">
            <input
                type="text"
                name="wa_admin_number"
                value="{{ old('wa_admin_number', $whatsappData['admin_number'] ?? '') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition"
                placeholder="628xxxxxxxxxx"
                inputmode="numeric"
            >
            @error('wa_admin_number')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </x-admin.form-group>

        {{-- Endpoint (optional override) --}}
        <x-admin.form-group label="Endpoint URL" hint="Default: https://xsender.id/id/send-message. Ubah jika pakai custom endpoint.">
            <input
                type="url"
                name="xsender_endpoint"
                value="{{ old('xsender_endpoint', $whatsappData['endpoint'] ?? 'https://xsender.id/id/send-message') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition"
                placeholder="https://xsender.id/id/send-message"
            >
            @error('xsender_endpoint')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </x-admin.form-group>

        {{-- Info box --}}
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            <p class="font-semibold mb-1">Cara kerja:</p>
            <ol class="list-decimal list-inside space-y-1 text-blue-700">
                <li>Sistem mengirim pesan via API XSender ke nomor customer/admin.</li>
                <li>Pastikan device WhatsApp terhubung di dashboard XSender.</li>
                <li>Notifikasi dikirim saat: order baru, pembayaran diverifikasi, pesanan dikirim.</li>
            </ol>
        </div>

        <div class="flex justify-end gap-3">
            <button
                type="button"
                id="btn-test-xsender"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
            >
                <i data-lucide="wifi" class="h-4 w-4"></i>
                Test Koneksi
            </button>
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-primary-700 transition"
            >
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Test result --}}
    <div id="xsender-test-result" class="hidden rounded-xl border p-4 text-sm"></div>

    <script>
        document.getElementById('btn-test-xsender').addEventListener('click', async function () {
            const btn = this;
            const resultDiv = document.getElementById('xsender-test-result');

            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Testing...';

            resultDiv.classList.add('hidden');

            try {
                const response = await fetch('{{ route("admin.settings.whatsapp.test") }}', {
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

                resultDiv.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-800', 'border-rose-200', 'bg-rose-50', 'text-rose-800');

                if (data.ok) {
                    resultDiv.classList.add('border-green-200', 'bg-green-50', 'text-green-800');
                    resultDiv.innerHTML = '<p class="font-semibold">✅ Koneksi berhasil!</p><p class="mt-1 text-xs">' + (data.message || 'Pesan test terkirim ke nomor sender.') + '</p>';
                } else {
                    resultDiv.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-800');
                    resultDiv.innerHTML = '<p class="font-semibold">❌ Koneksi gagal</p><p class="mt-1 text-xs">' + (data.message || 'Periksa API Key dan status device.') + '</p>';
                }
            } catch (e) {
                resultDiv.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-800', 'border-rose-200', 'bg-rose-50', 'text-rose-800');
                resultDiv.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-800');
                resultDiv.innerHTML = '<p class="font-semibold">❌ Error</p><p class="mt-1 text-xs">' + e.message + '</p>';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="wifi" class="h-4 w-4"></i> Test Koneksi';
                if (window.lucide) lucide.createIcons();
            }
        });
    </script>
</section>
