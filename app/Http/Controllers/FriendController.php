<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FriendController extends Controller
{
    /**
     * Get the current user's friends list.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            // Get accepted friendships where current user is either requester or addressee
            $friendships = Friendship::where('status', 'accepted')
                ->where(function ($query) use ($currentUser) {
                    $query->where('requester_id', $currentUser->id)
                          ->orWhere('addressee_id', $currentUser->id);
                })
                ->with(['requester:id,name,email,avatar,last_seen_at', 'addressee:id,name,email,avatar,last_seen_at'])
                ->get();

            // Extract friend users (the other user in each friendship)
            $friends = $friendships->map(function ($friendship) use ($currentUser) {
                $friend = $friendship->requester_id === $currentUser->id 
                    ? $friendship->addressee 
                    : $friendship->requester;
                
                $friend->avatar_url = $friend->avatar_url;
                $friend->is_online = $friend->isOnline();
                
                return $friend;
            });

            return response()->json([
                'success' => true,
                'data' => $friends,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching friends',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Send a friend request to another user.
     */
    public function sendRequest(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $currentUser = $request->user();
            $targetUserId = $request->input('user_id');

            // Check if trying to send request to self
            if ($currentUser->id === $targetUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot send a friend request to yourself',
                ], 400);
            }

            // Check if target user exists and is verified
            $targetUser = User::where('id', $targetUserId)
                ->whereNotNull('email_verified_at')
                ->first();

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or not verified',
                ], 404);
            }

            // Check if friendship already exists
            if (Friendship::existsBetween($currentUser, $targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Friend request already exists or you are already friends',
                ], 400);
            }

            // Create friend request
            $friendship = Friendship::create([
                'requester_id' => $currentUser->id,
                'addressee_id' => $targetUserId,
                'status' => 'pending',
            ]);

            // Load the relationship data
            $friendship->load(['requester:id,name,email,avatar', 'addressee:id,name,email,avatar']);

            return response()->json([
                'success' => true,
                'message' => 'Friend request sent successfully',
                'data' => [
                    'friendship_id' => $friendship->id,
                    'status' => $friendship->status,
                    'sent_to' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->name,
                        'email' => $targetUser->email,
                        'avatar_url' => $targetUser->avatar_url,
                    ],
                    'created_at' => $friendship->created_at,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending friend request',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Accept a friend request.
     */
    public function acceptRequest(Request $request, int $friendshipId): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            $friendship = Friendship::where('id', $friendshipId)
                ->where('addressee_id', $currentUser->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendship) {
                return response()->json([
                    'success' => false,
                    'message' => 'Friend request not found or already processed',
                ], 404);
            }

            $friendship->accept();
            $friendship->load(['requester:id,name,email,avatar']);

            return response()->json([
                'success' => true,
                'message' => 'Friend request accepted successfully',
                'data' => [
                    'friendship_id' => $friendship->id,
                    'status' => $friendship->status,
                    'friend' => [
                        'id' => $friendship->requester->id,
                        'name' => $friendship->requester->name,
                        'email' => $friendship->requester->email,
                        'avatar_url' => $friendship->requester->avatar_url,
                        'is_online' => $friendship->requester->isOnline(),
                    ],
                    'accepted_at' => $friendship->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while accepting friend request',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Decline a friend request.
     */
    public function declineRequest(Request $request, int $friendshipId): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            $friendship = Friendship::where('id', $friendshipId)
                ->where('addressee_id', $currentUser->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendship) {
                return response()->json([
                    'success' => false,
                    'message' => 'Friend request not found or already processed',
                ], 404);
            }

            $friendship->decline();

            return response()->json([
                'success' => true,
                'message' => 'Friend request declined successfully',
                'data' => [
                    'friendship_id' => $friendship->id,
                    'status' => $friendship->status,
                    'declined_at' => $friendship->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while declining friend request',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get pending friend requests (both sent and received).
     */
    public function pendingRequests(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();

            // Get received friend requests (requests I need to respond to)
            $receivedRequests = Friendship::where('addressee_id', $currentUser->id)
                ->where('status', 'pending')
                ->with(['requester:id,name,email,avatar,last_seen_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Get sent friend requests (requests I sent)
            $sentRequests = Friendship::where('requester_id', $currentUser->id)
                ->where('status', 'pending')
                ->with(['addressee:id,name,email,avatar,last_seen_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Format received requests
            $receivedFormatted = $receivedRequests->map(function ($friendship) {
                return [
                    'friendship_id' => $friendship->id,
                    'type' => 'received',
                    'user' => [
                        'id' => $friendship->requester->id,
                        'name' => $friendship->requester->name,
                        'email' => $friendship->requester->email,
                        'avatar_url' => $friendship->requester->avatar_url,
                        'is_online' => $friendship->requester->isOnline(),
                    ],
                    'created_at' => $friendship->created_at,
                    'can_accept' => true,
                    'can_decline' => true,
                ];
            });

            // Format sent requests
            $sentFormatted = $sentRequests->map(function ($friendship) {
                return [
                    'friendship_id' => $friendship->id,
                    'type' => 'sent',
                    'user' => [
                        'id' => $friendship->addressee->id,
                        'name' => $friendship->addressee->name,
                        'email' => $friendship->addressee->email,
                        'avatar_url' => $friendship->addressee->avatar_url,
                        'is_online' => $friendship->addressee->isOnline(),
                    ],
                    'created_at' => $friendship->created_at,
                    'can_accept' => false,
                    'can_decline' => false,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'received' => $receivedFormatted,
                    'sent' => $sentFormatted,
                    'total_received' => $receivedRequests->count(),
                    'total_sent' => $sentRequests->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching pending requests',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove a friend (unfriend).
     */
    public function removeFriend(Request $request, int $userId): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $targetUser = User::findOrFail($userId);

            $friendship = Friendship::between($currentUser, $targetUser);

            if (!$friendship || !$friendship->isAccepted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Friendship not found or not accepted',
                ], 404);
            }

            $friendship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Friend removed successfully',
                'data' => [
                    'removed_user_id' => $userId,
                    'removed_at' => now(),
                ],
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing friend',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Cancel a sent friend request.
     */
    public function cancelRequest(Request $request, int $friendshipId): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            $friendship = Friendship::where('id', $friendshipId)
                ->where('requester_id', $currentUser->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendship) {
                return response()->json([
                    'success' => false,
                    'message' => 'Friend request not found or already processed',
                ], 404);
            }

            $friendship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Friend request cancelled successfully',
                'data' => [
                    'cancelled_friendship_id' => $friendshipId,
                    'cancelled_at' => now(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling friend request',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}