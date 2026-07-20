<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliatorPayoutAccount;
use App\Models\Commission;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Database\Seeders\AffiliatorTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AffiliatorTypeSeeder::class);
    }

    private function activeAffiliator(): Affiliator
    {
        return Affiliator::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function giveBalance(Affiliator $affiliator, float $amount): void
    {
        Commission::create([
            'affiliator_id' => $affiliator->id,
            'referral_order_id' => null,
            'amount' => $amount,
            'rate_applied' => 10,
            'status' => 'available',
            'available_at' => now()->subDay(),
        ]);
    }

    public function test_form_only_offers_accounts_whose_method_is_active(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 500000);

        $live = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => WithdrawalMethod::factory()->create(['name' => 'Bank Aktif'])->id,
        ]);
        AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => WithdrawalMethod::factory()->inactive()->create(['name' => 'Bank Mati'])->id,
        ]);

        $this->actingAs($affiliator, 'affiliator');
        $response = $this->get(route('withdrawals.create'));

        $response->assertStatus(200);
        $response->assertSee('Bank Aktif');
        $response->assertDontSee('Bank Mati');
        $this->assertCount(1, $response->viewData('accounts'));
        $this->assertSame($live->id, $response->viewData('accounts')->first()->id);
    }

    public function test_fee_is_deducted_from_transferred_amount_not_from_balance(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 120000);

        $method = WithdrawalMethod::factory()->withFee(2500)->create([
            'name' => 'BCA',
            'min_withdrawal' => 50000,
        ]);
        $account = AffiliatorPayoutAccount::factory()->primary()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ]);

        $this->actingAs($affiliator, 'affiliator');
        $response = $this->post(route('withdrawals.store'), [
            'payout_account_id' => $account->id,
            'amount' => 100000,
        ]);

        $response->assertRedirect(route('withdrawals.index'));
        $withdrawal = Withdrawal::firstOrFail();

        $this->assertEquals(100000, $withdrawal->amount, 'Bruto = yang diminta.');
        $this->assertEquals(2500, $withdrawal->fee);
        $this->assertEquals(97500, $withdrawal->net_amount, 'Neto = bruto - biaya admin.');
        // Rekening di-snapshot, bukan dibaca ulang lewat relasi.
        $this->assertSame('1234567890', $withdrawal->account_number);
        $this->assertSame('Budi Santoso', $withdrawal->account_name);
        $this->assertSame('BCA', $withdrawal->method_name);
    }

    public function test_renaming_a_method_does_not_rewrite_withdrawal_history(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 200000);

        $method = WithdrawalMethod::factory()->create(['name' => 'BCA', 'min_withdrawal' => 50000]);
        $account = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
        ]);

        $this->actingAs($affiliator, 'affiliator');
        $this->post(route('withdrawals.store'), ['payout_account_id' => $account->id, 'amount' => 100000]);

        $method->update(['name' => 'Bank Central Asia']);

        $this->assertSame('BCA', Withdrawal::firstOrFail()->methodName());
    }

    public function test_withdrawal_via_deactivated_method_is_rejected(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 200000);

        $method = WithdrawalMethod::factory()->create(['min_withdrawal' => 50000]);
        $account = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
        ]);

        // Admin menonaktifkan metode setelah halaman penarikan dibuka.
        $method->update(['is_active' => false]);

        $this->actingAs($affiliator, 'affiliator');
        $response = $this->post(route('withdrawals.store'), [
            'payout_account_id' => $account->id,
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('payout_account_id');
        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_affiliator_cannot_withdraw_to_someone_elses_account(): void
    {
        $mine = $this->activeAffiliator();
        $this->giveBalance($mine, 200000);

        $theirs = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $this->activeAffiliator()->id,
            'withdrawal_method_id' => WithdrawalMethod::factory()->create(['min_withdrawal' => 50000])->id,
        ]);

        $this->actingAs($mine, 'affiliator');
        $response = $this->post(route('withdrawals.store'), [
            'payout_account_id' => $theirs->id,
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('payout_account_id');
        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_amount_below_method_minimum_is_rejected(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 200000);

        $method = WithdrawalMethod::factory()->create(['min_withdrawal' => 50000]);
        $account = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
        ]);

        $this->actingAs($affiliator, 'affiliator');
        $response = $this->post(route('withdrawals.store'), [
            'payout_account_id' => $account->id,
            'amount' => 40000,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_minimum_is_compared_against_gross_not_net(): void
    {
        // Biaya admin tidak boleh membuat penarikan tepat-minimum ikut ditolak.
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 200000);

        $method = WithdrawalMethod::factory()->withFee(2500)->create(['min_withdrawal' => 50000]);
        $account = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
        ]);

        $this->actingAs($affiliator, 'affiliator');
        $response = $this->post(route('withdrawals.store'), [
            'payout_account_id' => $account->id,
            'amount' => 50000,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(47500, Withdrawal::firstOrFail()->net_amount);
    }

    public function test_amount_above_available_balance_is_rejected(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->giveBalance($affiliator, 60000);

        $method = WithdrawalMethod::factory()->create(['min_withdrawal' => 50000]);
        $account = AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
        ]);

        $this->actingAs($affiliator, 'affiliator');
        $response = $this->post(route('withdrawals.store'), [
            'payout_account_id' => $account->id,
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawals', 0);
    }
}
