<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Chat;
use App\Models\Friendship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_get_their_chats()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        // Create a chat between users
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/chats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'name',
                            'participants',
                            'last_message',
                            'last_message_at',
                            'unread_count',
                            'other_participant'
                        ]
                    ]
                ]);
    }

    public function test_user_can_create_private_chat()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/chats', [
            'user_id' => $otherUser->id,
            'type' => 'private'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'type',
                        'name',
                        'participants',
                        'last_message_at',
                        'unread_count',
                        'other_participant'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('chats', [
            'type' => 'private'
        ]);
    }

    public function test_user_cannot_create_chat_with_themselves()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/chats', [
            'user_id' => $user->id,
            'type' => 'private'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'You cannot create a chat with yourself.'
                ]);
    }

    public function test_duplicate_private_chat_returns_existing_chat()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        // Create existing chat
        $existingChat = Chat::factory()->create(['type' => 'private']);
        $existingChat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/chats', [
            'user_id' => $otherUser->id,
            'type' => 'private'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Chat already exists.',
                    'data' => [
                        'id' => $existingChat->id
                    ]
                ]);
    }

    public function test_user_can_view_specific_chat()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/chats/{$chat->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'type',
                        'name',
                        'participants',
                        'last_message_at',
                        'unread_count',
                        'other_participant'
                    ]
                ]);
    }

    public function test_user_cannot_view_chat_they_are_not_participant_in()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser1 = User::factory()->create(['email_verified_at' => now()]);
        $otherUser2 = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$otherUser1->id, $otherUser2->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/chats/{$chat->id}");

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You are not a participant in this chat.'
                ]);
    }

    public function test_user_can_mark_chat_as_read()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/mark-read");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Messages marked as read.'
                ]);
    }

    public function test_user_cannot_delete_private_chat()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/chats/{$chat->id}");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Private chats cannot be deleted.'
                ]);
    }

    public function test_unauthenticated_user_cannot_access_chats()
    {
        $response = $this->getJson('/api/chats');
        $response->assertStatus(401);
    }

    public function test_unverified_user_cannot_access_chats()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/chats');
        $response->assertStatus(403);
    }
}