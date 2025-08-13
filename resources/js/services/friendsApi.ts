import axios from 'axios';
import type {
  SearchUsersParams,
  SearchUsersResponse,
  FriendsListResponse,
  FriendRequestsResponse,
  SendFriendRequestData,
  FriendRequestResponse,
  FriendActionResponse
} from '../types/friends';

// Create axios instance with base configuration
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const friendsApi = {
  async searchUsers(params: SearchUsersParams): Promise<SearchUsersResponse> {
    try {
      const response = await api.get('/users/search', { params });
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to search users',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async getFriends(page: number = 1, perPage: number = 20): Promise<FriendsListResponse> {
    try {
      const response = await api.get('/friends', {
        params: { page, per_page: perPage }
      });
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to fetch friends',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async getFriendRequests(): Promise<FriendRequestsResponse> {
    try {
      const response = await api.get('/friends/requests');
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to fetch friend requests',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async sendFriendRequest(data: SendFriendRequestData): Promise<FriendRequestResponse> {
    try {
      const response = await api.post('/friends/request', data);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to send friend request',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async acceptFriendRequest(requestId: number): Promise<FriendActionResponse> {
    try {
      const response = await api.post(`/friends/accept/${requestId}`);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to accept friend request',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async declineFriendRequest(requestId: number): Promise<FriendActionResponse> {
    try {
      const response = await api.post(`/friends/decline/${requestId}`);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to decline friend request',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async cancelFriendRequest(requestId: number): Promise<FriendActionResponse> {
    try {
      const response = await api.delete(`/friends/request/${requestId}`);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to cancel friend request',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async removeFriend(friendshipId: number): Promise<FriendActionResponse> {
    try {
      const response = await api.delete(`/friends/${friendshipId}`);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to remove friend',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  }
};