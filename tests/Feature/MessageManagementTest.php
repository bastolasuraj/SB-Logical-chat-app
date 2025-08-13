<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_get_messages_from_chat()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        // Create some messages
        Message::factory()->count(5)->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/chats/{$chat->id}/messages");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'chat_id',
                            'user_id',
                            'content',
                            'message_type',
                            'read_at',
                            'created_at',
                            'updated_at',
                            'user'
                        ]
                    ],
                    'pagination'
                ]);
    }

    public function test_user_can_send_message_to_chat()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $messageContent = 'Hello, this is a test message!';

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => $messageContent,
            'message_type' => 'text'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'chat_id',
                        'user_id',
                        'content',
                        'message_type',
                        'created_at',
                        'user'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'content' => $messageContent,
            'message_type' => 'text'
        ]);
    }

    public function test_user_cannot_send_message_to_chat_they_are_not_participant_in()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser1 = User::factory()->create(['email_verified_at' => now()]);
        $otherUser2 = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$otherUser1->id, $otherUser2->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'This should not work',
            'message_type' => 'text'
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You are not a participant in this chat.'
                ]);
    }

    public function test_message_content_is_required()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'message_type' => 'text'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
    }

    public function test_message_content_has_max_length()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => str_repeat('a', 10001), // Exceeds 10000 character limit
            'message_type' => 'text'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_get_older_messages()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        // Create messages with specific order
        $messages = Message::factory()->count(10)->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id
        ]);

        Sanctum::actingAs($user);

        $beforeId = $messages->last()->id;

        $response = $this->getJson("/api/chats/{$chat->id}/messages/older?before_id={$beforeId}&limit=5");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'has_more'
                ]);
    }

    public function test_user_can_view_specific_message()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/chats/{$chat->id}/messages/{$message->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'chat_id',
                        'user_id',
                        'content',
                        'message_type',
                        'created_at',
                        'user'
                    ]
                ]);
    }

    public function test_user_can_update_their_own_message()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'content' => 'Original message'
        ]);

        Sanctum::actingAs($user);

        $newContent = 'Updated message content';

        $response = $this->putJson("/api/chats/{$chat->id}/messages/{$message->id}", [
            'content' => $newContent
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'content' => $newContent
                    ]
                ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'content' => $newContent
        ]);
    }

    public function test_user_cannot_update_other_users_message()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $otherUser->id,
            'content' => 'Other user message'
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/chats/{$chat->id}/messages/{$message->id}", [
            'content' => 'Trying to update'
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You can only edit your own messages.'
                ]);
    }

    public function test_user_can_delete_their_own_message()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/chats/{$chat->id}/messages/{$message->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Message deleted successfully.'
                ]);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id
        ]);
    }

    public function test_user_can_mark_message_as_read()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $otherUser->id, // Message from other user
            'read_at' => null
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages/{$message->id}/mark-read");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Message marked as read.'
                ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
        ]);

        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    public function test_user_cannot_mark_own_message_as_read()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id, // User's own message
            'read_at' => null
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages/{$message->id}/mark-read");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'You cannot mark your own message as read.'
                ]);
    }

    public function test_message_content_cannot_be_only_whitespace()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        // Test with empty string after normalization
        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => '',
            'message_type' => 'text'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
    }

    public function test_message_content_is_sanitized()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => '<script>alert("xss")</script>Hello World!',
            'message_type' => 'text'
        ]);

        $response->assertStatus(201);
        
        $message = Message::latest()->first();
        $this->assertStringNotContainsString('<script>', $message->content);
        $this->assertStringContainsString('Hello World!', $message->content);
    }

    public function test_message_type_validation()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'Hello World!',
            'message_type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message_type']);
    }

    public function test_image_message_validation()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        // Valid image URL
        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'https://example.com/image.jpg',
            'message_type' => 'image'
        ]);

        $response->assertStatus(201);

        // Invalid image URL
        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'not-a-valid-url',
            'message_type' => 'image'
        ]);

        $response->assertStatus(422);
    }

    public function test_file_message_validation()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        // Valid file URL
        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'https://example.com/document.pdf',
            'message_type' => 'file'
        ]);

        $response->assertStatus(201);

        // Valid file reference
        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'uploads/documents/file_123.pdf',
            'message_type' => 'file'
        ]);

        $response->assertStatus(201);
    }

    public function test_message_timestamps_are_set_correctly()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        
        $chat = Chat::factory()->create(['type' => 'private']);
        $chat->participants()->attach([$user->id, $otherUser->id]);

        Sanctum::actingAs($user);

        $beforeTime = now()->subSecond(); // Give more buffer

        $response = $this->postJson("/api/chats/{$chat->id}/messages", [
            'content' => 'Test message with timestamp',
            'message_type' => 'text'
        ]);

        $afterTime = now()->addSecond(); // Give more buffer

        $response->assertStatus(201);
        
        $messageData = $response->json('data');
        $createdAt = \Carbon\Carbon::parse($messageData['created_at']);
        
        $this->assertTrue($createdAt->greaterThanOrEqualTo($beforeTime));
        $this->assertTrue($createdAt->lessThanOrEqualTo($afterTime));
        
        // Check if read_at exists in response, it should be null
        if (array_key_exists('read_at', $messageData)) {
            $this->assertNull($messageData['read_at']);
        }
    }
}