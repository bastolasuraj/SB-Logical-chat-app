import { describe, it, expect, beforeEach, vi } from 'vitest';
import type { SearchUsersParams, SendFriendRequestData } from '../../types/friends';

// Mock axios
const mockAxiosInstance = {
  get: vi.fn(),
  post: vi.fn(),
  delete: vi.fn(),
  interceptors: {
    request: { use: vi.fn() },
    response: { use: vi.fn() }
  }
};

vi.mock('axios', () => ({
  default: {
    create: vi.fn(() => mockAxiosInstance)
  }
}));

describe('Friends API Service', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('searchUsers', () => {
    it('should successfully search for users', async () => {
      const searchParams: SearchUsersParams = {
        query: 'john',
        page: 1,
        per_page: 10
      };

      const mockResponse = {
        data: {
          data: [
            {
              id: 1,
              name: 'John Doe',
              email: 'john@example.com',
              is_friend: false,
              has_pending_request: false,
              request_sent_by_me: false
            }
          ],
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 1
        }
      };

      mockAxiosInstance.get.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.searchUsers(searchParams);

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/users/search', { params: searchParams });
      expect(result).toEqual(mockResponse.data);
    });

    it('should handle search error', async () => {
      const searchParams: SearchUsersParams = {
        query: 'test'
      };

      const mockError = {
        response: {
          status: 500,
          data: {
            message: 'Server error'
          }
        }
      };

      mockAxiosInstance.get.mockRejectedValue(mockError);

      const { friendsApi } = await import('../../services/friendsApi');
      await expect(friendsApi.searchUsers(searchParams)).rejects.toEqual({
        message: 'Server error',
        status: 500,
        errors: {}
      });
    });
  });

  describe('getFriends', () => {
    it('should successfully get friends list', async () => {
      const mockResponse = {
        data: {
          data: [
            {
              id: 1,
              name: 'John Doe',
              email: 'john@example.com',
              avatar: '',
              last_seen_at: '2023-01-01T00:00:00.000Z',
              created_at: '2023-01-01T00:00:00.000Z',
              updated_at: '2023-01-01T00:00:00.000Z'
            }
          ],
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 1
        }
      };

      mockAxiosInstance.get.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.getFriends(1, 20);

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/friends', {
        params: { page: 1, per_page: 20 }
      });
      expect(result).toEqual(mockResponse.data);
    });

    it('should use default pagination parameters', async () => {
      const mockResponse = {
        data: {
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 0
        }
      };

      mockAxiosInstance.get.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      await friendsApi.getFriends();

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/friends', {
        params: { page: 1, per_page: 20 }
      });
    });
  });

  describe('getFriendRequests', () => {
    it('should successfully get friend requests', async () => {
      const mockResponse = {
        data: {
          sent: [
            {
              id: 1,
              requester: {
                id: 1,
                name: 'Current User',
                email: 'current@example.com',
                avatar: '',
                last_seen_at: '2023-01-01T00:00:00.000Z',
                created_at: '2023-01-01T00:00:00.000Z',
                updated_at: '2023-01-01T00:00:00.000Z'
              },
              addressee: {
                id: 2,
                name: 'John Doe',
                email: 'john@example.com',
                avatar: '',
                last_seen_at: '2023-01-01T00:00:00.000Z',
                created_at: '2023-01-01T00:00:00.000Z',
                updated_at: '2023-01-01T00:00:00.000Z'
              },
              status: 'pending',
              created_at: '2023-01-01T00:00:00.000Z',
              updated_at: '2023-01-01T00:00:00.000Z'
            }
          ],
          received: []
        }
      };

      mockAxiosInstance.get.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.getFriendRequests();

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/friends/requests');
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('sendFriendRequest', () => {
    it('should successfully send friend request', async () => {
      const requestData: SendFriendRequestData = {
        user_id: 2
      };

      const mockResponse = {
        data: {
          message: 'Friend request sent successfully',
          request: {
            id: 1,
            requester: {
              id: 1,
              name: 'Current User',
              email: 'current@example.com',
              avatar: '',
              last_seen_at: '2023-01-01T00:00:00.000Z',
              created_at: '2023-01-01T00:00:00.000Z',
              updated_at: '2023-01-01T00:00:00.000Z'
            },
            addressee: {
              id: 2,
              name: 'John Doe',
              email: 'john@example.com',
              avatar: '',
              last_seen_at: '2023-01-01T00:00:00.000Z',
              created_at: '2023-01-01T00:00:00.000Z',
              updated_at: '2023-01-01T00:00:00.000Z'
            },
            status: 'pending',
            created_at: '2023-01-01T00:00:00.000Z',
            updated_at: '2023-01-01T00:00:00.000Z'
          }
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.sendFriendRequest(requestData);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/friends/request', requestData);
      expect(result).toEqual(mockResponse.data);
    });

    it('should handle duplicate friend request error', async () => {
      const requestData: SendFriendRequestData = {
        user_id: 2
      };

      const mockError = {
        response: {
          status: 422,
          data: {
            message: 'Friend request already exists',
            errors: {
              user_id: ['You have already sent a friend request to this user.']
            }
          }
        }
      };

      mockAxiosInstance.post.mockRejectedValue(mockError);

      const { friendsApi } = await import('../../services/friendsApi');
      await expect(friendsApi.sendFriendRequest(requestData)).rejects.toEqual({
        message: 'Friend request already exists',
        status: 422,
        errors: {
          user_id: ['You have already sent a friend request to this user.']
        }
      });
    });
  });

  describe('acceptFriendRequest', () => {
    it('should successfully accept friend request', async () => {
      const requestId = 1;
      const mockResponse = {
        data: {
          message: 'Friend request accepted',
          friendship: {
            id: 1,
            requester_id: 2,
            addressee_id: 1,
            status: 'accepted',
            created_at: '2023-01-01T00:00:00.000Z',
            updated_at: '2023-01-01T00:00:00.000Z',
            requester: {
              id: 2,
              name: 'John Doe',
              email: 'john@example.com',
              avatar: '',
              last_seen_at: '2023-01-01T00:00:00.000Z',
              created_at: '2023-01-01T00:00:00.000Z',
              updated_at: '2023-01-01T00:00:00.000Z'
            },
            addressee: {
              id: 1,
              name: 'Current User',
              email: 'current@example.com',
              avatar: '',
              last_seen_at: '2023-01-01T00:00:00.000Z',
              created_at: '2023-01-01T00:00:00.000Z',
              updated_at: '2023-01-01T00:00:00.000Z'
            }
          }
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.acceptFriendRequest(requestId);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith(`/friends/accept/${requestId}`);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('declineFriendRequest', () => {
    it('should successfully decline friend request', async () => {
      const requestId = 1;
      const mockResponse = {
        data: {
          message: 'Friend request declined'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.declineFriendRequest(requestId);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith(`/friends/decline/${requestId}`);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('cancelFriendRequest', () => {
    it('should successfully cancel friend request', async () => {
      const requestId = 1;
      const mockResponse = {
        data: {
          message: 'Friend request cancelled'
        }
      };

      mockAxiosInstance.delete.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.cancelFriendRequest(requestId);

      expect(mockAxiosInstance.delete).toHaveBeenCalledWith(`/friends/request/${requestId}`);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('removeFriend', () => {
    it('should successfully remove friend', async () => {
      const friendshipId = 1;
      const mockResponse = {
        data: {
          message: 'Friend removed successfully'
        }
      };

      mockAxiosInstance.delete.mockResolvedValue(mockResponse);

      const { friendsApi } = await import('../../services/friendsApi');
      const result = await friendsApi.removeFriend(friendshipId);

      expect(mockAxiosInstance.delete).toHaveBeenCalledWith(`/friends/${friendshipId}`);
      expect(result).toEqual(mockResponse.data);
    });
  });
});