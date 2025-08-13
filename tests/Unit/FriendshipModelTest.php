<?php

namespace Tests\Unit;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_friendship_belongs_to_requester()
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        $this->assertInstanceOf(User::class, $friendship->requester);
        $this->assertEquals($requester->id, $friendship->requester->id);
    }

    public function test_friendship_belongs_to_addressee()
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        $this->assertInstanceOf(User::class, $friendship->addressee);
        $this->assertEquals($addressee->id, $friendship->addressee->id);
    }

    public function test_status_check_methods()
    {
        $pendingFriendship = Friendship::factory()->create(['status' => 'pending']);
        $acceptedFriendship = Friendship::factory()->create(['status' => 'accepted']);
        $declinedFriendship = Friendship::factory()->create(['status' => 'declined']);

        $this->assertTrue($pendingFriendship->isPending());
        $this->assertFalse($pendingFriendship->isAccepted());
        $this->assertFalse($pendingFriendship->isDeclined());

        $this->assertFalse($acceptedFriendship->isPending());
        $this->assertTrue($acceptedFriendship->isAccepted());
        $this->assertFalse($acceptedFriendship->isDeclined());

        $this->assertFalse($declinedFriendship->isPending());
        $this->assertFalse($declinedFriendship->isAccepted());
        $this->assertTrue($declinedFriendship->isDeclined());
    }

    public function test_accept_method()
    {
        $friendship = Friendship::factory()->create(['status' => 'pending']);
        
        $friendship->accept();
        
        $this->assertTrue($friendship->fresh()->isAccepted());
    }

    public function test_decline_method()
    {
        $friendship = Friendship::factory()->create(['status' => 'pending']);
        
        $friendship->decline();
        
        $this->assertTrue($friendship->fresh()->isDeclined());
    }

    public function test_get_other_user_method()
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        $otherUserFromRequester = $friendship->getOtherUser($requester);
        $otherUserFromAddressee = $friendship->getOtherUser($addressee);

        $this->assertEquals($addressee->id, $otherUserFromRequester->id);
        $this->assertEquals($requester->id, $otherUserFromAddressee->id);
    }

    public function test_get_other_user_returns_null_for_uninvolved_user()
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $otherUser = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        $result = $friendship->getOtherUser($otherUser);

        $this->assertNull($result);
    }

    public function test_involves_user_method()
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $otherUser = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        $this->assertTrue($friendship->involvesUser($requester));
        $this->assertTrue($friendship->involvesUser($addressee));
        $this->assertFalse($friendship->involvesUser($otherUser));
    }

    public function test_pending_scope()
    {
        $pendingFriendship = Friendship::factory()->create(['status' => 'pending']);
        $acceptedFriendship = Friendship::factory()->create(['status' => 'accepted']);

        $pendingFriendships = Friendship::pending()->get();

        $this->assertTrue($pendingFriendships->contains($pendingFriendship));
        $this->assertFalse($pendingFriendships->contains($acceptedFriendship));
    }

    public function test_accepted_scope()
    {
        $pendingFriendship = Friendship::factory()->create(['status' => 'pending']);
        $acceptedFriendship = Friendship::factory()->create(['status' => 'accepted']);

        $acceptedFriendships = Friendship::accepted()->get();

        $this->assertFalse($acceptedFriendships->contains($pendingFriendship));
        $this->assertTrue($acceptedFriendships->contains($acceptedFriendship));
    }

    public function test_declined_scope()
    {
        $pendingFriendship = Friendship::factory()->create(['status' => 'pending']);
        $declinedFriendship = Friendship::factory()->create(['status' => 'declined']);

        $declinedFriendships = Friendship::declined()->get();

        $this->assertFalse($declinedFriendships->contains($pendingFriendship));
        $this->assertTrue($declinedFriendships->contains($declinedFriendship));
    }

    public function test_for_user_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        $friendship1 = Friendship::factory()->create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
        ]);
        
        $friendship2 = Friendship::factory()->create([
            'requester_id' => $user3->id,
            'addressee_id' => $user1->id,
        ]);
        
        $friendship3 = Friendship::factory()->create([
            'requester_id' => $user2->id,
            'addressee_id' => $user3->id,
        ]);

        $user1Friendships = Friendship::forUser($user1)->get();

        $this->assertTrue($user1Friendships->contains($friendship1));
        $this->assertTrue($user1Friendships->contains($friendship2));
        $this->assertFalse($user1Friendships->contains($friendship3));
    }

    public function test_sent_by_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $sentFriendship = Friendship::factory()->create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
        ]);
        
        $receivedFriendship = Friendship::factory()->create([
            'requester_id' => $user2->id,
            'addressee_id' => $user1->id,
        ]);

        $sentFriendships = Friendship::sentBy($user1)->get();

        $this->assertTrue($sentFriendships->contains($sentFriendship));
        $this->assertFalse($sentFriendships->contains($receivedFriendship));
    }

    public function test_received_by_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $sentFriendship = Friendship::factory()->create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
        ]);
        
        $receivedFriendship = Friendship::factory()->create([
            'requester_id' => $user2->id,
            'addressee_id' => $user1->id,
        ]);

        $receivedFriendships = Friendship::receivedBy($user1)->get();

        $this->assertFalse($receivedFriendships->contains($sentFriendship));
        $this->assertTrue($receivedFriendships->contains($receivedFriendship));
    }

    public function test_exists_between_static_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        Friendship::factory()->create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
        ]);

        $this->assertTrue(Friendship::existsBetween($user1, $user2));
        $this->assertTrue(Friendship::existsBetween($user2, $user1)); // Should work both ways
        $this->assertFalse(Friendship::existsBetween($user1, $user3));
    }

    public function test_between_static_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        $friendship = Friendship::factory()->create([
            'requester_id' => $user1->id,
            'addressee_id' => $user2->id,
        ]);

        $foundFriendship1 = Friendship::between($user1, $user2);
        $foundFriendship2 = Friendship::between($user2, $user1); // Should work both ways
        $notFoundFriendship = Friendship::between($user1, $user3);

        $this->assertEquals($friendship->id, $foundFriendship1->id);
        $this->assertEquals($friendship->id, $foundFriendship2->id);
        $this->assertNull($notFoundFriendship);
    }
}