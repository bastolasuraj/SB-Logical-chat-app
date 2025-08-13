<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    /**
     * Get all chats for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $chats = Chat::forUser($user)
            ->with([
                'participants:id,name,email,avatar,last_seen_at',
                'lastMessage' => function ($query) {
                    $query->select('id', 'chat_id', 'user_id', 'content', 'message_type', 'created_at');
                },
                'lastMessage.user:id,name'
            ])
            ->orderByLastMessage()
            ->get()
            ->map(function ($chat) use ($user) {
                return [
                    'id' => $chat->id,
                    'type' => $chat->type,
                    'name' => $chat->name,
                    'participants' => $chat->participants,
                    'last_message' => $chat->lastMessage,
                    'last_message_at' => $chat->last_message_at,
                    'unread_count' => $chat->getUnreadCountForUser($user),
                    'other_participant' => $chat->isPrivate() ? $chat->getOtherParticipant($user) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $chats
        ]);
    }

    /**
     * Get a specific chat with its details.
     */
    public function show(Chat $chat): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        $chat->load([
            'participants:id,name,email,avatar,last_seen_at'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chat->id,
                'type' => $chat->type,
                'name' => $chat->name,
                'participants' => $chat->participants,
                'last_message_at' => $chat->last_message_at,
                'unread_count' => $chat->getUnreadCountForUser($user),
                'other_participant' => $chat->isPrivate() ? $chat->getOtherParticipant($user) : null,
            ]
        ]);
    }

    /**
     * Create a new chat (private chat with another user).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => ['sometimes', Rule::in(['private', 'group'])],
            'name' => 'nullable|string|max:255'
        ]);

        $user = Auth::user();
        $otherUserId = $request->user_id;
        $type = $request->type ?? 'private';

        // Check if user is trying to create chat with themselves
        if ($user->id == $otherUserId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot create a chat with yourself.'
            ], 400);
        }

        // For private chats, check if chat already exists
        if ($type === 'private') {
            $existingChat = Chat::where('type', 'private')
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->whereHas('participants', function ($query) use ($otherUserId) {
                    $query->where('users.id', $otherUserId);
                })
                ->first();

            if ($existingChat) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $existingChat->id,
                        'type' => $existingChat->type,
                        'name' => $existingChat->name,
                        'participants' => $existingChat->participants,
                        'last_message_at' => $existingChat->last_message_at,
                        'other_participant' => $existingChat->getOtherParticipant($user),
                    ],
                    'message' => 'Chat already exists.'
                ]);
            }
        }

        // Create new chat
        DB::beginTransaction();
        try {
            $chat = Chat::create([
                'type' => $type,
                'name' => $request->name,
                'last_message_at' => now(),
            ]);

            // Add participants
            $chat->participants()->attach([$user->id, $otherUserId], [
                'joined_at' => now()
            ]);

            $chat->load([
                'participants:id,name,email,avatar,last_seen_at'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $chat->id,
                    'type' => $chat->type,
                    'name' => $chat->name,
                    'participants' => $chat->participants,
                    'last_message_at' => $chat->last_message_at,
                    'unread_count' => 0,
                    'other_participant' => $chat->isPrivate() ? $chat->getOtherParticipant($user) : null,
                ],
                'message' => 'Chat created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat.'
            ], 500);
        }
    }

    /**
     * Mark all messages in a chat as read for the authenticated user.
     */
    public function markAsRead(Chat $chat): JsonResponse
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
            $updatedCount = $this->messageService->markChatAsRead($chat, $user);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read.',
                'data' => [
                    'messages_marked' => $updatedCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read.'
            ], 500);
        }
    }

    /**
     * Delete a chat (only for group chats or if user is admin).
     */
    public function destroy(Chat $chat): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user is a participant in this chat
        if (!$chat->participants()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.'
            ], 403);
        }

        // For now, only allow deletion of group chats
        // Private chats can be "archived" by removing the user from participants
        if ($chat->isPrivate()) {
            return response()->json([
                'success' => false,
                'message' => 'Private chats cannot be deleted.'
            ], 400);
        }

        try {
            $chat->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Chat deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chat.'
            ], 500);
        }
    }
}