<nav class="flex-1 px-4 py-5 space-y-6 overflow-y-auto">
    <div class="space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu</p>
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
        <x-nav-link :href="route('referrals.index')" :active="request()->routeIs('referrals.*')" icon="link">Link Referral</x-nav-link>
        <x-nav-link :href="route('commissions.index')" :active="request()->routeIs('commissions.*')" icon="coins">Komisi</x-nav-link>
        <x-nav-link :href="route('withdrawals.index')" :active="request()->routeIs('withdrawals.*')" icon="wallet">Penarikan</x-nav-link>
        <x-nav-link :href="route('materials.index')" :active="request()->routeIs('materials.*')" icon="folder-open">Materi Marketing</x-nav-link>
    </div>

    <div class="space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Gamifikasi</p>
        <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')" icon="trophy">Event</x-nav-link>
        <x-nav-link :href="route('leaderboard')" :active="request()->routeIs('leaderboard')" icon="bar-chart-3">Leaderboard</x-nav-link>
        <x-nav-link :href="route('rewards.index')" :active="request()->routeIs('rewards.*')" icon="gift">Reward Saya</x-nav-link>
    </div>

    <div class="space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Akun</p>
        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">Profil</x-nav-link>
        <x-nav-link :href="route('payout-accounts.index')" :active="request()->routeIs('payout-accounts.*')" icon="landmark">Rekening Tujuan</x-nav-link>
        <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')" icon="bell">Notifikasi</x-nav-link>
    </div>
</nav>

<div class="px-4 py-4 border-t border-slate-100">
    <div class="flex items-center gap-3 px-3 py-2.5 bg-primary-50/60 rounded-xl">
        <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-600 text-white text-xs font-bold shrink-0">
            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </span>
        <div class="min-w-0">
            <p class="text-xs text-slate-500">Tipe Affiliator</p>
            <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->type->name ?? '—' }}</p>
        </div>
    </div>
</div>
