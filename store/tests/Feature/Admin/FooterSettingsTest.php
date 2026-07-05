<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Setting;
use App\Services\Settings;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Footer storefront dinamis via tab Settings → Footer.
 */
class FooterSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ── Service defaults ────────────────────────────────────

    public function test_get_footer_returns_defaults_when_unset(): void
    {
        $footer = Settings::getFooter();

        $this->assertSame('Firman', $footer['brand_text']);
        $this->assertSame(Settings::DEFAULT_FOOTER_SOCIALS, $footer['socials']);
        $this->assertSame(Settings::DEFAULT_FOOTER_LINKS, $footer['links']);
        $this->assertSame(Settings::DEFAULT_FOOTER_LEGAL, $footer['legal']);
    }

    public function test_saved_empty_list_is_respected_not_overridden_by_default(): void
    {
        Settings::set('footer.socials', [], 'array');

        $this->assertSame([], Settings::getFooter()['socials']);
    }

    // ── Admin tab ───────────────────────────────────────────

    public function test_footer_tab_renders(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index', ['tab' => 'footer']))
            ->assertOk()
            ->assertSee('Footer')
            ->assertSee('Media Sosial')
            ->assertSee('Kolom Link')
            ->assertSee('Tagline');
    }

    public function test_update_requires_auth(): void
    {
        $this->put(route('admin.settings.footer.update'))
            ->assertRedirect(route('admin.login'));
    }

    // ── Update ──────────────────────────────────────────────

    public function test_update_saves_scalars_and_lists(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'brand_text' => 'Mas Firman',
                'brand_accent' => 'Pratama',
                'tagline' => 'Tagline baru dari admin.',
                'address' => 'Surabaya HQ',
                'phone' => '0812-0000',
                'email' => 'halo@example.com',
                'copyright' => '© {year} Contoh.',
                'socials' => [
                    ['icon' => 'instagram', 'href' => 'https://instagram.com/x', 'label' => 'IG'],
                ],
                'links' => [
                    ['group' => 'Layanan', 'label' => 'Kelas', 'href' => '/produk'],
                    ['group' => 'Info', 'label' => 'Blog', 'href' => '/blog'],
                ],
                'legal' => [
                    ['label' => 'Privasi', 'href' => '/privacy'],
                ],
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'footer']))
            ->assertSessionHas('status');

        $footer = Settings::getFooter();
        $this->assertSame('Mas Firman', $footer['brand_text']);
        $this->assertSame('Tagline baru dari admin.', $footer['tagline']);
        $this->assertCount(1, $footer['socials']);
        $this->assertSame('instagram', $footer['socials'][0]['icon']);
        $this->assertCount(2, $footer['links']);
        $this->assertSame('halo@example.com', Setting::getValue('footer.email'));
    }

    public function test_update_filters_incomplete_rows(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'socials' => [
                    ['icon' => 'instagram', 'href' => 'https://ig/x', 'label' => 'IG'],
                    ['icon' => 'facebook', 'href' => '', 'label' => 'FB'], // no href → dropped
                ],
                'links' => [
                    ['group' => 'A', 'label' => 'Ada', 'href' => '/a'],
                    ['group' => '', 'label' => 'Tanpa grup', 'href' => '/b'], // no group → dropped
                ],
            ]);

        $footer = Settings::getFooter();
        $this->assertCount(1, $footer['socials']);
        $this->assertCount(1, $footer['links']);
        $this->assertSame('A', $footer['links'][0]['group']);
    }

    public function test_update_defaults_social_label_from_icon(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'socials' => [['icon' => 'youtube', 'href' => 'https://yt/x', 'label' => '']],
            ]);

        $this->assertSame('Youtube', Settings::getFooter()['socials'][0]['label']);
    }

    public function test_update_validates_email(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), ['email' => 'bukan-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_footer_tab_renders_icon_picker(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index', ['tab' => 'footer']))
            ->assertOk()
            ->assertSee('Pilih icon…')
            ->assertSee('Telegram'); // opsi galeri dari FOOTER_SOCIAL_ICONS
    }

    public function test_update_rejects_unknown_social_icon(): void
    {
        // Icon harus dari picker terkurasi (nama Lucide valid) — nama bebas ditolak.
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'socials' => [['icon' => 'icon-ngawur', 'href' => 'https://x.com/a', 'label' => 'X']],
            ])
            ->assertSessionHasErrors('socials.0.icon');
    }

    public function test_update_accepts_curated_social_icon(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'socials' => [['icon' => 'send', 'href' => 'https://t.me/firman', 'label' => 'Telegram']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('send', Settings::getFooter()['socials'][0]['icon']);
    }

    public function test_update_rejects_dangerous_url_scheme(): void
    {
        // Footer link tampil di halaman publik — skema javascript:/data: ditolak
        // (stored-XSS guard), tidak boleh tersimpan.
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'links' => [
                    ['group' => 'Evil', 'label' => 'Klik', 'href' => 'javascript:alert(document.cookie)'],
                ],
            ])
            ->assertSessionHasErrors('footer_url');

        // Tidak tersimpan → getFooter tetap default (skema jahat tak pernah masuk).
        $this->assertSame(Settings::DEFAULT_FOOTER_LINKS, Settings::getFooter()['links']);
    }

    public function test_update_allows_relative_and_http_urls(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.footer.update'), [
                'socials' => [['icon' => 'instagram', 'href' => 'https://instagram.com/x', 'label' => 'IG']],
                'links' => [['group' => 'A', 'label' => 'Blog', 'href' => '/blog']],
                'legal' => [['label' => 'Kontak', 'href' => 'mailto:hi@example.com']],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.settings.index', ['tab' => 'footer']));
    }

    // ── Storefront render ───────────────────────────────────

    public function test_storefront_footer_reflects_saved_content(): void
    {
        $this->actingAs($this->admin, 'admin')->put(route('admin.settings.footer.update'), [
            'brand_text' => 'Firman',
            'brand_accent' => 'Pratama',
            'tagline' => 'TAGLINE-KUSTOM-123',
            'copyright' => '© {year} Hak Cipta Kustom.',
            'socials' => [['icon' => 'instagram', 'href' => 'https://instagram.com/kustom', 'label' => 'IG']],
            'links' => [
                ['group' => 'GrupSatu', 'label' => 'LinkSatu', 'href' => '/satu'],
                ['group' => 'GrupDua', 'label' => 'LinkDua', 'href' => '/dua'],
            ],
            'legal' => [['label' => 'LegalKustom', 'href' => '/legal-x']],
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('TAGLINE-KUSTOM-123', $html);
        $this->assertStringContainsString('https://instagram.com/kustom', $html);
        // Kedua grup jadi kolom footer.
        $this->assertStringContainsString('GrupSatu', $html);
        $this->assertStringContainsString('GrupDua', $html);
        $this->assertStringContainsString('LinkSatu', $html);
        // Legal + copyright dengan tahun terisi.
        $this->assertStringContainsString('LegalKustom', $html);
        $this->assertStringContainsString('© '.now()->year.' Hak Cipta Kustom.', $html);
    }

    public function test_storefront_footer_uses_defaults_out_of_the_box(): void
    {
        // Tanpa setting apa pun, footer tetap tampil lengkap (default).
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Pakar Pikiran No.1 Indonesia', $html);
        $this->assertStringContainsString('Layanan', $html);
        $this->assertStringContainsString('Komunitas', $html);
    }
}
