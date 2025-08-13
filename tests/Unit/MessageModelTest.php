<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_belongs_to_chat()
    {
        $chat = Chat::factory()->create();
        $user = User::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Chat::class, $message->chat);
        $this->assertEquals($chat->id, $message->chat->id);
    }

    public function test_message_belongs_to_user()
    {
        $chat = Chat::factory()->create();
        $user = User::factory()->create();
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $message->user);
        $this->assertEquals($user->id, $message->user->id);
    }

    public function test_is_read_method()
    {
        $readMessage = Message::factory()->create(['read_at' => now()]);
        $unreadMessage = Message::factory()->create(['read_at' => null]);

        $this->assertTrue($readMessage->isRead());
        $this->assertFalse($unreadMessage->isRead());
    }

    public function test_mark_as_read_method()
    {
        $message = Message::factory()->create(['read_at' => null]);
        
        $this->assertFalse($message->isRead());
        
        $message->markAsRead();
        
        $this->assertTrue($message->fresh()->isRead());
    }

    public function test_mark_as_read_does_not_update_already_read_message()
    {
        $originalReadTime = now()->subHour();
        $message = Message::factory()->create(['read_at' => $originalReadTime]);
        
        $message->markAsRead();
        
        $this->assertEquals($originalReadTime->timestamp, $message->fresh()->read_at->timestamp);
    }

    public function test_message_type_methods()
    {
        $textMessage = Message::factory()->create(['message_type' => 'text']);
        $imageMessage = Message::factory()->create(['message_type' => 'image']);
        $fileMessage = Message::factory()->create(['message_type' => 'file']);

        $this->assertTrue($textMessage->isText());
        $this->assertFalse($textMessage->isImage());
        $this->assertFalse($textMessage->isFile());

        $this->assertFalse($imageMessage->isText());
        $this->assertTrue($imageMessage->isImage());
        $this->assertFalse($imageMessage->isFile());

        $this->assertFalse($fileMessage->isText());
        $this->assertFalse($fileMessage->isImage());
        $this->assertTrue($fileMessage->isFile());
    }

    public function test_formatted_content_attribute()
    {
        $textMessage = Message::factory()->create([
            'message_type' => 'text',
            'content' => 'Hello world'
        ]);
        $imageMessage = Message::factory()->create(['message_type' => 'image']);
        $fileMessage = Message::factory()->create(['message_type' => 'file']);

        $this->assertEquals('Hello world', $textMessage->formatted_content);
        $this->assertEquals('[Image]', $imageMessage->formatted_content);
        $this->assertEquals('[File]', $fileMessage->formatted_content);
    }

    public function test_unread_scope()
    {
        $readMessage = Message::factory()->create(['read_at' => now()]);
        $unreadMessage = Message::factory()->create(['read_at' => null]);

        $unreadMessages = Message::unread()->get();

        $this->assertFalse($unreadMessages->contains($readMessage));
        $this->assertTrue($unreadMessages->contains($unreadMessage));
    }

    public function test_for_chat_scope()
    {
        $chat1 = Chat::factory()->create();
        $chat2 = Chat::factory()->create();
        $user = User::factory()->create();
        
        $message1 = Message::factory()->create(['chat_id' => $chat1->id, 'user_id' => $user->id]);
        $message2 = Message::factory()->create(['chat_id' => $chat2->id, 'user_id' => $user->id]);

        $chat1Messages = Message::forChat($chat1)->get();

        $this->assertTrue($chat1Messages->contains($message1));
        $this->assertFalse($chat1Messages->contains($message2));
    }

    public function test_from_user_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $chat = Chat::factory()->create();
        
        $message1 = Message::factory()->create(['user_id' => $user1->id, 'chat_id' => $chat->id]);
        $message2 = Message::factory()->create(['user_id' => $user2->id, 'chat_id' => $chat->id]);

        $user1Messages = Message::fromUser($user1)->get();

        $this->assertTrue($user1Messages->contains($message1));
        $this->assertFalse($user1Messages->contains($message2));
    }

    public function test_recent_scope()
    {
        $chat = Chat::factory()->create();
        $user = User::factory()->create();
        
        $oldMessage = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);
        
        $newMessage = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $recentMessages = Message::recent(1)->get();

        $this->assertEquals(1, $recentMessages->count());
        $this->assertEquals($newMessage->id, $recentMessages->first()->id);
    }

    public function test_message_creation_updates_chat_last_message_time()
    {
        $chat = Chat::factory()->create(['last_message_at' => null]);
        $user = User::factory()->create();
        
        $this->assertNull($chat->last_message_at);
        
        Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
        ]);
        
        $this->assertNotNull($chat->fresh()->last_message_at);
    }
}