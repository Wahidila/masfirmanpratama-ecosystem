<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliatorType;
use Database\Seeders\AffiliatorTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AffiliatorTypeSeeder::class);
    }

    public function test_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Affiliate Program');
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke akun Anda');
    }

    public function test_register_page_loads(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Daftar Affiliate');
    }

    public function test_register_page_shows_affiliator_types(): void
    {
        $response = $this->get('/register');
        $response->assertSee('Alumni');
        $response->assertSee('Non-Alumni');
        $response->assertDontSee('Peserta Aktif');
    }

    public function test_only_two_affiliator_types_exist(): void
    {
        $this->assertSame(2, AffiliatorType::count());
        $this->assertSame(
            ['alumni', 'non-alumni'],
            AffiliatorType::orderBy('slug')->pluck('slug')->all()
        );
    }

    public function test_user_can_register(): void
    {
        $type = AffiliatorType::where('slug', 'alumni')->first();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '081234567890',
            'affiliator_type_id' => $type->id,
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('affiliators', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'affiliator_type_id' => $type->id,
            'status' => 'pending',
        ]);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->post('/register', []);
        $response->assertSessionHasErrors(['name', 'email', 'password', 'affiliator_type_id']);
    }

    public function test_register_validates_unique_email(): void
    {
        $type = AffiliatorType::where('slug', 'alumni')->first();

        Affiliator::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'name' => 'Test',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'affiliator_type_id' => $type->id,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_active_user_can_login(): void
    {
        $affiliator = Affiliator::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $affiliator->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($affiliator, 'affiliator');
    }

    public function test_pending_user_cannot_access_dashboard(): void
    {
        $affiliator = Affiliator::factory()->create([
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($affiliator, 'affiliator');

        $response = $this->get('/dashboard');
        $response->assertRedirect(route('pending-approval'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $affiliator = Affiliator::factory()->create(['status' => 'active']);

        $response = $this->post('/login', [
            'email' => $affiliator->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest('affiliator');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_logout(): void
    {
        $affiliator = Affiliator::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $this->actingAs($affiliator, 'affiliator');

        $response = $this->post('/logout');
        $response->assertRedirect(route('login'));
        $this->assertGuest('affiliator');
    }
}
