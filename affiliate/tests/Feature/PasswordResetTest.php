<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function affiliator(array $overrides = []): Affiliator
    {
        return Affiliator::factory()->create(array_merge([
            'email' => 'aff@example.com',
            'password' => Hash::make('oldpassword'),
            'status' => 'active',
        ], $overrides));
    }

    public function test_forgot_password_page_loads(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa password');
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $affiliator = $this->affiliator();

        $this->post(route('password.email'), ['email' => $affiliator->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($affiliator, ResetPassword::class);
    }

    public function test_reset_link_request_for_unknown_email_does_not_error(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        Notification::assertNothingSent();
    }

    public function test_reset_password_page_loads(): void
    {
        $this->get(route('password.reset', ['token' => 'sometoken']))->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $affiliator = $this->affiliator();

        $this->post(route('password.email'), ['email' => $affiliator->email]);

        $token = null;
        Notification::assertSentTo($affiliator, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $affiliator->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('newpassword123', $affiliator->fresh()->password));
    }

    public function test_reset_rejects_short_password(): void
    {
        $this->affiliator();

        $this->post(route('password.update'), [
            'token' => 'whatever',
            'email' => 'aff@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
