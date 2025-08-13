<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserSearchController extends Controller
{
    /**
     * Search for users by name or email.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:255',
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
            ]);

            $query = $request->input('query');
            $perPage = $request->input('per_page', 10);
            $currentUser = $request->user();

            // Search for users by name or email, excluding current user
            $users = User::where('id', '!=', $currentUser->id)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->whereNotNull('email_verified_at') // Only verified users
                ->select(['id', 'name', 'email', 'avatar', 'last_seen_at'])
                ->paginate($perPage);

            // Add friendship status for each user
            $users->getCollection()->transform(function ($user) use ($currentUser) {
                $friendship = Friendship::between($currentUser, $user);
                
                $user->friendship_status = 'none';
                $user->friendship_id = null;
                $user->can_send_request = true;
                
                if ($friendship) {
                    $user->friendship_status = $friendship->status;
                    $user->friendship_id = $friendship->id;
                    $user->can_send_request = false;
                    
                    // Check if current user can accept/decline
                    $user->can_accept = $friendship->addressee_id === $currentUser->id && $friendship->isPending();
                    $user->can_decline = $friendship->addressee_id === $currentUser->id && $friendship->isPending();
                } else {
                    $user->can_accept = false;
                    $user->can_decline = false;
                }
                
                // Add avatar URL and online status
                $user->avatar_url = $user->avatar_url;
                $user->is_online = $user->isOnline();
                
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user suggestions based on mutual connections or recent activity.
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:20',
            ]);

            $limit = $request->input('limit', 10);
            $currentUser = $request->user();

            // Get users who are not friends and not in pending requests
            $existingFriendships = Friendship::forUser($currentUser)
                ->pluck('requester_id')
                ->merge(Friendship::forUser($currentUser)->pluck('addressee_id'))
                ->unique()
                ->filter(fn($id) => $id !== $currentUser->id);

            $suggestions = User::where('id', '!=', $currentUser->id)
                ->whereNotIn('id', $existingFriendships)
                ->whereNotNull('email_verified_at')
                ->orderBy('last_seen_at', 'desc')
                ->select(['id', 'name', 'email', 'avatar', 'last_seen_at'])
                ->limit($limit)
                ->get();

            // Add additional data for each suggestion
            $suggestions->transform(function ($user) {
                $user->avatar_url = $user->avatar_url;
                $user->is_online = $user->isOnline();
                $user->friendship_status = 'none';
                $user->can_send_request = true;
                
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting user suggestions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}