<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class FriendManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_get_friends_list()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $friend = User::factory()->create(['email_verified_at' => now()]);
        
        // Create accepted friendship
        Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $friend->id,
            'status' => 'accepted',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/friends');

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
                    ]
                ]
            ])
            ->assertJsonFragment(['id' => $friend->id]);
    }

    public function test_user_can_send_friend_request()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $targetUser = User::factory()->create(['email_verified_at' => now()]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/friends/request', [
            'user_id' => $targetUser->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'friendship_id',
                    'status',
                    'sent_to' => [
                        'id',
                        'name',
                        'email',
                        'avatar_url',
                    ],
                    'created_at',
                ]
            ])
            ->assertJsonFragment([
                'status' => 'pending',
                'id' => $targetUser->id,
            ]);

        $this->assertDatabaseHas('friendships', [
            'requester_id' => $currentUser->id,
            'addressee_id' => $targetUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_send_friend_request_to_self()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/friends/request', [
            'user_id' => $currentUser->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'You cannot send a friend request to yourself',
            ]);
    }

    public function test_user_cannot_send_duplicate_friend_request()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $targetUser = User::factory()->create(['email_verified_at' => now()]);
        
        // Create existing friendship
        Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $targetUser->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->postJson('/api/friends/request', [
            'user_id' => $targetUser->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Friend request already exists or you are already friends',
            ]);
    }

    public function test_user_can_accept_friend_request()
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $addressee = User::factory()->create(['email_verified_at' => now()]);
        
        $friendship = Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($addressee);

        $response = $this->postJson("/api/friends/accept/{$friendship->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'friendship_id',
                    'status',
                    'friend' => [
                        'id',
                        'name',
                        'email',
                        'avatar_url',
                        'is_online',
                    ],
                    'accepted_at',
                ]
            ])
            ->assertJsonFragment([
                'status' => 'accepted',
                'id' => $requester->id,
            ]);

        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_decline_friend_request()
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $addressee = User::factory()->create(['email_verified_at' => now()]);
        
        $friendship = Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($addressee);

        $response = $this->postJson("/api/friends/decline/{$friendship->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'declined',
            ]);

        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => 'declined',
        ]);
    }

    public function test_user_cannot_accept_request_not_addressed_to_them()
    {
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $addressee = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $friendship = Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/friends/accept/{$friendship->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_get_pending_requests()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $requester = User::factory()->create(['email_verified_at' => now()]);
        $addressee = User::factory()->create(['email_verified_at' => now()]);
        
        // Create received request
        $receivedRequest = Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $currentUser->id,
            'status' => 'pending',
        ]);
        
        // Create sent request
        $sentRequest = Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->getJson('/api/friends/pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'received' => [
                        '*' => [
                            'friendship_id',
                            'type',
                            'user' => [
                                'id',
                                'name',
                                'email',
                                'avatar_url',
                                'is_online',
                            ],
                            'created_at',
                            'can_accept',
                            'can_decline',
                        ]
                    ],
                    'sent' => [
                        '*' => [
                            'friendship_id',
                            'type',
                            'user',
                            'created_at',
                            'can_accept',
                            'can_decline',
                        ]
                    ],
                    'total_received',
                    'total_sent',
                ]
            ])
            ->assertJsonFragment([
                'friendship_id' => $receivedRequest->id,
                'type' => 'received',
                'can_accept' => true,
            ])
            ->assertJsonFragment([
                'friendship_id' => $sentRequest->id,
                'type' => 'sent',
                'can_accept' => false,
            ]);
    }

    public function test_user_can_cancel_sent_friend_request()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $targetUser = User::factory()->create(['email_verified_at' => now()]);
        
        $friendship = Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $targetUser->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->deleteJson("/api/friends/cancel/{$friendship->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'cancelled_friendship_id' => $friendship->id,
            ]);

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);
    }

    public function test_user_can_remove_friend()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        $friend = User::factory()->create(['email_verified_at' => now()]);
        
        $friendship = Friendship::create([
            'requester_id' => $currentUser->id,
            'addressee_id' => $friend->id,
            'status' => 'accepted',
        ]);
        
        Sanctum::actingAs($currentUser);

        $response = $this->deleteJson("/api/friends/remove/{$friend->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'removed_user_id' => $friend->id,
            ]);

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);
    }

    public function test_friend_request_validation()
    {
        $currentUser = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($currentUser);

        // Test missing user_id
        $response = $this->postJson('/api/friends/request', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);

        // Test invalid user_id
        $response = $this->postJson('/api/friends/request', [
            'user_id' => 99999,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_friend_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/friends');
        $response->assertStatus(401);

        $response = $this->postJson('/api/friends/request', ['user_id' => 1]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/friends/pending');
        $response->assertStatus(401);
    }

    public function test_friend_endpoints_require_email_verification()
    {
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);
        Sanctum::actingAs($unverifiedUser);

        $response = $this->getJson('/api/friends');
        $response->assertStatus(403); // Email verification required
    }
}