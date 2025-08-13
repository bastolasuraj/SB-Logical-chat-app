<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_has_messages_relationship()
    {
        $chat = Chat::factory()->create();
        $user = User::factory()->create();
        
        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($chat->messages->contains($message));
        $this->assertInstanceOf(Message::class, $chat->messages->first());
    }

    public function test_chat_has_participants_relationship()
    {
        $chat = Chat::factory()->create();
        $user = User::factory()->create();
        
        $chat->participants()->attach($user->id, ['joined_at' => now()]);

        $this->assertTrue($chat->participants->contains($user));
        $this->assertInstanceOf(User::class, $chat->participants->first());
    }

    public function test_chat_has_last_message_relationship()
    {
        $chat = Chat::factory()->create();
        $user = User::factory()->create();
        
        $oldMessage = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'created_at' => now()->subHour(),
        ]);
        
        $newMessage = Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->assertEquals($newMessage->id, $chat->lastMessage->id);
    }

    public function test_is_private_method()
    {
        $privateChat = Chat::factory()->create(['type' => 'private']);
        $groupChat = Chat::factory()->create(['type' => 'group']);

        $this->assertTrue($privateChat->isPrivate());
        $this->assertFalse($groupChat->isPrivate());
    }

    public function test_is_group_method()
    {
        $privateChat = Chat::factory()->create(['type' => 'private']);
        $groupChat = Chat::factory()->create(['type' => 'group']);

        $this->assertFalse($privateChat->isGroup());
        $this->assertTrue($groupChat->isGroup());
    }

    public function test_get_other_participant_in_private_chat()
    {
        $chat = Chat::factory()->create(['type' => 'private']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $chat->participants()->attach([$user1->id, $user2->id]);

        $otherParticipant = $chat->getOtherParticipant($user1);
        
        $this->assertEquals($user2->id, $otherParticipant->id);
    }

    public function test_get_other_participant_returns_null_for_group_chat()
    {
        $chat = Chat::factory()->create(['type' => 'group']);
        $user = User::factory()->create();

        $otherParticipant = $chat->getOtherParticipant($user);
        
        $this->assertNull($otherParticipant);
    }

    public function test_get_unread_count_for_user()
    {
        $chat = Chat::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create read and unread messages
        Message::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id,
            'read_at' => now(),
        ]);
        
        Message::factory()->count(3)->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id,
            'read_at' => null,
        ]);

        $unreadCount = $chat->getUnreadCountForUser($user1);
        
        $this->assertEquals(3, $unreadCount);
    }

    public function test_mark_as_read_for_user()
    {
        $chat = Chat::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Message::factory()->count(3)->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id,
            'read_at' => null,
        ]);

        $chat->markAsReadForUser($user1);
        
        $unreadCount = $chat->getUnreadCountForUser($user1);
        $this->assertEquals(0, $unreadCount);
    }

    public function test_update_last_message_time()
    {
        $chat = Chat::factory()->create(['last_message_at' => null]);
        
        $chat->updateLastMessageTime();
        
        $this->assertNotNull($chat->fresh()->last_message_at);
    }

    public function test_for_user_scope()
    {
        $user = User::factory()->create();
        $chat1 = Chat::factory()->create();
        $chat2 = Chat::factory()->create();
        
        $chat1->participants()->attach($user->id);
        
        $userChats = Chat::forUser($user)->get();
        
        $this->assertTrue($userChats->contains($chat1));
        $this->assertFalse($userChats->contains($chat2));
    }

    public function test_order_by_last_message_scope()
    {
        $chat1 = Chat::factory()->create(['last_message_at' => now()->subHour()]);
        $chat2 = Chat::factory()->create(['last_message_at' => now()]);
        
        $orderedChats = Chat::orderByLastMessage()->get();
        
        $this->assertEquals($chat2->id, $orderedChats->first()->id);
        $this->assertEquals($chat1->id, $orderedChats->last()->id);
    }
}