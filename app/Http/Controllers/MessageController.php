<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Services\MessageService;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    /**
     * Get messages for a specific chat with pagination.
     */
    public function index(Chat $chat, Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);

        $messages = $chat->messages()
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Reverse the order for display (oldest first)
        $messagesData = $messages->getCollection()->reverse()->values();

        return response()->json([
            'success' => true,
            'data' => $messagesData,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'has_more_pages' => $messages->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get older messages for lazy loading.
     */
    public function older(Chat $chat, Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        $request->validate([
            'before_id' => 'required|integer|exists:messages,id',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $beforeId = $request->get('before_id');
        $limit = $request->get('limit', 50);

        $messages = $chat->messages()
            ->with('user:id,name,avatar')
            ->where('id', '<', $beforeId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $hasMore = $chat->messages()
            ->where('id', '<', $messages->first()?->id ?? $beforeId)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => $messages,
            'has_more' => $hasMore
        ]);
    }

    /**
     * Send a new message to a chat.
     */
    public function store(Chat $chat, StoreMessageRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        try {
            $message = $this->messageService->createMessage($chat, $user, $request->validated());
            $message->load('user:id,name,avatar');

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Message sent successfully.'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message.'
            ], 500);
        }
    }

    /**
     * Get a specific message.
     */
    public function show(Chat $chat, Message $message): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        // Check if message belongs to this chat
        if ($message->chat_id !== $chat->id) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found in this chat.'
            ], 404);
        }

        $message->load('user:id,name,avatar');

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Update a message (only by the sender).
     */
    public function update(Chat $chat, Message $message, UpdateMessageRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        // Check if message belongs to this chat
        if ($message->chat_id !== $chat->id) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found in this chat.'
            ], 404);
        }

        // Check if user is the sender of this message
        if ($message->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own messages.'
            ], 403);
        }

        try {
            $message = $this->messageService->updateMessage($message, $request->validated());
            $message->load('user:id,name,avatar');

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Message updated successfully.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update message.'
            ], 500);
        }
    }

    /**
     * Delete a message (only by the sender).
     */
    public function destroy(Chat $chat, Message $message): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        // Check if message belongs to this chat
        if ($message->chat_id !== $chat->id) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found in this chat.'
            ], 404);
        }

        try {
            $this->messageService->deleteMessage($message, $user);
            
            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message.'
            ], 500);
        }
    }

    /**
     * Mark a specific message as read.
     */
    public function markAsRead(Chat $chat, Message $message): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        // Check if message belongs to this chat
        if ($message->chat_id !== $chat->id) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found in this chat.'
            ], 404);
        }

        try {
            $this->messageService->markMessageAsRead($message, $user);

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark message as read.'
            ], 500);
        }
    }
}