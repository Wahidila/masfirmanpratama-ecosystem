<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Setting;
use App\Services\Settings;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_redirects_unauthenticated_to_login(): void
    {
        $this->get(route('admin.settings.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_renders_store_info_tab_by_default(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Store Info');
        $response->assertSee('Bank Accounts');
        $response->assertSee('Nama toko');
    }

    public function test_renders_bank_accounts_tab(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index', ['tab' => 'bank-accounts']));

        $response->assertStatus(200);
        $response->assertSee('Tambah Rekening');
    }

    public function test_invalid_tab_falls_back_to_store_info(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index', ['tab' => 'totally-invalid-tab']));

        $response->assertStatus(200);
        $response->assertSee('Nama toko');
    }

    public function test_update_store_info_persists_to_settings_table(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.store-info.update'), [
                'name' => 'Toko Mas Firman',
                'tagline' => 'Mind Power & Life Mastery',
                'address' => 'Jl. Contoh 123',
                'city' => 'Jakarta Selatan',
                'phone' => '6281234567890',
                'email' => 'admin@mfp.test',
                'operating_hours' => 'Senin-Sabtu 09:00-18:00',
            ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'store-info']));
        $response->assertSessionHas('status');

        $this->assertSame('Toko Mas Firman', Setting::getValue('store.name'));
        $this->assertSame('Jakarta Selatan', Setting::getValue('store.city'));
        $this->assertSame('admin@mfp.test', Setting::getValue('store.email'));
    }

    public function test_update_store_info_validates_required_name(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.index', ['tab' => 'store-info']))
            ->put(route('admin.settings.store-info.update'), [
                'name' => '',
                'email' => 'admin@mfp.test',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_store_info_validates_email_format(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.index', ['tab' => 'store-info']))
            ->put(route('admin.settings.store-info.update'), [
                'name' => 'Toko',
                'email' => 'not-an-email',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_update_bank_accounts_persists_array(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.bank-accounts.update'), [
                'bank_accounts' => [
                    ['bank' => 'BCA', 'number' => '1234-5678', 'holder' => 'PT MFP', 'logo_color' => 'sky', 'primary' => '1'],
                    ['bank' => 'Mandiri', 'number' => '9999-0000', 'holder' => 'PT MFP', 'logo_color' => 'amber'],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'bank-accounts']));

        $stored = Setting::getValue('bank_accounts');
        $this->assertIsArray($stored);
        $this->assertCount(2, $stored);
        $this->assertSame('BCA', $stored[0]['bank']);
        $this->assertTrue($stored[0]['primary']);
        $this->assertFalse($stored[1]['primary']);
    }

    public function test_update_bank_accounts_filters_empty_rows(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.bank-accounts.update'), [
                'bank_accounts' => [
                    ['bank' => 'BCA', 'number' => '1234'],
                    ['bank' => '', 'number' => ''], // empty row should be filtered
                ],
            ]);

        $response->assertRedirect();

        $stored = Setting::getValue('bank_accounts');
        $this->assertCount(1, $stored);
    }

    public function test_update_bank_accounts_validates_required_fields_when_partial(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.settings.index', ['tab' => 'bank-accounts']))
            ->put(route('admin.settings.bank-accounts.update'), [
                'bank_accounts' => [
                    ['bank' => 'BCA'], // missing number
                ],
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_settings_service_get_returns_default_when_missing(): void
    {
        $value = Settings::get('non.existent.key', 'fallback-default');
        $this->assertSame('fallback-default', $value);
    }

    public function test_settings_service_set_and_get_roundtrip(): void
    {
        Settings::set('test.string', 'hello world', 'string');
        Settings::set('test.bool', true, 'bool');
        Settings::set('test.array', ['a', 'b', 'c'], 'array');

        $this->assertSame('hello world', Settings::get('test.string'));
        $this->assertTrue(Settings::get('test.bool'));
        $this->assertSame(['a', 'b', 'c'], Settings::get('test.array'));
    }

    public function test_get_bank_accounts_falls_back_to_config_when_db_empty(): void
    {
        // No DB row exists yet — should fall back to config
        $accounts = Settings::getBankAccounts();
        $this->assertNotEmpty($accounts);
        $this->assertSame('BCA', $accounts[0]['bank']); // from config/store.php dummy
    }

    public function test_get_bank_accounts_uses_db_when_present(): void
    {
        Settings::set('bank_accounts', [
            ['bank' => 'BSI', 'number' => '7777', 'holder' => 'Test', 'primary' => true],
        ], 'array');

        $accounts = Settings::getBankAccounts();
        $this->assertCount(1, $accounts);
        $this->assertSame('BSI', $accounts[0]['bank']);
    }

    public function test_get_store_info_returns_bundle_with_defaults(): void
    {
        $info = Settings::getStoreInfo();
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('email', $info);
        $this->assertArrayHasKey('city', $info);
    }

    public function test_get_store_info_reflects_db_values(): void
    {
        Settings::set('store.name', 'Custom MFP Name', 'string');
        Settings::set('store.city', 'Surabaya', 'string');

        $info = Settings::getStoreInfo();
        $this->assertSame('Custom MFP Name', $info['name']);
        $this->assertSame('Surabaya', $info['city']);
    }

    public function test_whatsapp_tab_saves_admin_alert_number(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.whatsapp.update'), [
                'xsender_api_key' => 'KEY123',
                'xsender_sender' => '6285111201722',
                'wa_admin_number' => '081234000111', // format 0.. → dinormalisasi ke 62..
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'whatsapp']));

        // Nomor admin ter-normalisasi & tersimpan → alert admin tidak lagi ke placeholder.
        $this->assertSame('6281234000111', Settings::getWaAdmin()['number']);
    }

    public function test_whatsapp_tab_prefills_admin_number(): void
    {
        Settings::set('wa_admin', ['number' => '6285111201722', 'label' => 'Admin'], 'array');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index', ['tab' => 'whatsapp']))
            ->assertStatus(200)
            ->assertSee('name="wa_admin_number"', false)
            ->assertSee('value="6285111201722"', false);
    }
}
