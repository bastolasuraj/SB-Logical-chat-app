<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_user_with_required_fields()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function it_hashes_password_automatically()
    {
        $user = User::factory()->create([
            'password' => 'plaintext-password',
        ]);

        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(Hash::check('plaintext-password', $user->password));
    }

    /** @test */
    public function it_can_have_an_avatar()
    {
        $user = User::factory()->withAvatar()->create();

        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('.jpg', $user->avatar);
    }

    /** @test */
    public function it_can_track_last_seen_timestamp()
    {
        $user = User::factory()->create([
            'last_seen_at' => now()->subMinutes(10),
        ]);

        $this->assertNotNull($user->last_seen_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->last_seen_at);
    }

    /** @test */
    public function it_can_determine_if_user_is_online()
    {
        // Online user (seen within 5 minutes)
        $onlineUser = User::factory()->create([
            'last_seen_at' => now()->subMinutes(2),
        ]);

        // Offline user (seen more than 5 minutes ago)
        $offlineUser = User::factory()->create([
            'last_seen_at' => now()->subMinutes(10),
        ]);

        // User with no last_seen_at
        $neverSeenUser = User::factory()->create([
            'last_seen_at' => null,
        ]);

        $this->assertTrue($onlineUser->isOnline());
        $this->assertFalse($offlineUser->isOnline());
        $this->assertFalse($neverSeenUser->isOnline());
    }

    /** @test */
    public function it_can_update_last_seen_timestamp()
    {
        $user = User::factory()->create([
            'last_seen_at' => now()->subHour(),
        ]);

        $oldTimestamp = $user->last_seen_at->copy();
        
        // Sleep for a moment to ensure timestamp difference
        sleep(1);
        
        $user->updateLastSeen();
        $user->refresh();

        $this->assertTrue($user->last_seen_at->greaterThan($oldTimestamp));
        $this->assertTrue($user->last_seen_at->diffInSeconds(now()) < 10);
    }

    /** @test */
    public function it_generates_avatar_url_for_uploaded_avatar()
    {
        $user = User::factory()->create([
            'avatar' => 'test-avatar.jpg',
        ]);

        $expectedUrl = asset('storage/avatars/test-avatar.jpg');
        $this->assertEquals($expectedUrl, $user->avatar_url);
    }

    /** @test */
    public function it_generates_gravatar_url_when_no_avatar()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'avatar' => null,
        ]);

        $expectedHash = md5(strtolower(trim('test@example.com')));
        $expectedUrl = "https://www.gravatar.com/avatar/{$expectedHash}?d=identicon&s=150";
        
        $this->assertEquals($expectedUrl, $user->avatar_url);
    }

    /** @test */
    public function it_implements_must_verify_email_interface()
    {
        $user = new User();
        
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class, $user);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $user = new User();
        
        $expectedFillable = ['name', 'email', 'password', 'avatar', 'last_seen_at'];
        
        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->create();
        
        $userArray = $user->toArray();
        
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /** @test */
    public function it_casts_timestamps_correctly()
    {
        $user = User::factory()->create([
            'email_verified_at' => '2023-01-01 12:00:00',
            'last_seen_at' => '2023-01-01 13:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->last_seen_at);
    }
}