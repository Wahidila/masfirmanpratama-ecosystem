@props([
    'active' => null,
])

@php
    $admin = auth('admin')->user();

    // Nav structure: ready (link) + coming-soon (disabled placeholder M2 sprint).
    $primaryNav = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'route' => 'admin.dashboard', 'enabled' => true],
        ['key' => 'products', 'label' => 'Produk', 'icon' => 'package', 'route' => 'admin.products.index', 'enabled' => true],
        ['key' => 'orders', 'label' => 'Pesanan', 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'enabled' => true],
        ['key' => 'installments', 'label' => 'Skema Cicilan', 'icon' => 'layers', 'route' => 'admin.installment-schemes.index', 'enabled' => true],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings.index', 'enabled' => true],
    ];

    $comingSoon = [];

    $iconClass = 'h-4 w-4 shrink-0';
@endphp

<aside {{ $attributes->class(['hidden lg:flex lg:w-64 flex-col border-r border-slate-200 bg-white']) }}>
    <div class="flex h-16 items-center px-6 border-b border-slate-100">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-semibold tracking-tight text-slate-900">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-secondary-500 text-white text-sm">F</span>
            Admin Panel
        </a>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1 text-sm">
        @foreach ($primaryNav as $item)
            @php $isActive = $active === $item['key'] || request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-2.5 rounded-xl px-3 py-2 font-medium transition {{ $isActive ? 'bg-primary-50 text-primary-700' : 'text-slate-700 hover:bg-slate-100' }}">
                <x-admin.icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach

        @if (! empty($comingSoon))
            <p class="px-3 pt-5 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400">Coming soon (M2 sprint)</p>

            @foreach ($comingSoon as $item)
                <span class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-slate-400 cursor-not-allowed select-none">
                    <x-admin.icon :name="$item['icon']" class="h-4 w-4 shrink-0 opacity-60" />
                    {{ $item['label'] }}
                </span>
            @endforeach
        @endif
    </nav>

    <div class="border-t border-slate-100 p-4">
        <div class="text-xs text-slate-500">Login sebagai</div>
        <div class="mt-1 text-sm font-medium text-slate-900 truncate">{{ $admin->name ?? 'Unknown' }}</div>
        <div class="text-xs text-slate-500 truncate">{{ $admin->email ?? '' }}</div>
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 transition">
                <x-admin.icon name="logout" class="h-3.5 w-3.5" />
                Logout
            </button>
        </form>
    </div>
</aside>
