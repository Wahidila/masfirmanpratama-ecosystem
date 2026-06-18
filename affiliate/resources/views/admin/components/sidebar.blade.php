<nav class="flex-1 px-3 py-4 space-y-1">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="{{ route('admin.affiliators.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.affiliators.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="users" class="w-4 h-4"></i> Affiliator
    </a>
    <a href="{{ route('admin.commissions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.commissions.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="coins" class="w-4 h-4"></i> Komisi
    </a>
    <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.withdrawals.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="wallet" class="w-4 h-4"></i> Penarikan
    </a>
    <a href="{{ route('admin.materials.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.materials.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="folder" class="w-4 h-4"></i> Materi
    </a>
    <a href="{{ route('admin.events.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.events.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="trophy" class="w-4 h-4"></i> Event & Gamifikasi
    </a>
    <a href="{{ route('admin.commissions.settings') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.commissions.settings') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
        <i data-lucide="settings" class="w-4 h-4"></i> Pengaturan
    </a>
</nav>
