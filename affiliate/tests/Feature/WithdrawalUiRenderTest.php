<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliatorPayoutAccount;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Database\Seeders\AffiliatorTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke render untuk setiap halaman yang tersentuh fitur metode penarikan.
 *
 * Fitur ini mengubah kolom tabel, mengganti form data bank dengan rekening
 * tersimpan, dan menambah dua halaman baru. Test fungsional di berkas lain
 * memeriksa perilakunya; berkas ini memastikan tidak ada halaman yang
 * meledak karena variabel hilang atau komponen salah pakai.
 */
class WithdrawalUiRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AffiliatorTypeSeeder::class);
    }

    private function adminSession(): array
    {
        return ['admin_authenticated' => true, 'admin_email' => 'admin@masfirmanpratama.com'];
    }

    private function affiliatorWithHistory(): Affiliator
    {
        $affiliator = Affiliator::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $method = WithdrawalMethod::factory()->withFee(2500)->create(['name' => 'BCA']);

        AffiliatorPayoutAccount::factory()->primary()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
        ]);

        Withdrawal::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
            'method_name' => 'BCA',
            'amount' => 100000,
            'fee' => 2500,
            'net_amount' => 97500,
        ]);

        return $affiliator;
    }

    public function test_affiliator_withdrawal_history_renders_with_fee_columns(): void
    {
        $this->actingAs($this->affiliatorWithHistory(), 'affiliator');

        $response = $this->get(route('withdrawals.index'));

        $response->assertStatus(200);
        $response->assertSee('Diterima');
        $response->assertSee('97.500');
        $response->assertSee('BCA');
    }

    public function test_affiliator_profile_page_renders_payout_account_card(): void
    {
        $this->actingAs($this->affiliatorWithHistory(), 'affiliator');

        $response = $this->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Rekening Tujuan Penarikan');
        $response->assertSee('Saat ini tersimpan 1 rekening');
    }

    public function test_admin_withdrawal_list_renders_net_amount(): void
    {
        $this->affiliatorWithHistory();

        $response = $this->withSession($this->adminSession())->get(route('admin.withdrawals.index'));

        $response->assertStatus(200);
        $response->assertSee('Ditransfer');
        $response->assertSee('97.500');
    }

    public function test_admin_dashboard_renders(): void
    {
        $this->affiliatorWithHistory();

        $response = $this->withSession($this->adminSession())->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('BCA');
    }

    public function test_admin_affiliator_detail_renders_saved_accounts(): void
    {
        $affiliator = $this->affiliatorWithHistory();

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.affiliators.show', $affiliator));

        $response->assertStatus(200);
        $response->assertSee('Rekening tujuan');
        $response->assertSee('BCA');
    }

    public function test_admin_method_edit_page_renders(): void
    {
        $method = WithdrawalMethod::factory()->create();

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.withdrawal-methods.edit', $method));

        $response->assertStatus(200);
        $response->assertSee('Biaya Admin (Rp)');
    }

    public function test_affiliator_sidebar_links_to_payout_accounts(): void
    {
        $this->actingAs($this->affiliatorWithHistory(), 'affiliator');

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('payout-accounts.index'));
    }

    public function test_admin_sidebar_links_to_withdrawal_methods(): void
    {
        $response = $this->withSession($this->adminSession())->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.withdrawal-methods.index'));
    }
}
