<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerificationService
{
    /**
     * Send verification email with both code and link options
     */
    public function sendVerificationEmail(User $user): void
    {
        // Generate 6-digit verification code
        $code = $this->generateVerificationCode();
        
        // Generate verification token for link
        $token = $this->generateVerificationToken();
        
        // Store both code and token in cache with 1 hour expiration
        $this->storeVerificationCode($user, $code);
        $this->storeVerificationToken($user, $token);
        
        // Generate verification URL
        $verificationUrl = $this->generateVerificationUrl($user, $token);
        
        // Send notification with both code and link
        $user->notify(new EmailVerificationNotification($code, $verificationUrl));
        
        // Update rate limiting
        $this->updateRateLimit($user);
    }

    /**
     * Verify 6-digit code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $storedCode = Cache::get($this->getCodeCacheKey($user));
        
        if (!$storedCode || $storedCode !== $code) {
            return false;
        }
        
        // Remove code from cache after successful verification
        Cache::forget($this->getCodeCacheKey($user));
        
        return true;
    }

    /**
     * Verify token from link
     */
    public function verifyToken(User $user, string $token): bool
    {
        $storedToken = Cache::get($this->getTokenCacheKey($user));
        
        if (!$storedToken || $storedToken !== $token) {
            return false;
        }
        
        // Remove token from cache after successful verification
        Cache::forget($this->getTokenCacheKey($user));
        
        return true;
    }

    /**
     * Check if user is rate limited for sending verification emails
     */
    public function isRateLimited(User $user): bool
    {
        $lastSent = Cache::get($this->getRateLimitCacheKey($user));
        
        if (!$lastSent) {
            return false;
        }
        
        // Allow resending after 1 minute
        return Carbon::parse($lastSent)->addMinute()->isFuture();
    }

    /**
     * Generate 6-digit verification code
     */
    private function generateVerificationCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate verification token
     */
    private function generateVerificationToken(): string
    {
        return Str::random(64);
    }

    /**
     * Store verification code in cache
     */
    private function storeVerificationCode(User $user, string $code): void
    {
        Cache::put(
            $this->getCodeCacheKey($user),
            $code,
            now()->addHour()
        );
    }

    /**
     * Store verification token in cache
     */
    private function storeVerificationToken(User $user, string $token): void
    {
        Cache::put(
            $this->getTokenCacheKey($user),
            $token,
            now()->addHour()
        );
    }

    /**
     * Generate verification URL
     */
    private function generateVerificationUrl(User $user, string $token): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            [
                'email' => $user->email,
                'token' => $token
            ]
        );
    }

    /**
     * Update rate limiting timestamp
     */
    private function updateRateLimit(User $user): void
    {
        Cache::put(
            $this->getRateLimitCacheKey($user),
            now()->toISOString(),
            now()->addMinutes(5) // Keep rate limit info for 5 minutes
        );
    }

    /**
     * Get cache key for verification code
     */
    private function getCodeCacheKey(User $user): string
    {
        return "email_verification_code_{$user->id}";
    }

    /**
     * Get cache key for verification token
     */
    private function getTokenCacheKey(User $user): string
    {
        return "email_verification_token_{$user->id}";
    }

    /**
     * Get cache key for rate limiting
     */
    private function getRateLimitCacheKey(User $user): string
    {
        return "email_verification_rate_limit_{$user->id}";
    }
}