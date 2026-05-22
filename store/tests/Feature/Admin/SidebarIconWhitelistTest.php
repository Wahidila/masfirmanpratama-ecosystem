<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard untuk admin sidebar icon rendering.
 *
 * Bug case (2026-05-22): sidebar nav punya item `wa-notifications` dengan
 * icon='message-square', tapi `<x-admin.icon>` whitelist component lupa
 * include 'message-square' → silent missing icon (empty <svg>) di sidebar.
 *
 * Test ini static-render only (cepat). Untuk visual regression suite full,
 * lihat docs/qc/visual-review-M2-admin.md (task t_bfc4f9c0).
 */
class SidebarIconWhitelistTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_all_sidebar_nav_icons_render_non_empty_svg(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Sidebar component renders <svg>...</svg> per nav item. Whitelist
        // miss → <svg></svg> (no path child). Empty SVG = silent missing icon.
        $emptyCount = preg_match_all(
            '/<svg[^>]*\bclass="[^"]*h-4 w-4 shrink-0[^"]*"[^>]*>\s*<\/svg>/i',
            $body,
            $emptyMatches,
        );

        $this->assertSame(
            0,
            $emptyCount,
            "Sidebar punya {$emptyCount} icon yang render <svg> kosong (whitelist miss). ".
                'Cek `store/resources/views/components/admin/icon.blade.php` whitelist '.
                'vs `components/admin/sidebar.blade.php $primaryNav[*].icon`.'
        );
    }

    /**
     * Static check: parse sidebar nav array dan validate setiap `icon` value
     * ada di whitelist component. Lebih cepat dari render full page, dan
     * memberi error message lebih spesifik.
     */
    public function test_sidebar_nav_icons_are_all_whitelisted(): void
    {
        $sidebarPath = resource_path('views/components/admin/sidebar.blade.php');
        $iconPath = resource_path('views/components/admin/icon.blade.php');

        $sidebar = file_get_contents($sidebarPath);
        $icon = file_get_contents($iconPath);

        // Extract icon names dari sidebar nav array literal.
        preg_match_all("/'icon'\\s*=>\\s*'([^']+)'/", $sidebar, $sidebarMatches);
        $required = array_unique($sidebarMatches[1] ?? []);

        // Extract whitelist keys dari icon.blade.php $paths array.
        preg_match_all("/'([a-z0-9-]+)'\\s*=>\\s*'<(?:path|rect|circle|polyline|polygon|line)/", $icon, $iconMatches);
        $available = $iconMatches[1] ?? [];

        $missing = array_values(array_diff($required, $available));

        $this->assertEmpty(
            $missing,
            'Sidebar request icon yang ngga di whitelist: '.implode(', ', $missing).
                ". Tambahin path SVG-nya di {$iconPath} \$paths array."
        );
    }
}
