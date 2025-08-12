<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Logout successful'
                ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create a token
        $token = $user->createToken('test-token');
        $tokenId = $token->accessToken->id;
        
        // Verify token exists in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId
        ]);
        
        // Use the token to authenticate and logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);

        // Verify the token was deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId
        ]);
    }

    public function test_authenticated_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create multiple tokens
        $token1 = $user->createToken('device-1');
        $token2 = $user->createToken('device-2');
        
        $token1Id = $token1->accessToken->id;
        $token2Id = $token2->accessToken->id;

        // Verify both tokens exist in database
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token1Id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token2Id]);

        // Use one token to logout from all devices
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1->plainTextToken,
        ])->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Logged out from all devices successfully'
                ]);

        // Verify all tokens were deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token1Id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token2Id]);
    }

    public function test_unauthenticated_user_cannot_logout_from_all_devices(): void
    {
        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'avatar_url',
                        'last_seen_at',
                        'is_online',
                    ]
                ])
                ->assertJson([
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ]
                ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_endpoint_updates_last_seen_timestamp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'last_seen_at' => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me');

        $user->refresh();
        $this->assertNotNull($user->last_seen_at);
    }
}