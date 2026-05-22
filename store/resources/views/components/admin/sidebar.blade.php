@props([
    'active' => null,
])

@php
    $admin = auth('admin')->user();
@endphp

<aside {{ $attributes->class(['hidden lg:flex lg:w-64 flex-col border-r border-slate-200 bg-white']) }}>
    <div class="flex h-16 items-center px-6 border-b border-slate-100">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-semibold tracking-tight text-slate-900">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-secondary-500 text-white text-sm">F</span>
            Admin Panel
        </a>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1 text-sm" data-admin-nav="desktop">
        @include('components.admin._nav-links', ['active' => $active])
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
