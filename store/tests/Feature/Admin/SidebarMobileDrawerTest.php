<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mobile admin nav drawer regression guard (M2-hardening C1).
 *
 * Bug case (visual review M2 admin): viewport <lg cuma punya logo + tombol
 * Logout, sidebar di-hide via `hidden lg:flex` tanpa fallback drawer →
 * akses ke Produk / Pesanan / WA Notifikasi / dst hilang di mobile.
 *
 * Strategi (Option A — Alpine inline drawer di layouts/admin.blade.php).
 * Single source of truth nav: config/admin-nav.php (consumed by both
 * desktop sidebar dan mobile drawer via _nav-links partial).
 *
 * Test scope: static-render assertions terhadap markup yang harus ada di
 * SEMUA admin page (mobile header + drawer skeleton). Tidak test interaksi
 * Alpine x-show toggle (perlu E2E browser test untuk itu).
 */
class SidebarMobileDrawerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_admin_layout_has_mobile_drawer_root(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Drawer root: <div x-data="{ open: false }" class="lg:hidden">
        $this->assertMatchesRegularExpression(
            '/x-data="\{\s*open:\s*false\s*\}"[^>]*class="[^"]*lg:hidden/i',
            $body,
            'Mobile drawer root (Alpine x-data toggle, lg:hidden) tidak ditemukan di admin layout.'
        );
    }

    public function test_admin_layout_has_hamburger_button_with_aria(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Hamburger button buka drawer: aria-controls="admin-mobile-drawer"
        $this->assertStringContainsString(
            'aria-controls="admin-mobile-drawer"',
            $body,
            'Hamburger button dengan aria-controls="admin-mobile-drawer" tidak ditemukan.'
        );

        $this->assertStringContainsString(
            'aria-label="Buka menu navigasi"',
            $body,
            'Hamburger button aria-label "Buka menu navigasi" hilang.'
        );
    }

    public function test_admin_layout_has_drawer_panel_with_dialog_role(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Drawer overlay punya role="dialog" + aria-modal + ID matching aria-controls
        $this->assertMatchesRegularExpression(
            '/id="admin-mobile-drawer"[^>]*role="dialog"|role="dialog"[^>]*id="admin-mobile-drawer"/is',
            $body,
            'Drawer overlay #admin-mobile-drawer dengan role="dialog" tidak ditemukan.'
        );

        $this->assertStringContainsString(
            'aria-modal="true"',
            $body,
            'Drawer aria-modal="true" hilang (a11y blocker untuk modal navigation).'
        );
    }

    public function test_mobile_drawer_renders_all_primary_nav_links(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Drawer pakai <nav data-admin-nav="mobile">. Extract slice dari opening
        // tag sampai closing </nav> agar assertion tepat di drawer (bukan
        // desktop sidebar yang punya data-admin-nav="desktop").
        $this->assertMatchesRegularExpression(
            '/<nav[^>]*data-admin-nav="mobile"/i',
            $body,
            'Mobile drawer <nav data-admin-nav="mobile"> tidak ditemukan.'
        );

        if (! preg_match('/<nav[^>]*data-admin-nav="mobile"[^>]*>(.*?)<\/nav>/is', $body, $m)) {
            $this->fail('Tidak bisa extract isi mobile drawer <nav>.');
        }
        $drawerNav = $m[1];

        // Verify semua primary nav link ada di dalam drawer nav block
        foreach (config('admin-nav.primary', []) as $item) {
            $href = route($item['route']);
            $this->assertStringContainsString(
                $href,
                $drawerNav,
                "Mobile drawer hilang link '{$item['label']}' (href {$href})."
            );
        }
    }

    public function test_mobile_drawer_links_close_drawer_on_click(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        if (! preg_match('/<nav[^>]*data-admin-nav="mobile"[^>]*>(.*?)<\/nav>/is', $body, $m)) {
            $this->fail('Tidak bisa extract isi mobile drawer <nav>.');
        }
        $drawerNav = $m[1];

        // Tiap link di drawer harus punya @click="open = false" untuk auto-close
        // (better UX: user ngga perlu klik backdrop dulu).
        $linkCount = preg_match_all('/<a\b[^>]*href=/i', $drawerNav);
        $autoCloseCount = preg_match_all('/@click="open = false"/i', $drawerNav);

        $this->assertSame(
            $linkCount,
            $autoCloseCount,
            "Drawer punya {$linkCount} <a> link tapi cuma {$autoCloseCount} yang punya ".
                '@click="open = false". Semua link harus auto-close drawer on klik.'
        );
    }

    public function test_desktop_sidebar_remains_intact(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Desktop sidebar masih ada (data-admin-nav="desktop" di <nav>) +
        // wrapped dalam <aside class="hidden lg:flex ...">
        $this->assertMatchesRegularExpression(
            '/<nav[^>]*data-admin-nav="desktop"/i',
            $body,
            'Desktop sidebar <nav data-admin-nav="desktop"> hilang setelah drawer added.'
        );

        $this->assertMatchesRegularExpression(
            '/<aside[^>]*class="[^"]*hidden\s+lg:flex/i',
            $body,
            'Desktop sidebar <aside class="hidden lg:flex"> wrapper hilang.'
        );
    }

    public function test_drawer_has_escape_key_close_handler(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $body = $response->getContent();

        // Drawer harus close on Escape — a11y best practice untuk modal
        $this->assertMatchesRegularExpression(
            '/@keydown\.escape\.window="open\s*=\s*false"/i',
            $body,
            'Drawer tidak handle Escape key (a11y: modal harus dismissable via keyboard).'
        );
    }
}
