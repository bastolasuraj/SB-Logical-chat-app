<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
    }

    public function test_unverified_user_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Email not verified. Please verify your email to access this resource.',
                    'requires_verification' => true
                ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_unverified_user_can_still_access_auth_endpoints(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        // Should be able to access /me endpoint even without verification
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(200);

        // Should be able to logout even without verification
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(200);
    }
}