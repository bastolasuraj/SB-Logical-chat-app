import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import UserSearch from '../../components/UserSearch.vue';
import { friendsApi } from '../../services/friendsApi';

// Mock the friends API
vi.mock('../../services/friendsApi', () => ({
  friendsApi: {
    searchUsers: vi.fn(),
    sendFriendRequest: vi.fn()
  }
}));

describe('UserSearch', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should render search input', () => {
    const wrapper = mount(UserSearch);
    
    const searchInput = wrapper.find('input[type="text"]');
    expect(searchInput.exists()).toBe(true);
    expect(searchInput.attributes('placeholder')).toContain('Search for users');
  });

  it('should show initial state message when no search query', () => {
    const wrapper = mount(UserSearch);
    
    expect(wrapper.text()).toContain('Search for users');
    expect(wrapper.text()).toContain('Start typing to find people');
  });

  it('should show loading state when searching', async () => {
    const mockSearchUsers = vi.mocked(friendsApi.searchUsers);
    mockSearchUsers.mockImplementation(() => new Promise(() => {})); // Never resolves

    const wrapper = mount(UserSearch);
    const searchInput = wrapper.find('input[type="text"]');
    
    await searchInput.setValue('test user');
    await searchInput.trigger('input');
    
    // Wait for debounce
    await new Promise(resolve => setTimeout(resolve, 350));
    
    expect(wrapper.find('.animate-spin').exists()).toBe(true);
  });

  it('should display search results', async () => {
    const mockSearchResults = {
      data: [
        {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com',
          is_friend: false,
          has_pending_request: false,
          request_sent_by_me: false
        },
        {
          id: 2,
          name: 'Jane Smith',
          email: 'jane@example.com',
          is_friend: true,
          has_pending_request: false,
          request_sent_by_me: false
        }
      ],
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 2
    };

    const mockSearchUsers = vi.mocked(friendsApi.searchUsers);
    mockSearchUsers.mockResolvedValue(mockSearchResults);

    const wrapper = mount(UserSearch);
    const searchInput = wrapper.find('input[type="text"]');
    
    await searchInput.setValue('john');
    await searchInput.trigger('input');
    
    // Wait for debounce and API call
    await new Promise(resolve => setTimeout(resolve, 350));
    await wrapper.vm.$nextTick();
    
    expect(wrapper.text()).toContain('John Doe');
    expect(wrapper.text()).toContain('Jane Smith');
    expect(wrapper.text()).toContain('john@example.com');
    expect(wrapper.text()).toContain('jane@example.com');
  });

  it('should show different button states based on friendship status', async () => {
    const mockSearchResults = {
      data: [
        {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com',
          is_friend: false,
          has_pending_request: false,
          request_sent_by_me: false
        },
        {
          id: 2,
          name: 'Jane Smith',
          email: 'jane@example.com',
          is_friend: true,
          has_pending_request: false,
          request_sent_by_me: false
        },
        {
          id: 3,
          name: 'Bob Wilson',
          email: 'bob@example.com',
          is_friend: false,
          has_pending_request: true,
          request_sent_by_me: true
        }
      ],
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 3
    };

    const mockSearchUsers = vi.mocked(friendsApi.searchUsers);
    mockSearchUsers.mockResolvedValue(mockSearchResults);

    const wrapper = mount(UserSearch);
    const searchInput = wrapper.find('input[type="text"]');
    
    await searchInput.setValue('test');
    await searchInput.trigger('input');
    
    // Wait for debounce and API call
    await new Promise(resolve => setTimeout(resolve, 350));
    await wrapper.vm.$nextTick();
    
    // Should show "Add Friend" button for John
    expect(wrapper.text()).toContain('Add Friend');
    
    // Should show "Friends" status for Jane
    expect(wrapper.text()).toContain('Friends');
    
    // Should show "Request Sent" status for Bob
    expect(wrapper.text()).toContain('Request Sent');
  });

  it('should emit friendRequestSent when friend request is sent', async () => {
    const mockSearchResults = {
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
    };

    const mockSearchUsers = vi.mocked(friendsApi.searchUsers);
    const mockSendFriendRequest = vi.mocked(friendsApi.sendFriendRequest);
    
    mockSearchUsers.mockResolvedValue(mockSearchResults);
    mockSendFriendRequest.mockResolvedValue({
      message: 'Friend request sent',
      request: {
        id: 1,
        requester: { id: 1, name: 'Current User', email: 'current@example.com', avatar: '', last_seen_at: '', created_at: '', updated_at: '' },
        addressee: { id: 2, name: 'John Doe', email: 'john@example.com', avatar: '', last_seen_at: '', created_at: '', updated_at: '' },
        status: 'pending',
        created_at: '',
        updated_at: ''
      }
    });

    const wrapper = mount(UserSearch);
    const searchInput = wrapper.find('input[type="text"]');
    
    await searchInput.setValue('john');
    await searchInput.trigger('input');
    
    // Wait for debounce and API call
    await new Promise(resolve => setTimeout(resolve, 350));
    await wrapper.vm.$nextTick();
    
    const addFriendButton = wrapper.find('button').filter(btn => btn.text().includes('Add Friend'))[0];
    await addFriendButton.trigger('click');
    await wrapper.vm.$nextTick();
    
    expect(mockSendFriendRequest).toHaveBeenCalledWith({ user_id: 1 });
    expect(wrapper.emitted('friendRequestSent')).toBeTruthy();
    expect(wrapper.emitted('friendRequestSent')?.[0]).toEqual([1]);
  });
});