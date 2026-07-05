<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ $title ?? 'Affiliate Program' }} — MasFirmanPratama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js" defer></script>

    @stack('styles')
</head>
<body class="h-full bg-slate-50 font-sans antialiased">
    @yield('body')

    <script>
        // Render Lucide icons on load + after Alpine DOM updates
        window.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());
        document.addEventListener('alpine:initialized', () => window.lucide?.createIcons());
    </script>
    @stack('scripts')
</body>
</html>
