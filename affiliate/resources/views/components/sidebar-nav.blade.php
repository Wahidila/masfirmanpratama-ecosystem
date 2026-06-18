<nav class="flex-1 px-4 py-4 space-y-1">
    <a href="{{ route('dashboard') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        Dashboard
    </a>
    <a href="{{ route('referrals.index') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('referrals.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="link" class="w-5 h-5"></i>
        Link Referral
    </a>
    <a href="{{ route('commissions.index') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('commissions.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="coins" class="w-5 h-5"></i>
        Komisi
    </a>
    <a href="{{ route('withdrawals.index') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('withdrawals.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="wallet" class="w-5 h-5"></i>
        Penarikan
    </a>
    <a href="{{ route('materials.index') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('materials.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="folder-open" class="w-5 h-5"></i>
        Materi Marketing
    </a>
    <a href="{{ route('events.index') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('events.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="trophy" class="w-5 h-5"></i>
        Event & Gamifikasi
    </a>
    <a href="{{ route('leaderboard') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('leaderboard') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
        Leaderboard
    </a>
    <a href="{{ route('rewards.index') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('rewards.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <i data-lucide="gift" class="w-5 h-5"></i>
        Reward Saya
    </a>

    <div class="pt-4 mt-4 border-t border-slate-100">
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
            <i data-lucide="user" class="w-5 h-5"></i>
            Profil
        </a>
        <a href="{{ route('notifications.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('notifications.*') ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
            <i data-lucide="bell" class="w-5 h-5"></i>
            Notifikasi
        </a>
    </div>
</nav>

<div class="px-4 py-4 border-t border-slate-100">
    <div class="px-3 py-2 bg-slate-50 rounded-xl">
        <p class="text-xs text-slate-500">Tipe Affiliator</p>
        <p class="text-sm font-medium text-slate-700">{{ auth()->user()->type->name ?? '-' }}</p>
    </div>
</div>
