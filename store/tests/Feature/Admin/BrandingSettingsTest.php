<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Services\Settings;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Logo header & footer dinamis via tab Settings → Logo.
 */
class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ── Service ─────────────────────────────────────────────

    public function test_get_branding_is_null_when_unset(): void
    {
        $b = Settings::getBranding();

        $this->assertNull($b['header_logo_url']);
        $this->assertNull($b['footer_logo_url']);
    }

    public function test_get_branding_resolves_stored_path_to_url(): void
    {
        Settings::set('branding.header_logo', 'branding/x.png', 'string');

        $this->assertSame(
            Storage::disk('public')->url('branding/x.png'),
            Settings::getBranding()['header_logo_url'],
        );
    }

    // ── Admin tab ───────────────────────────────────────────

    public function test_logo_tab_renders(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index', ['tab' => 'logo']))
            ->assertOk()
            ->assertSee('Logo Header')
            ->assertSee('Logo Footer');
    }

    public function test_update_requires_auth(): void
    {
        $this->post(route('admin.settings.logo.update'))
            ->assertRedirect(route('admin.login'));
    }

    // ── Upload / replace / remove ───────────────────────────

    public function test_upload_header_and_footer_logo(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.logo.update'), [
                'header_logo' => UploadedFile::fake()->image('header.png', 200, 60),
                'footer_logo' => UploadedFile::fake()->image('footer.png', 200, 60),
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'logo']))
            ->assertSessionHas('status');

        $b = Settings::getBranding();
        $this->assertStringStartsWith('branding/', $b['header_logo_path']);
        $this->assertStringStartsWith('branding/', $b['footer_logo_path']);
        Storage::disk('public')->assertExists($b['header_logo_path']);
        Storage::disk('public')->assertExists($b['footer_logo_path']);
    }

    public function test_replacing_logo_deletes_old_file(): void
    {
        Storage::disk('public')->put('branding/old.png', 'x');
        Settings::set('branding.header_logo', 'branding/old.png', 'string');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.logo.update'), [
                'header_logo' => UploadedFile::fake()->image('new.png'),
            ]);

        Storage::disk('public')->assertMissing('branding/old.png');
        Storage::disk('public')->assertExists(Settings::getBranding()['header_logo_path']);
    }

    public function test_remove_logo_clears_and_deletes(): void
    {
        Storage::disk('public')->put('branding/gone.png', 'x');
        Settings::set('branding.header_logo', 'branding/gone.png', 'string');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.logo.update'), [
                'remove_header_logo' => '1',
            ]);

        Storage::disk('public')->assertMissing('branding/gone.png');
        $this->assertNull(Settings::getBranding()['header_logo_url']);
    }

    public function test_keeps_existing_when_no_file_and_no_remove(): void
    {
        Storage::disk('public')->put('branding/keep.png', 'x');
        Settings::set('branding.header_logo', 'branding/keep.png', 'string');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.logo.update'), []);

        $this->assertSame('branding/keep.png', Settings::getBranding()['header_logo_path']);
    }

    public function test_rejects_non_image_file(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.logo.update'), [
                'header_logo' => UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('header_logo');
    }

    public function test_rejects_svg_upload(): void
    {
        // SVG ditolak (stored-XSS: file svg di /storage bisa eksekusi <script>
        // saat dibuka langsung). Hanya raster yang diterima.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.logo.update'), [
                'header_logo' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
            ])
            ->assertSessionHasErrors('header_logo');

        $this->assertNull(Settings::getBranding()['header_logo_path']);
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    // ── Storefront render ───────────────────────────────────

    public function test_navbar_and_footer_render_logo_image_when_set(): void
    {
        Settings::set('branding.header_logo', 'branding/head.png', 'string');
        Settings::set('branding.footer_logo', 'branding/foot.png', 'string');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(Storage::disk('public')->url('branding/head.png'), $html);
        $this->assertStringContainsString(Storage::disk('public')->url('branding/foot.png'), $html);
    }

    public function test_falls_back_to_default_brand_when_no_logo(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Tanpa logo → ikon brain-circuit default masih dipakai (navbar + footer).
        $this->assertStringContainsString('brain-circuit', $html);
        $this->assertStringNotContainsString('branding/', $html);
    }
}
