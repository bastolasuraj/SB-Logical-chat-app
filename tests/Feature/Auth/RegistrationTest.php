<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Registration successful. Please check your email for verification.',
                ])
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at'
                    ]
                ]);

        // Assert user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert password was hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        // Assert email is not verified yet
        $this->assertNull($user->email_verified_at);

        // Assert verification email was sent
        Notification::assertSentTo($user, EmailVerificationNotification::class);
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Validation failed',
                ])
                ->assertJsonValidationErrors(['name', 'email', 'password']);

        // Assert no user was created
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        // Create existing user
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Assert only one user exists
        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_can_verify_email_with_code(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // Simulate stored verification code
        Cache::put("email_verification_code_{$user->id}", '123456', now()->addHour());

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Email verified successfully',
                ]);

        // Assert user email is now verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Assert verification code was removed from cache
        $this->assertNull(Cache::get("email_verification_code_{$user->id}"));
    }

    public function test_user_can_verify_email_with_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // Simulate stored verification token
        $token = 'test-verification-token';
        Cache::put("email_verification_token_{$user->id}", $token, now()->addHour());

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => $user->email,
            'token' => $token,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Email verified successfully',
                ]);

        // Assert user email is now verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Assert verification token was removed from cache
        $this->assertNull(Cache::get("email_verification_token_{$user->id}"));
    }

    public function test_email_verification_fails_with_invalid_code(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // Simulate stored verification code
        Cache::put("email_verification_code_{$user->id}", '123456', now()->addHour());

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => $user->email,
            'code' => '654321', // Wrong code
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Invalid or expired verification code/token',
                ]);

        // Assert user email is still not verified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_fails_for_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'nonexistent@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'User not found',
                ]);
    }

    public function test_email_verification_fails_for_already_verified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Email already verified',
                ]);
    }

    public function test_user_can_resend_verification_email(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Verification email sent successfully',
                ]);

        // Assert verification email was sent
        Notification::assertSentTo($user, EmailVerificationNotification::class);
    }

    public function test_resend_verification_fails_for_verified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Email already verified',
                ]);
    }

    public function test_resend_verification_is_rate_limited(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // Simulate recent verification email sent
        Cache::put("email_verification_rate_limit_{$user->id}", now()->toISOString(), now()->addMinutes(5));

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'message' => 'Please wait before requesting another verification email',
                ]);
    }

    public function test_verification_requires_either_code_or_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => $user->email,
            // Missing both code and token
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
    }
}