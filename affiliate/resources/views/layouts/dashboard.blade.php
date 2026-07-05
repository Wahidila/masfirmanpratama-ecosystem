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
                <x-brand-mark size="sm" />
                <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            @include('components.sidebar-nav')
        </div>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
        <div class="flex flex-col flex-grow bg-white border-r border-slate-200 overflow-y-auto">
            <div class="flex items-center px-6 h-16 border-b border-slate-100">
                <x-brand-mark size="sm" />
            </div>
            @include('components.sidebar-nav')
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64 flex flex-col min-h-screen">
        <header class="sticky top-0 z-30 flex items-center gap-3 h-16 px-4 sm:px-6 lg:px-8 bg-white/80 backdrop-blur-md border-b border-slate-100">
            <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-700"><i data-lucide="menu" class="w-6 h-6"></i></button>

            <div class="ml-auto flex items-center gap-2 sm:gap-4">
                <a href="{{ route('notifications.index') }}" class="relative flex items-center justify-center w-9 h-9 rounded-full text-slate-500 hover:text-primary-600 hover:bg-primary-50 transition" aria-label="Notifikasi">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-900">
                        <span class="flex items-center justify-center w-9 h-9 bg-primary-100 rounded-full text-primary-700 font-semibold text-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden sm:block max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak x-transition
                         class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-slate-100 py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Profil Saya</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50"><i data-lucide="log-out" class="w-4 h-4"></i> Keluar</button>
                        </form>
                    </div>
                </div>
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
