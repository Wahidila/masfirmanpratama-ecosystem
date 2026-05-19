@php
    $title ??= 'Admin · MasFirmanPratama.com';
    $active ??= null;
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $title)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-full bg-slate-50 antialiased text-slate-800">
    <div class="min-h-screen flex">

        <x-admin.sidebar :active="$active" />

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0">
            <x-admin.navbar />

            {{-- Mobile / tablet header (replace navbar di < lg) --}}
            <header class="lg:hidden flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-semibold text-slate-900">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-secondary-500 text-white text-sm">F</span>
                    Admin
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium text-slate-700">
                        <x-admin.icon name="logout" class="h-3.5 w-3.5" />
                        Logout
                    </button>
                </form>
            </header>

            <main class="flex-1 px-4 py-8 sm:px-8 lg:px-10">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
