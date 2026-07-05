@extends('layouts.app')

@section('body')
<div x-data="{ sidebarOpen: false }" class="min-h-full">
    {{-- Mobile sidebar --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
             class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="sidebarOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="relative flex w-72 max-w-[80%] flex-col bg-white h-full shadow-xl">
            <div class="flex items-center justify-between px-5 h-16 border-b border-slate-100">
                <div class="flex items-center gap-2"><x-brand-mark size="sm" /><span class="text-[10px] font-bold uppercase tracking-wider text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded">Admin</span></div>
                <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            @include('admin.components.sidebar')
        </div>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
        <div class="flex flex-col flex-grow bg-white border-r border-slate-200 overflow-y-auto">
            <div class="flex items-center gap-2 px-6 h-16 border-b border-slate-100">
                <x-brand-mark size="sm" />
                <span class="text-[10px] font-bold uppercase tracking-wider text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded">Admin</span>
            </div>
            @include('admin.components.sidebar')
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64 flex flex-col min-h-screen">
        <header class="sticky top-0 z-30 flex items-center gap-3 h-16 px-4 sm:px-6 lg:px-8 bg-white/80 backdrop-blur-md border-b border-slate-100">
            <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-700"><i data-lucide="menu" class="w-6 h-6"></i></button>

            <div class="ml-auto flex items-center gap-3">
                <span class="hidden sm:flex items-center gap-2 text-sm text-slate-500">
                    <i data-lucide="shield-check" class="w-4 h-4 text-slate-400"></i>
                    {{ session('admin_email') ?? 'Administrator' }}
                </span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 text-sm font-medium text-rose-600 hover:text-rose-700"><i data-lucide="log-out" class="w-4 h-4"></i> Keluar</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @if (session('success'))<x-alert tone="success" dismissible class="mb-6">{{ session('success') }}</x-alert>@endif
            @if (session('error'))<x-alert tone="danger" dismissible class="mb-6">{{ session('error') }}</x-alert>@endif
            @if ($errors->any())
                <x-alert tone="danger" title="Periksa kembali:" class="mb-6">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </x-alert>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@endsection
