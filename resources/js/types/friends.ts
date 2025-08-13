// Friend management related TypeScript interfaces

import type { User } from './auth';

export interface Friendship {
  id: number;
  requester_id: number;
  addressee_id: number;
  status: 'pending' | 'accepted' | 'declined';
  created_at: string;
  updated_at: string;
  requester: User;
  addressee: User;
}

export interface FriendRequest {
  id: number;
  requester: User;
  addressee: User;
  status: 'pending' | 'accepted' | 'declined';
  created_at: string;
  updated_at: string;
}

export interface UserSearchResult {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  is_friend: boolean;
  has_pending_request: boolean;
  request_sent_by_me: boolean;
}

export interface SearchUsersParams {
  query: string;
  page?: number;
  per_page?: number;
}

export interface SearchUsersResponse {
  data: UserSearchResult[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface FriendsListResponse {
  data: User[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface FriendRequestsResponse {
  sent: FriendRequest[];
  received: FriendRequest[];
}

export interface SendFriendRequestData {
  user_id: number;
}

export interface FriendRequestResponse {
  message: string;
  request: FriendRequest;
}

export interface FriendActionResponse {
  message: string;
  friendship?: Friendship;
}