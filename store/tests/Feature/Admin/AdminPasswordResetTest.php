<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => Hash::make('oldpassword'),
        ], $overrides));
    }

    public function test_forgot_password_page_loads(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa Password');
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($admin, ResetPassword::class);
    }

    public function test_reset_link_for_unknown_email_does_not_send(): void
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
        $admin = $this->admin();

        $this->post(route('password.email'), ['email' => $admin->email]);

        $token = null;
        Notification::assertSentTo($admin, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('newpassword123', $admin->fresh()->password));
    }
}
