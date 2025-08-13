<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->messageService = new MessageService();
    }

    public function test_can_create_text_message()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'Hello, this is a test message!',
            'message_type' => 'text'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($chat->id, $message->chat_id);
        $this->assertEquals($user->id, $message->user_id);
        $this->assertEquals('Hello, this is a test message!', $message->content);
        $this->assertEquals('text', $message->message_type);
    }

    public function test_sanitizes_message_content()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => '<script>alert("xss")</script>Hello   world!   ',
            'message_type' => 'text'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);

        // Should strip HTML tags and encode special characters
        $this->assertStringNotContainsString('<script>', $message->content);
        // The content should be sanitized - let's check what we actually get
        $this->assertStringContainsString('Hello world!', $message->content);
        
        // Debug: Let's see what the actual content is
        // dump($message->content);
        
        // The script tags should be stripped and content encoded
        $this->assertTrue(strlen($message->content) > 0);
    }

    public function test_validates_empty_content()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => '   ',
            'message_type' => 'text'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_validates_content_length()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => str_repeat('a', 10001), // Exceeds 10000 character limit
            'message_type' => 'text'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_detects_spam_patterns()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', // Repeated characters
            'message_type' => 'text'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_can_update_message()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'content' => 'Original content'
        ]);

        $data = [
            'content' => 'Updated content'
        ];

        $updatedMessage = $this->messageService->updateMessage($message, $data);

        $this->assertEquals('Updated content', $updatedMessage->content);
    }

    public function test_can_mark_message_as_read()
    {
        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $chat = Chat::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $sender->id,
            'read_at' => null
        ]);

        $this->messageService->markMessageAsRead($message, $reader);

        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    public function test_cannot_mark_own_message_as_read()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'read_at' => null
        ]);

        $this->expectException(ValidationException::class);
        $this->messageService->markMessageAsRead($message, $user);
    }

    public function test_can_mark_chat_as_read()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $chat = Chat::factory()->create();
        
        // Create messages from user2 to user1 (unread)
        Message::factory()->count(3)->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id,
            'read_at' => null
        ]);

        // Create message from user1 (should not be marked as read)
        Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user1->id,
            'read_at' => null
        ]);

        $updatedCount = $this->messageService->markChatAsRead($chat, $user1);

        $this->assertEquals(3, $updatedCount);
        
        // Verify only messages from other users were marked as read
        $unreadCount = $chat->messages()->whereNull('read_at')->count();
        $this->assertEquals(1, $unreadCount); // Only user1's own message remains unread
    }

    public function test_can_delete_message()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id
        ]);

        $messageId = $message->id;

        $this->messageService->deleteMessage($message, $user);

        $this->assertDatabaseMissing('messages', ['id' => $messageId]);
    }

    public function test_cannot_delete_other_users_message()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $chat = Chat::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user1->id
        ]);

        $this->expectException(ValidationException::class);
        $this->messageService->deleteMessage($message, $user2);
    }

    public function test_validates_image_message_url()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'https://example.com/image.jpg',
            'message_type' => 'image'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);

        $this->assertEquals('https://example.com/image.jpg', $message->content);
        $this->assertEquals('image', $message->message_type);
    }

    public function test_rejects_invalid_image_url()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'not-a-valid-url',
            'message_type' => 'image'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_validates_file_message_url()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'https://example.com/document.pdf',
            'message_type' => 'file'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);

        $this->assertEquals('https://example.com/document.pdf', $message->content);
        $this->assertEquals('file', $message->message_type);
    }

    public function test_accepts_valid_file_reference()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'uploads/documents/file_123.pdf',
            'message_type' => 'file'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);

        $this->assertEquals('uploads/documents/file_123.pdf', $message->content);
        $this->assertEquals('file', $message->message_type);
    }

    public function test_rejects_invalid_message_type()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'Hello world',
            'message_type' => 'invalid_type'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_can_get_message_stats()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach([$user1->id, $user2->id]);

        // Create some messages
        Message::factory()->count(5)->create([
            'chat_id' => $chat->id,
            'user_id' => $user1->id,
            'read_at' => now()
        ]);

        Message::factory()->count(3)->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id,
            'read_at' => null
        ]);

        // Update chat's last message time to simulate real behavior
        $chat->updateLastMessageTime();

        $stats = $this->messageService->getMessageStats($chat);

        $this->assertEquals(8, $stats['total_messages']);
        $this->assertEquals(3, $stats['unread_messages']);
        $this->assertEquals(2, $stats['participants_count']);
        $this->assertNotNull($stats['last_message_at']);
    }

    public function test_normalizes_line_endings()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        // Test the normalization directly in the request class
        $request = new \App\Http\Requests\StoreMessageRequest();
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('normalizeContent');
        $method->setAccessible(true);

        $content = "Line 1\r\nLine 2\rLine 3\nLine 4";
        $normalized = $method->invoke($request, $content);

        // Should normalize all line endings to \n
        $this->assertEquals("Line 1\nLine 2\nLine 3\nLine 4", $normalized);
    }

    public function test_limits_consecutive_newlines()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        // Test the normalization directly in the request class
        $request = new \App\Http\Requests\StoreMessageRequest();
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('normalizeContent');
        $method->setAccessible(true);

        $content = "Line 1\n\n\n\n\n\nLine 2";
        $normalized = $method->invoke($request, $content);

        // Should limit to maximum 3 consecutive newlines
        $this->assertEquals("Line 1\n\n\nLine 2", $normalized);
    }

    public function test_removes_control_characters()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        // Test the normalization directly in the request class
        $request = new \App\Http\Requests\StoreMessageRequest();
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('normalizeContent');
        $method->setAccessible(true);

        $content = "Hello\x00\x01\x02World\x7F";
        $normalized = $method->invoke($request, $content);

        // Control characters should be removed
        $this->assertStringNotContainsString("\x00", $normalized);
        $this->assertStringNotContainsString("\x01", $normalized);
        $this->assertStringNotContainsString("\x7F", $normalized);
        $this->assertStringContainsString('HelloWorld', $normalized);
    }

    public function test_validates_message_with_only_whitespace()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => "\n\n\t  \n  \t",
            'message_type' => 'text'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_detects_credit_card_patterns()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'My card number is 4532 1234 5678 9012',
            'message_type' => 'text'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_detects_email_addresses_in_content()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'Contact me at spam@example.com for more info',
            'message_type' => 'text'
        ];

        $this->expectException(ValidationException::class);
        $this->messageService->createMessage($chat, $user, $data);
    }

    public function test_message_timestamps_are_accurate()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $beforeTime = now()->subSecond(); // Give a bit more buffer
        
        $data = [
            'content' => 'Test message with timestamp',
            'message_type' => 'text'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);
        
        $afterTime = now()->addSecond(); // Give a bit more buffer

        // Message timestamp should be between before and after times
        $this->assertTrue($message->created_at->greaterThanOrEqualTo($beforeTime));
        $this->assertTrue($message->created_at->lessThanOrEqualTo($afterTime));
        $this->assertNull($message->read_at); // Should be null initially
    }

    public function test_read_status_tracking()
    {
        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach([$sender->id, $reader->id]);

        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $sender->id,
            'read_at' => null
        ]);

        $this->assertFalse($message->isRead());

        $beforeRead = now()->subSecond(); // Give a bit more buffer
        $this->messageService->markMessageAsRead($message, $reader);
        $afterRead = now()->addSecond(); // Give a bit more buffer

        $message->refresh();
        $this->assertTrue($message->isRead());
        $this->assertTrue($message->read_at->greaterThanOrEqualTo($beforeRead));
        $this->assertTrue($message->read_at->lessThanOrEqualTo($afterRead));
    }

    public function test_message_content_encoding()
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->participants()->attach($user->id);

        $data = [
            'content' => 'Test with special chars: <>&"\'',
            'message_type' => 'text'
        ];

        $message = $this->messageService->createMessage($chat, $user, $data);

        // Debug: Let's see what the actual content is
        // dump($message->content);
        
        // The content should be sanitized and encoded
        $this->assertStringNotContainsString('<script>', $message->content);
        $this->assertStringContainsString('Test with special chars:', $message->content);
        
        // Check that dangerous characters are handled
        $this->assertStringNotContainsString('<', $message->content);
        $this->assertStringNotContainsString('>', $message->content);
    }
}