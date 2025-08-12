<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmailVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailVerificationService();
        Notification::fake();
    }

    public function test_send_verification_email_generates_code_and_token(): void
    {
        $user = User::factory()->create();

        $this->service->sendVerificationEmail($user);

        // Assert notification was sent
        Notification::assertSentTo($user, EmailVerificationNotification::class);

        // Assert code and token were stored in cache
        $this->assertNotNull(Cache::get("email_verification_code_{$user->id}"));
        $this->assertNotNull(Cache::get("email_verification_token_{$user->id}"));
        $this->assertNotNull(Cache::get("email_verification_rate_limit_{$user->id}"));
    }

    public function test_verify_code_with_valid_code(): void
    {
        $user = User::factory()->create();
        $code = '123456';
        
        Cache::put("email_verification_code_{$user->id}", $code, now()->addHour());

        $result = $this->service->verifyCode($user, $code);

        $this->assertTrue($result);
        
        // Assert code was removed from cache
        $this->assertNull(Cache::get("email_verification_code_{$user->id}"));
    }

    public function test_verify_code_with_invalid_code(): void
    {
        $user = User::factory()->create();
        
        Cache::put("email_verification_code_{$user->id}", '123456', now()->addHour());

        $result = $this->service->verifyCode($user, '654321');

        $this->assertFalse($result);
        
        // Assert code was not removed from cache
        $this->assertNotNull(Cache::get("email_verification_code_{$user->id}"));
    }

    public function test_verify_code_with_no_stored_code(): void
    {
        $user = User::factory()->create();

        $result = $this->service->verifyCode($user, '123456');

        $this->assertFalse($result);
    }

    public function test_verify_token_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = 'valid-token';
        
        Cache::put("email_verification_token_{$user->id}", $token, now()->addHour());

        $result = $this->service->verifyToken($user, $token);

        $this->assertTrue($result);
        
        // Assert token was removed from cache
        $this->assertNull(Cache::get("email_verification_token_{$user->id}"));
    }

    public function test_verify_token_with_invalid_token(): void
    {
        $user = User::factory()->create();
        
        Cache::put("email_verification_token_{$user->id}", 'valid-token', now()->addHour());

        $result = $this->service->verifyToken($user, 'invalid-token');

        $this->assertFalse($result);
        
        // Assert token was not removed from cache
        $this->assertNotNull(Cache::get("email_verification_token_{$user->id}"));
    }

    public function test_verify_token_with_no_stored_token(): void
    {
        $user = User::factory()->create();

        $result = $this->service->verifyToken($user, 'some-token');

        $this->assertFalse($result);
    }

    public function test_is_rate_limited_returns_false_when_no_limit(): void
    {
        $user = User::factory()->create();

        $result = $this->service->isRateLimited($user);

        $this->assertFalse($result);
    }

    public function test_is_rate_limited_returns_true_when_recently_sent(): void
    {
        $user = User::factory()->create();
        
        // Simulate email sent 30 seconds ago
        Cache::put(
            "email_verification_rate_limit_{$user->id}",
            now()->subSeconds(30)->toISOString(),
            now()->addMinutes(5)
        );

        $result = $this->service->isRateLimited($user);

        $this->assertTrue($result);
    }

    public function test_is_rate_limited_returns_false_when_enough_time_passed(): void
    {
        $user = User::factory()->create();
        
        // Simulate email sent 2 minutes ago
        Cache::put(
            "email_verification_rate_limit_{$user->id}",
            now()->subMinutes(2)->toISOString(),
            now()->addMinutes(5)
        );

        $result = $this->service->isRateLimited($user);

        $this->assertFalse($result);
    }

    public function test_notification_contains_code_and_url(): void
    {
        $user = User::factory()->create();

        $this->service->sendVerificationEmail($user);

        Notification::assertSentTo($user, EmailVerificationNotification::class, function ($notification) use ($user) {
            $arrayData = $notification->toArray($user);
            
            // Check that notification contains both code and URL
            $this->assertArrayHasKey('verification_code', $arrayData);
            $this->assertArrayHasKey('verification_url', $arrayData);
            $this->assertIsString($arrayData['verification_code']);
            $this->assertIsString($arrayData['verification_url']);
            $this->assertEquals(6, strlen($arrayData['verification_code']));
            
            return true;
        });
    }
}