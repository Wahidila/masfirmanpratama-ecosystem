@php
    $title ??= 'Lupa Password · MasFirmanPratama.com';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('admin/admin.css') }}">
    @vite(['resources/js/admin.js'])
    <style>[x-cloak] { display: none !important; }</style>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark', 'bg-gray-900');
            }
        })();
    </script>
</head>

<body class="relative bg-white dark:bg-gray-900">
    <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
        <div class="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
            <!-- Form -->
            <div class="flex w-full flex-1 flex-col lg:w-1/2">
                <div class="mx-auto w-full max-w-md pt-10">
                    <a href="{{ route('admin.login') }}"
                        class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="stroke-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Kembali ke login
                    </a>
                </div>
                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                    <div>
                        <div class="mb-5 sm:mb-8">
                            <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">
                                Lupa Password
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Masukkan email admin Anda. Kami kirim tautan untuk mengatur ulang password.
                            </p>
                        </div>
                        <div>
                            @if (session('status'))
                                <div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-900/20 dark:text-success-400">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.email') }}">
                                @csrf
                                <div class="space-y-5">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Email<span class="text-error-500">*</span>
                                        </label>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@masfirmanpratama.com"
                                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 @error('email') border-error-300 ring-1 ring-error-300 @enderror" />
                                        @error('email')
                                            <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <button type="submit"
                                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                                            Kirim tautan reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel (brand) -->
            <div class="bg-brand-950 relative hidden h-full w-full items-center lg:grid lg:w-1/2 dark:bg-white/5">
                <div class="z-1 flex items-center justify-center">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-white/5 blur-3xl"></div>
                        <div class="absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-white/5 blur-3xl"></div>
                    </div>
                    <div class="flex max-w-xs flex-col items-center">
                        <a href="{{ url('/') }}" class="mb-4 block">
                            <img src="{{ asset('assets/images/logo.webp') }}" alt="MasFirmanPratama.com" class="max-w-[160px]" />
                        </a>
                        <p class="text-center text-gray-400 dark:text-white/60">
                            Panel Admin — kelola produk, pesanan, pembayaran, dan pengiriman MasFirmanPratama.com.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
