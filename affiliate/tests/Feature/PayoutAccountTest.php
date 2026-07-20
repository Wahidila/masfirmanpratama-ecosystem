<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliatorPayoutAccount;
use App\Models\WithdrawalMethod;
use Database\Seeders\AffiliatorTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutAccountTest extends TestCase
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

    public function test_affiliator_can_view_payout_accounts_page(): void
    {
        $this->actingAs($this->activeAffiliator(), 'affiliator');

        $response = $this->get(route('payout-accounts.index'));

        $response->assertStatus(200);
        $response->assertViewIs('payout-accounts.index');
    }

    public function test_first_saved_account_becomes_primary(): void
    {
        $affiliator = $this->activeAffiliator();
        $method = WithdrawalMethod::factory()->create();
        $this->actingAs($affiliator, 'affiliator');

        $response = $this->post(route('payout-accounts.store'), [
            'withdrawal_method_id' => $method->id,
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ]);

        $response->assertRedirect(route('payout-accounts.index'));
        $this->assertDatabaseHas('affiliator_payout_accounts', [
            'affiliator_id' => $affiliator->id,
            'account_number' => '1234567890',
            'is_primary' => true,
        ]);
    }

    public function test_second_saved_account_is_not_primary(): void
    {
        $affiliator = $this->activeAffiliator();
        AffiliatorPayoutAccount::factory()->primary()->create(['affiliator_id' => $affiliator->id]);
        $method = WithdrawalMethod::factory()->create();
        $this->actingAs($affiliator, 'affiliator');

        $this->post(route('payout-accounts.store'), [
            'withdrawal_method_id' => $method->id,
            'account_number' => '999888777',
            'account_name' => 'Budi Santoso',
        ]);

        $this->assertDatabaseHas('affiliator_payout_accounts', [
            'account_number' => '999888777',
            'is_primary' => false,
        ]);
    }

    public function test_inactive_method_cannot_be_saved(): void
    {
        $affiliator = $this->activeAffiliator();
        $method = WithdrawalMethod::factory()->inactive()->create();
        $this->actingAs($affiliator, 'affiliator');

        $response = $this->post(route('payout-accounts.store'), [
            'withdrawal_method_id' => $method->id,
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ]);

        $response->assertSessionHasErrors('withdrawal_method_id');
        $this->assertDatabaseCount('affiliator_payout_accounts', 0);
    }

    public function test_duplicate_account_is_rejected(): void
    {
        $affiliator = $this->activeAffiliator();
        $method = WithdrawalMethod::factory()->create();
        AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => $affiliator->id,
            'withdrawal_method_id' => $method->id,
            'account_number' => '1234567890',
        ]);
        $this->actingAs($affiliator, 'affiliator');

        $response = $this->post(route('payout-accounts.store'), [
            'withdrawal_method_id' => $method->id,
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ]);

        $response->assertSessionHasErrors('account_number');
        $this->assertSame(1, AffiliatorPayoutAccount::count());
    }

    public function test_setting_primary_unsets_the_previous_one(): void
    {
        $affiliator = $this->activeAffiliator();
        $first = AffiliatorPayoutAccount::factory()->primary()->create(['affiliator_id' => $affiliator->id]);
        $second = AffiliatorPayoutAccount::factory()->create(['affiliator_id' => $affiliator->id]);
        $this->actingAs($affiliator, 'affiliator');

        $this->post(route('payout-accounts.primary', $second));

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_deleting_primary_promotes_another_account(): void
    {
        $affiliator = $this->activeAffiliator();
        $primary = AffiliatorPayoutAccount::factory()->primary()->create(['affiliator_id' => $affiliator->id]);
        $other = AffiliatorPayoutAccount::factory()->create(['affiliator_id' => $affiliator->id]);
        $this->actingAs($affiliator, 'affiliator');

        $this->delete(route('payout-accounts.destroy', $primary));

        $this->assertDatabaseMissing('affiliator_payout_accounts', ['id' => $primary->id]);
        $this->assertTrue($other->fresh()->is_primary, 'Affiliator tidak boleh ditinggal tanpa rekening utama.');
    }

    public function test_affiliator_cannot_delete_someone_elses_account(): void
    {
        $mine = $this->activeAffiliator();
        $theirs = AffiliatorPayoutAccount::factory()->create(['affiliator_id' => $this->activeAffiliator()->id]);
        $this->actingAs($mine, 'affiliator');

        $response = $this->delete(route('payout-accounts.destroy', $theirs));

        $response->assertForbidden();
        $this->assertDatabaseHas('affiliator_payout_accounts', ['id' => $theirs->id]);
    }

    public function test_affiliator_cannot_promote_someone_elses_account(): void
    {
        $mine = $this->activeAffiliator();
        $theirs = AffiliatorPayoutAccount::factory()->create(['affiliator_id' => $this->activeAffiliator()->id]);
        $this->actingAs($mine, 'affiliator');

        $response = $this->post(route('payout-accounts.primary', $theirs));

        $response->assertForbidden();
        $this->assertFalse($theirs->fresh()->is_primary);
    }

    public function test_guest_cannot_manage_payout_accounts(): void
    {
        $response = $this->get(route('payout-accounts.index'));

        $response->assertRedirect(route('login'));
    }
}
