<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Asep Nurdin',
            'email' => 'asep@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'asep@example.com')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);

        $this->assertDatabaseHas('users', ['email' => 'asep@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registration_validates_password_confirmation_and_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Asep',
            'email' => 'taken@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'beda12345',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_login_get_profile_and_revoke_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'asep@example.com',
            'password' => 'Rahasia123',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'asep@example.com',
            'password' => 'Rahasia123',
            'device_name' => 'postman',
        ])->assertOk();

        $token = $login->json('data.token');
        $this->withToken($token)->getJson('/api/auth/me')->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_protected_routes_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->getJson('/api/products')->assertUnauthorized();
    }
}
