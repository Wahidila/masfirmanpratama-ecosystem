<x-admin.card>
    <form method="POST" action="{{ route('admin.settings.shipping.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-admin.form-group label="Kota asal pengiriman" name="origin" required
                hint="Nama kota tempat pengiriman berasal.">
                <input type="text" id="origin" name="origin" value="{{ old('origin', $shippingData['origin']) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>

            <x-admin.form-group label="Kode pos asal" name="origin_zipcode" required>
                <input type="text" id="origin_zipcode" name="origin_zipcode" value="{{ old('origin_zipcode', $shippingData['origin_zipcode']) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-admin.form-group label="Berat default (kg)" name="default_weight_kg" required
                hint="Berat default untuk produk tanpa berat, minimal 0.1 kg.">
                <input type="number" id="default_weight_kg" name="default_weight_kg" step="0.1" min="0.1" max="100"
                    value="{{ old('default_weight_kg', $shippingData['default_weight_kg']) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>

            <x-admin.form-group label="Aktifkan ongkos kirim">
                <div class="flex items-center gap-3 pt-1">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer dark:text-gray-300">
                        <input type="hidden" name="shipping_enabled" value="0">
                        <input type="checkbox" name="shipping_enabled" value="1" role="switch"
                            @checked(old('shipping_enabled', $shippingData['shipping_enabled']))
                            class="h-5 w-9 rounded-full border-gray-300 bg-gray-200 checked:bg-brand-500 focus:ring-brand-500 focus:ring-offset-0 transition-colors cursor-pointer dark:border-gray-700 dark:bg-gray-800">
                        <span>{{ $shippingData['shipping_enabled'] ? 'Aktif' : 'Nonaktif' }}</span>
                    </label>
                </div>
            </x-admin.form-group>
        </div>

        <x-admin.form-group label="Kurir aktif" hint="Daftar kurir di-fetch dari license Agenwebsite (cached 24 jam). Centang yang ingin tampil saat checkout.">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1">
                @php $activeCouriers = old('couriers', $shippingData['couriers'] ?? []); @endphp
                @foreach ($availableCouriers as $courierId => $courierTitle)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300">
                        <input type="checkbox" name="couriers[]" value="{{ $courierId }}"
                            @checked(in_array($courierId, $activeCouriers))
                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                        {{ $courierTitle }}
                    </label>
                @endforeach
            </div>
        </x-admin.form-group>

        <x-admin.form-group label="Markup biaya per layanan" name="service_markup"
            hint="Satu baris per layanan dengan format: service_id:markup. Contoh: jne_reg:5000. Kosongkan jika tanpa markup.">
            <textarea id="service_markup" name="service_markup" rows="4"
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm font-mono text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                placeholder="jne_reg:5000&#10;jnt_reg:3000&#10;sicepat_reg:2000">{{ old('service_markup', $shippingData['service_markup_raw']) }}</textarea>
        </x-admin.form-group>

        <hr class="border-gray-200 dark:border-gray-800">

        <div>
            <h4 class="text-sm font-semibold text-gray-800 mb-1 dark:text-white/90">Lisensi Agenwebsite</h4>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                License key &amp; domain terdaftar. Kosongkan untuk pakai nilai default dari
                <code class="text-gray-700 bg-gray-100 px-1 rounded dark:text-gray-300 dark:bg-white/[0.03]">.env</code>.
            </p>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-admin.form-group label="License Key (API Key)" name="license"
                    hint="Kode lisensi dari dashboard Agenwebsite.">
                    <input type="text" id="license" name="license" value="{{ old('license', $shippingData['license']) }}"
                        autocomplete="off" spellcheck="false" placeholder="Masukkan license key"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm font-mono text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('license')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </x-admin.form-group>

                <x-admin.form-group label="Domain terdaftar (site URL)" name="site_url"
                    hint="Domain yang terdaftar di lisensi. License domain-bound — harus cocok.">
                    <input type="url" id="site_url" name="site_url" value="{{ old('site_url', $shippingData['site_url']) }}"
                        autocomplete="off" spellcheck="false" placeholder="https://masfirmanpratama.com"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('site_url')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
                </x-admin.form-group>
            </div>

            <div class="mb-4 mt-3 flex items-center gap-3">
                <button type="button" id="btn-test-shipping"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                    <x-admin.icon name="info" class="h-4 w-4" />
                    Test koneksi lisensi
                </button>
                <span id="shipping-test-result" class="hidden text-sm"></span>
            </div>

            <h4 class="text-sm font-semibold text-gray-800 mb-3 dark:text-white/90">Status Lisensi Saat Ini</h4>

            @php $lic = $shippingData['license_status']; @endphp
            @if ($lic && ($lic['status'] ?? '') === 'success')
                <div class="flex items-start gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-400">
                    <x-admin.icon name="check" class="h-4 w-4 mt-0.5 shrink-0 text-success-600 dark:text-success-500" />
                    <div>
                        <p class="font-medium">Terhubung</p>
                        @if (!empty($lic['result']['expire_date']))
                            <p class="mt-0.5 text-xs text-success-600 dark:text-success-400">Lisensi berlaku hingga {{ $lic['result']['expire_date'] }}</p>
                        @endif
                    </div>
                </div>
            @elseif ($lic && ($lic['status'] ?? '') === 'error')
                <div class="flex items-start gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-800 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400">
                    <x-admin.icon name="alert-triangle" class="h-4 w-4 mt-0.5 shrink-0 text-error-600 dark:text-error-500" />
                    <div>
                        <p class="font-medium">Lisensi tidak terhubung</p>
                        @if (!empty($lic['message']))
                            <p class="mt-0.5 text-xs text-error-600 dark:text-error-400">{{ $lic['message'] }}</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                    <x-admin.icon name="info" class="h-4 w-4 mt-0.5 shrink-0" />
                    <div>
                        <p class="font-medium">Memeriksa lisensi...</p>
                    </div>
                </div>
            @endif

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Status di atas dihitung dari nilai yang sedang aktif (DB bila diisi, atau
                <code class="text-gray-700 bg-gray-100 px-1 rounded dark:text-gray-300 dark:bg-white/[0.03]">.env</code>
                sebagai fallback). Klik <strong>Test koneksi lisensi</strong> untuk memverifikasi nilai pada form sebelum disimpan.
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
            <x-admin.button type="submit">
                Simpan pengaturan pengiriman
            </x-admin.button>
        </div>
    </form>

    <script>
        document.getElementById('btn-test-shipping')?.addEventListener('click', async function () {
            const btn = this;
            const result = document.getElementById('shipping-test-result');
            const original = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Menguji...';
            result.classList.add('hidden');

            try {
                const response = await fetch('{{ route('admin.settings.shipping.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        license: document.querySelector('[name="license"]').value,
                        site_url: document.querySelector('[name="site_url"]').value,
                    }),
                });
                const data = await response.json();

                result.classList.remove('hidden', 'text-success-600', 'text-error-600');
                result.classList.add(data.ok ? 'text-success-600' : 'text-error-600');
                result.textContent = (data.ok ? '✅ ' : '❌ ') + (data.message || '');
            } catch (e) {
                result.classList.remove('hidden', 'text-success-600');
                result.classList.add('text-error-600');
                result.textContent = '❌ ' + e.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });
    </script>
</x-admin.card>
