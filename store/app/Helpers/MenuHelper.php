<?php

namespace App\Helpers;

class MenuHelper
{
    /**
     * Build menu groups from admin-nav config for TailAdmin sidebar.
     *
     * Returns a flat single-group structure compatible with TailAdmin sidebar template.
     *
     * @return array<int, array{title: string, items: list<array{name: string, icon: string, path: string}>}>
     */
    public static function getMenuGroups(): array
    {
        $primary = collect(config('admin-nav.primary', []))
            ->filter(fn ($item) => $item['enabled'] ?? true)
            ->map(fn ($item) => [
                'name' => $item['label'],
                'icon' => $item['icon'],
                'path' => route($item['route']),
            ])
            ->values()
            ->all();

        return [
            ['title' => 'Menu', 'items' => $primary],
        ];
    }

    /**
     * Check if a given path is the current active route.
     *
     * Uses server-side request matching for reliable initial render.
     * Dashboard gets exact match to avoid always-active on prefix.
     */
    public static function isActive(string $path): bool
    {
        $requestPath = request()->path();
        $urlPath = ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');

        // Exact match first
        if ($requestPath === $urlPath) {
            return true;
        }

        // For dashboard, only exact match (avoid /admin matching everything)
        if ($urlPath === 'admin' || $urlPath === 'admin/dashboard') {
            return false;
        }

        // Prefix match for other routes (e.g. admin/products matches admin/products/create)
        return str_starts_with($requestPath, $urlPath.'/');
    }

    /**
     * Get SVG markup for an icon name via the admin.icon component pattern.
     *
     * Used by TailAdmin sidebar which expects raw SVG output.
     * Maps icon names to Lucide-style SVG paths matching components/admin/icon.blade.php.
     */
    public static function getIconSvg(string $name, string $class = 'size-5'): string
    {
        $paths = [
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
            'graduation-cap' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
            'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
            'shopping-bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
            'message-square' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'layers' => '<path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
            'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
            'bar-chart' => '<line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/>',
        ];

        $svgPath = $paths[$name] ?? '';

        return '<svg xmlns="http://www.w3.org/2000/svg" class="'.$class.'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$svgPath.'</svg>';
    }
}
