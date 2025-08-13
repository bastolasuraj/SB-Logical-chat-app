<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'last_message_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Get all messages in this chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in this chat.
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all participants in this chat.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants')
                    ->withTimestamps()
                    ->withPivot('joined_at');
    }

    /**
     * Check if this is a private chat.
     */
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    /**
     * Check if this is a group chat.
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Get the other participant in a private chat.
     */
    public function getOtherParticipant(User $user): ?User
    {
        if (!$this->isPrivate()) {
            return null;
        }

        return $this->participants()->where('users.id', '!=', $user->id)->first();
    }

    /**
     * Get unread message count for a specific user.
     */
    public function getUnreadCountForUser(User $user): int
    {
        return $this->messages()
                    ->where('user_id', '!=', $user->id)
                    ->whereNull('read_at')
                    ->count();
    }

    /**
     * Mark all messages as read for a specific user.
     */
    public function markAsReadForUser(User $user): void
    {
        $this->messages()
             ->where('user_id', '!=', $user->id)
             ->whereNull('read_at')
             ->update(['read_at' => now()]);
    }

    /**
     * Update the last message timestamp.
     */
    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Scope to get chats for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('participants', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }

    /**
     * Scope to order chats by last message time.
     */
    public function scopeOrderByLastMessage($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }
}