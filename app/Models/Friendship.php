<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    /**
     * Get the user who sent the friend request.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user who received the friend request.
     */
    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /**
     * Check if the friendship is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the friendship is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the friendship is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Accept the friend request.
     */
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Decline the friend request.
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Get the other user in the friendship relationship.
     */
    public function getOtherUser(User $user): ?User
    {
        if ($this->requester_id === $user->id) {
            return $this->addressee;
        } elseif ($this->addressee_id === $user->id) {
            return $this->requester;
        }

        return null;
    }

    /**
     * Check if a user is involved in this friendship.
     */
    public function involvesUser(User $user): bool
    {
        return $this->requester_id === $user->id || $this->addressee_id === $user->id;
    }

    /**
     * Scope to get pending friend requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted friendships.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get declined friendships.
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Scope to get friendships for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)
              ->orWhere('addressee_id', $user->id);
        });
    }

    /**
     * Scope to get friend requests sent by a user.
     */
    public function scopeSentBy($query, User $user)
    {
        return $query->where('requester_id', $user->id);
    }

    /**
     * Scope to get friend requests received by a user.
     */
    public function scopeReceivedBy($query, User $user)
    {
        return $query->where('addressee_id', $user->id);
    }

    /**
     * Check if a friendship exists between two users.
     */
    public static function existsBetween(User $user1, User $user2): bool
    {
        return static::where(function ($query) use ($user1, $user2) {
            $query->where('requester_id', $user1->id)
                  ->where('addressee_id', $user2->id);
        })->orWhere(function ($query) use ($user1, $user2) {
            $query->where('requester_id', $user2->id)
                  ->where('addressee_id', $user1->id);
        })->exists();
    }

    /**
     * Get friendship between two users.
     */
    public static function between(User $user1, User $user2): ?self
    {
        return static::where(function ($query) use ($user1, $user2) {
            $query->where('requester_id', $user1->id)
                  ->where('addressee_id', $user2->id);
        })->orWhere(function ($query) use ($user1, $user2) {
            $query->where('requester_id', $user2->id)
                  ->where('addressee_id', $user1->id);
        })->first();
    }
}