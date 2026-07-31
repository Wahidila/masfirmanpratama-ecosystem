<?php

namespace Tests\Feature\Admin;

use App\Helpers\MenuHelper;
use Tests\TestCase;

/**
 * Sidebar dikelompokkan per fungsi (config admin-nav.groups → MenuHelper).
 * Tiap grup punya judul (header <h2> di sidebar) + item yang merujuk `primary`.
 */
class SidebarMenuGroupsTest extends TestCase
{
    public function test_menu_is_split_into_four_titled_groups(): void
    {
        $this->assertSame(
            ['Utama', 'Katalog', 'Konten & Promosi', 'Sistem'],
            array_column(MenuHelper::getMenuGroups(), 'title'),
        );
    }

    public function test_group_items_are_in_configured_order(): void
    {
        $groups = collect(MenuHelper::getMenuGroups())->keyBy('title');

        $this->assertSame(['Dashboard', 'Pesanan', 'Laporan'], array_column($groups['Utama']['items'], 'name'));
        $this->assertSame(['Produk', 'Kelas', 'Peserta Kursus', 'Skema Cicilan'], array_column($groups['Katalog']['items'], 'name'));
        $this->assertSame(['Blog', 'Testimoni Video', 'Banner Promo'], array_column($groups['Konten & Promosi']['items'], 'name'));
        $this->assertSame(['WA Notifikasi', 'Settings'], array_column($groups['Sistem']['items'], 'name'));
    }

    public function test_every_item_appears_exactly_once_across_groups(): void
    {
        $names = collect(MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => array_column($g['items'], 'name'))
            ->all();

        $expected = ['Dashboard', 'Produk', 'Kelas', 'Peserta Kursus', 'Blog', 'Testimoni Video', 'Banner Promo', 'Pesanan', 'Laporan', 'WA Notifikasi', 'Skema Cicilan', 'Settings'];

        $this->assertCount(count($expected), $names); // tidak ada yang hilang
        $this->assertCount(count($names), array_unique($names)); // tidak ada dobel
        $this->assertEqualsCanonicalizing($expected, $names);
    }

    public function test_items_carry_name_icon_and_resolved_path(): void
    {
        $item = collect(MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => $g['items'])
            ->firstWhere('name', 'Pesanan');

        $this->assertNotNull($item);
        $this->assertSame('shopping-bag', $item['icon']);
        $this->assertSame(route('admin.orders.index'), $item['path']);
    }

    public function test_disabled_item_is_dropped_from_its_group(): void
    {
        $primary = config('admin-nav.primary');
        foreach ($primary as $i => $item) {
            if ($item['key'] === 'reports') {
                $primary[$i]['enabled'] = false;
            }
        }
        config(['admin-nav.primary' => $primary]);

        $names = collect(MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => array_column($g['items'], 'name'))
            ->all();

        $this->assertNotContains('Laporan', $names);
    }

    public function test_unknown_key_in_group_is_skipped_and_empty_group_dropped(): void
    {
        config(['admin-nav.groups' => [
            ['title' => 'Utama', 'items' => ['dashboard', 'ghost-key', 'orders']],
            ['title' => 'Kosong', 'items' => ['nope']],
        ]]);

        $groups = MenuHelper::getMenuGroups();

        $this->assertCount(1, $groups); // grup "Kosong" di-drop
        $this->assertSame(['Dashboard', 'Pesanan'], array_column($groups[0]['items'], 'name'));
    }

    public function test_falls_back_to_single_menu_group_without_groups_config(): void
    {
        config(['admin-nav.groups' => []]);

        $groups = MenuHelper::getMenuGroups();

        $this->assertCount(1, $groups);
        $this->assertSame('Menu', $groups[0]['title']);
        $this->assertCount(12, $groups[0]['items']);
    }
}
