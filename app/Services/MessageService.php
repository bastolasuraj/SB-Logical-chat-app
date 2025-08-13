<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MessageService
{
    /**
     * Create and store a new message with validation and sanitization.
     */
    public function createMessage(Chat $chat, User $user, array $data): Message
    {
        // Validate message content
        $this->validateMessageContent($data);
        
        // Sanitize content
        $sanitizedContent = $this->sanitizeContent($data['content'], $data['message_type'] ?? 'text');
        
        DB::beginTransaction();
        try {
            // Create the message
            $message = Message::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'content' => $sanitizedContent,
                'message_type' => $data['message_type'] ?? 'text',
            ]);

            // Update chat's last message timestamp
            $chat->updateLastMessageTime();

            // Log message creation for monitoring
            Log::info('Message created', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'message_type' => $message->message_type,
                'content_length' => strlen($sanitizedContent)
            ]);

            DB::commit();
            return $message;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create message', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing message with validation and sanitization.
     */
    public function updateMessage(Message $message, array $data): Message
    {
        // Validate message content
        $this->validateMessageContent($data);
        
        // Sanitize content
        $sanitizedContent = $this->sanitizeContent($data['content'], $message->message_type);
        
        try {
            $message->update([
                'content' => $sanitizedContent,
                'updated_at' => now()
            ]);

            // Log message update for monitoring
            Log::info('Message updated', [
                'message_id' => $message->id,
                'chat_id' => $message->chat_id,
                'user_id' => $message->user_id,
                'content_length' => strlen($sanitizedContent)
            ]);

            return $message;

        } catch (\Exception $e) {
            Log::error('Failed to update message', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mark a message as read with timestamp tracking.
     */
    public function markMessageAsRead(Message $message, User $user): void
    {
        // Users can't mark their own messages as read
        if ($message->user_id === $user->id) {
            throw ValidationException::withMessages([
                'message' => 'You cannot mark your own message as read.'
            ]);
        }

        // Only mark as read if not already read
        if (!$message->isRead()) {
            $message->update(['read_at' => now()]);
            
            Log::info('Message marked as read', [
                'message_id' => $message->id,
                'reader_user_id' => $user->id,
                'sender_user_id' => $message->user_id
            ]);
        }
    }

    /**
     * Mark all messages in a chat as read for a specific user.
     */
    public function markChatAsRead(Chat $chat, User $user): int
    {
        $updatedCount = $chat->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updatedCount > 0) {
            Log::info('Chat messages marked as read', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'messages_count' => $updatedCount
            ]);
        }

        return $updatedCount;
    }

    /**
     * Delete a message with proper authorization and logging.
     */
    public function deleteMessage(Message $message, User $user): void
    {
        // Verify user owns the message
        if ($message->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'message' => 'You can only delete your own messages.'
            ]);
        }

        try {
            $messageId = $message->id;
            $chatId = $message->chat_id;
            
            $message->delete();
            
            Log::info('Message deleted', [
                'message_id' => $messageId,
                'chat_id' => $chatId,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete message', [
                'message_id' => $message->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }   
 /**
     * Validate message content based on type and security requirements.
     */
    private function validateMessageContent(array $data): void
    {
        $content = $data['content'] ?? '';
        $messageType = $data['message_type'] ?? 'text';

        // Basic content validation
        if (empty(trim($content))) {
            throw ValidationException::withMessages([
                'content' => 'Message content cannot be empty.'
            ]);
        }

        // Length validation
        if (strlen($content) > 10000) {
            throw ValidationException::withMessages([
                'content' => 'Message content cannot exceed 10,000 characters.'
            ]);
        }

        // Type-specific validation
        switch ($messageType) {
            case 'text':
                $this->validateTextMessage($content);
                break;
            case 'image':
                $this->validateImageMessage($content);
                break;
            case 'file':
                $this->validateFileMessage($content);
                break;
            default:
                throw ValidationException::withMessages([
                    'message_type' => 'Invalid message type. Must be text, image, or file.'
                ]);
        }
    }

    /**
     * Validate text message content.
     */
    private function validateTextMessage(string $content): void
    {
        // Check for excessive whitespace
        if (strlen(trim($content)) < 1) {
            throw ValidationException::withMessages([
                'content' => 'Text message cannot be empty or contain only whitespace.'
            ]);
        }

        // Check for potential spam patterns
        if ($this->containsSpamPatterns($content)) {
            throw ValidationException::withMessages([
                'content' => 'Message content appears to contain spam or inappropriate content.'
            ]);
        }
    }

    /**
     * Validate image message content (URL or file reference).
     */
    private function validateImageMessage(string $content): void
    {
        // For now, treat as URL validation
        if (!filter_var($content, FILTER_VALIDATE_URL) && !$this->isValidFileReference($content)) {
            throw ValidationException::withMessages([
                'content' => 'Image message must contain a valid URL or file reference.'
            ]);
        }
    }

    /**
     * Validate file message content (URL or file reference).
     */
    private function validateFileMessage(string $content): void
    {
        // For now, treat as URL validation
        if (!filter_var($content, FILTER_VALIDATE_URL) && !$this->isValidFileReference($content)) {
            throw ValidationException::withMessages([
                'content' => 'File message must contain a valid URL or file reference.'
            ]);
        }
    }

    /**
     * Sanitize message content based on type.
     */
    private function sanitizeContent(string $content, string $messageType): string
    {
        switch ($messageType) {
            case 'text':
                return $this->sanitizeTextContent($content);
            case 'image':
            case 'file':
                return $this->sanitizeUrlContent($content);
            default:
                return $this->sanitizeTextContent($content);
        }
    }

    /**
     * Sanitize text content.
     */
    private function sanitizeTextContent(string $content): string
    {
        // Remove potentially dangerous HTML/script tags
        $content = strip_tags($content);
        
        // Encode special characters to prevent XSS
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        // Normalize whitespace but preserve line breaks
        $content = preg_replace('/[ \t]+/', ' ', $content); // Only normalize spaces and tabs
        $content = preg_replace('/\n{4,}/', "\n\n\n", $content); // Limit consecutive newlines
        
        // Trim only leading/trailing whitespace, preserve internal structure
        $content = trim($content);
        
        return $content;
    }

    /**
     * Sanitize URL content for image/file messages.
     */
    private function sanitizeUrlContent(string $content): string
    {
        // Basic URL sanitization
        $content = trim($content);
        
        // Remove any potential XSS attempts
        $content = filter_var($content, FILTER_SANITIZE_URL);
        
        return $content;
    }

    /**
     * Check if content contains spam patterns.
     */
    private function containsSpamPatterns(string $content): bool
    {
        $spamPatterns = [
            '/(.)\1{20,}/', // Repeated characters (20+ times)
            '/https?:\/\/[^\s]+\s+https?:\/\/[^\s]+\s+https?:\/\//', // Multiple URLs
            '/\b(buy now|click here|free money|win now|act now|limited time|urgent|congratulations)\b/i', // Common spam phrases
            '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/', // Credit card patterns
            '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', // Email addresses (suspicious in messages)
            '/\$\d+|\d+\s*USD|\d+\s*EUR|\d+\s*GBP/i', // Money amounts
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content is a valid file reference.
     */
    private function isValidFileReference(string $content): bool
    {
        // For now, just check if it looks like a file path
        return preg_match('/^[a-zA-Z0-9_\-\/\.]+\.(jpg|jpeg|png|gif|pdf|doc|docx|txt)$/i', $content);
    }

    /**
     * Get message statistics for monitoring.
     */
    public function getMessageStats(Chat $chat): array
    {
        return [
            'total_messages' => $chat->messages()->count(),
            'unread_messages' => $chat->messages()->whereNull('read_at')->count(),
            'messages_today' => $chat->messages()->whereDate('created_at', today())->count(),
            'last_message_at' => $chat->last_message_at,
            'participants_count' => $chat->participants()->count(),
        ];
    }
}