<nav class="flex-1 px-4 py-5 space-y-6 overflow-y-auto">
    <div class="space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Operasional</p>
        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
        <x-nav-link :href="route('admin.affiliators.index')" :active="request()->routeIs('admin.affiliators.*')" icon="users">Affiliator</x-nav-link>
        <x-nav-link :href="route('admin.commissions.index')" :active="request()->routeIs('admin.commissions.index')" icon="coins">Komisi</x-nav-link>
        <x-nav-link :href="route('admin.withdrawals.index')" :active="request()->routeIs('admin.withdrawals.*')" icon="wallet">Penarikan</x-nav-link>
    </div>

    <div class="space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Konten</p>
        <x-nav-link :href="route('admin.materials.index')" :active="request()->routeIs('admin.materials.*')" icon="folder">Materi</x-nav-link>
        <x-nav-link :href="route('admin.events.index')" :active="request()->routeIs('admin.events.*')" icon="trophy">Event &amp; Gamifikasi</x-nav-link>
    </div>

    <div class="space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Sistem</p>
        <x-nav-link :href="route('admin.commissions.settings')" :active="request()->routeIs('admin.commissions.settings')" icon="settings">Pengaturan Komisi</x-nav-link>
    </div>
</nav>
