<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class UserSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_search_for_other_users()
    {
        // Create users
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $searchableUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/search?query=John');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'avatar',
                        'last_seen_at',
                        'friendship_status',
                        'friendship_id',
                        'can_send_request',
                        'can_accept',
                        'can_decline',
                        'avatar_url',
                        'is_online',
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'friendship_status' => 'none',
                'can_send_request' => true,
            ]);
    }

    public function test_search_excludes_current_user()
    {
        $currentUser = User::factory()->create([
            'name' => 'Current User',
            'email_verified_at' => now(),
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/search?query=Current');

        $response->assertStatus(200)
            ->assertJsonMissing([
                'name' => 'Current User',
            ]);
    }

    public function test_search_excludes_unverified_users()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create([
            'name' => 'Unverified User',
            'email_verified_at' => null,
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/search?query=Unverified');

        $response->assertStatus(200)
            ->assertJsonMissing([
                'name' => 'Unverified User',
            ]);
    }

    public function test_search_shows_friendship_status()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $friendUser = User::factory()->create([
            'name' => 'Friend User',
            'email_verified_at' => now(),
        ]);
        
        // Create pending friendship
        $friendship = Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $friendUser->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/search?query=Friend');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Friend User',
                'friendship_status' => 'pending',
                'friendship_id' => $friendship->id,
                'can_send_request' => false,
            ]);
    }

    public function test_search_requires_minimum_query_length()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/search?query=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    public function test_search_requires_authentication()
    {
        $response = $this->getJson('/api/users/search?query=test');

        $response->assertStatus(401);
    }

    public function test_search_supports_pagination()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        
        // Create multiple users
        User::factory()->count(15)->create([
            'name' => 'Test User',
            'email_verified_at' => now(),
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/search?query=Test&per_page=5&page=1');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonCount(5, 'data');
    }

    public function test_user_can_get_suggestions()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $suggestedUser = User::factory()->create([
            'email_verified_at' => now(),
            'last_seen_at' => now()->subMinutes(10),
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/suggestions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'avatar',
                        'last_seen_at',
                        'avatar_url',
                        'is_online',
                        'friendship_status',
                        'can_send_request',
                    ]
                ]
            ]);
    }

    public function test_suggestions_exclude_existing_friends()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $friendUser = User::factory()->create(['email_verified_at' => now()]);
        $nonFriendUser = User::factory()->create(['email_verified_at' => now()]);
        
        // Create accepted friendship
        Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $friendUser->id,
            'status' => 'accepted',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/suggestions');

        $response->assertStatus(200)
            ->assertJsonMissing(['id' => $friendUser->id])
            ->assertJsonFragment(['id' => $nonFriendUser->id]);
    }

    public function test_suggestions_support_limit()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        User::factory()->count(10)->create(['email_verified_at' => now()]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/users/suggestions?limit=3');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}