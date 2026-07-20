<?php

namespace Tests\Feature\Admin;

use App\Models\Affiliator;
use App\Models\AffiliatorPayoutAccount;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWithdrawalMethodTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        return ['admin_authenticated' => true, 'admin_email' => 'admin@masfirmanpratama.com'];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jago',
            'type' => 'bank_transfer',
            'min_withdrawal' => 50000,
            'fee_flat' => 2500,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_access_withdrawal_methods_index(): void
    {
        $response = $this->get(route('admin.withdrawal-methods.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_withdrawal_methods_index(): void
    {
        WithdrawalMethod::factory()->count(3)->create();

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.withdrawal-methods.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.withdrawal-methods.index');
        $response->assertViewHas('methods');
    }

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('admin.withdrawal-methods.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.withdrawal-methods.create');
    }

    public function test_admin_can_create_withdrawal_method(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.store'), $this->validPayload());

        $response->assertRedirect(route('admin.withdrawal-methods.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('withdrawal_methods', [
            'name' => 'Jago',
            'type' => 'bank_transfer',
            'min_withdrawal' => 50000,
            'fee_flat' => 2500,
            'is_active' => true,
        ]);
    }

    public function test_unchecked_active_box_creates_inactive_method(): void
    {
        $payload = $this->validPayload();
        unset($payload['is_active']);

        $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.store'), $payload);

        $this->assertDatabaseHas('withdrawal_methods', ['name' => 'Jago', 'is_active' => false]);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        WithdrawalMethod::factory()->create(['name' => 'Jago']);

        $response = $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.store'), $this->validPayload());

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, WithdrawalMethod::where('name', 'Jago')->count());
    }

    public function test_unknown_type_is_rejected(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.store'), $this->validPayload(['type' => 'qris']));

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseMissing('withdrawal_methods', ['name' => 'Jago']);
    }

    public function test_fee_equal_or_above_minimum_is_rejected(): void
    {
        // Biaya >= minimum berarti affiliator bisa menerima nol atau minus.
        $response = $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.store'), $this->validPayload([
                'min_withdrawal' => 25000,
                'fee_flat' => 25000,
            ]));

        $response->assertSessionHasErrors('fee_flat');
        $this->assertDatabaseMissing('withdrawal_methods', ['name' => 'Jago']);
    }

    public function test_admin_can_update_withdrawal_method(): void
    {
        $method = WithdrawalMethod::factory()->create(['name' => 'Jago', 'min_withdrawal' => 50000, 'fee_flat' => 0]);

        $response = $this->withSession($this->adminSession())
            ->put(route('admin.withdrawal-methods.update', $method), $this->validPayload([
                'name' => 'Bank Jago',
                'min_withdrawal' => 30000,
                'fee_flat' => 1500,
            ]));

        $response->assertRedirect(route('admin.withdrawal-methods.index'));
        $method->refresh();
        $this->assertSame('Bank Jago', $method->name);
        $this->assertEquals(30000, $method->min_withdrawal);
        $this->assertEquals(1500, $method->fee_flat);
    }

    public function test_method_can_keep_its_own_name_on_update(): void
    {
        // Unique rule harus mengecualikan baris yang sedang diedit.
        $method = WithdrawalMethod::factory()->create(['name' => 'Jago']);

        $response = $this->withSession($this->adminSession())
            ->put(route('admin.withdrawal-methods.update', $method), $this->validPayload(['name' => 'Jago']));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.withdrawal-methods.index'));
    }

    public function test_admin_can_toggle_method_active_state(): void
    {
        $method = WithdrawalMethod::factory()->create(['is_active' => true]);

        $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.toggle', $method));

        $this->assertFalse($method->fresh()->is_active);

        $this->withSession($this->adminSession())
            ->post(route('admin.withdrawal-methods.toggle', $method));

        $this->assertTrue($method->fresh()->is_active);
    }

    public function test_admin_can_delete_unused_method(): void
    {
        $method = WithdrawalMethod::factory()->create();

        $response = $this->withSession($this->adminSession())
            ->delete(route('admin.withdrawal-methods.destroy', $method));

        $response->assertRedirect(route('admin.withdrawal-methods.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('withdrawal_methods', ['id' => $method->id]);
    }

    public function test_method_used_by_a_withdrawal_cannot_be_deleted(): void
    {
        $method = WithdrawalMethod::factory()->create();
        Withdrawal::factory()->create(['withdrawal_method_id' => $method->id]);

        $response = $this->withSession($this->adminSession())
            ->delete(route('admin.withdrawal-methods.destroy', $method));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('withdrawal_methods', ['id' => $method->id]);
    }

    public function test_method_used_by_a_saved_account_cannot_be_deleted(): void
    {
        $method = WithdrawalMethod::factory()->create();
        AffiliatorPayoutAccount::factory()->create([
            'affiliator_id' => Affiliator::factory(),
            'withdrawal_method_id' => $method->id,
        ]);

        $response = $this->withSession($this->adminSession())
            ->delete(route('admin.withdrawal-methods.destroy', $method));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('withdrawal_methods', ['id' => $method->id]);
    }
}
