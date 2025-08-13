<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
        'message_type',
        'read_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the chat this message belongs to.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this message has been read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Mark this message as read.
     */
    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if this is a text message.
     */
    public function isText(): bool
    {
        return $this->message_type === 'text';
    }

    /**
     * Check if this is an image message.
     */
    public function isImage(): bool
    {
        return $this->message_type === 'image';
    }

    /**
     * Check if this is a file message.
     */
    public function isFile(): bool
    {
        return $this->message_type === 'file';
    }

    /**
     * Get formatted message content based on type.
     */
    public function getFormattedContentAttribute(): string
    {
        switch ($this->message_type) {
            case 'image':
                return '[Image]';
            case 'file':
                return '[File]';
            default:
                return $this->content;
        }
    }

    /**
     * Scope to get unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get messages for a specific chat.
     */
    public function scopeForChat($query, Chat $chat)
    {
        return $query->where('chat_id', $chat->id);
    }

    /**
     * Scope to get messages from a specific user.
     */
    public function scopeFromUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to get recent messages.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update chat's last_message_at when a new message is created
        static::created(function ($message) {
            $message->chat->updateLastMessageTime();
        });
    }
}