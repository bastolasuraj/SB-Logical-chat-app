import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import FriendsView from '../../views/FriendsView.vue';
import { friendsApi } from '../../services/friendsApi';

// Mock the friends API
vi.mock('../../services/friendsApi', () => ({
  friendsApi: {
    getFriends: vi.fn(),
    removeFriend: vi.fn()
  }
}));

// Mock child components
vi.mock('../../components/UserSearch.vue', () => ({
  default: {
    name: 'UserSearch',
    template: '<div data-testid="user-search">UserSearch Component</div>',
    emits: ['friendRequestSent']
  }
}));

vi.mock('../../components/FriendRequests.vue', () => ({
  default: {
    name: 'FriendRequests',
    template: '<div data-testid="friend-requests">FriendRequests Component</div>',
    emits: ['requestAccepted', 'requestDeclined', 'requestCancelled'],
    methods: {
      loadFriendRequests: vi.fn()
    }
  }
}));

describe('FriendsView', () => {
  let router: any;

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Create a mock router
    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/chat', name: 'chat', component: { template: '<div>Chat</div>' } },
        { path: '/friends', name: 'friends', component: FriendsView }
      ]
    });
  });

  it('should render the main layout with tabs', async () => {
    const mockFriendsData = {
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    };

    const mockGetFriends = vi.mocked(friendsApi.getFriends);
    mockGetFriends.mockResolvedValue(mockFriendsData);

    const wrapper = mount(FriendsView, {
      global: {
        plugins: [router]
      }
    });

    expect(wrapper.find('h1').text()).toBe('Friends');
    expect(wrapper.text()).toContain('Manage your friends and friend requests');
    
    // Check tab navigation
    expect(wrapper.text()).toContain('My Friends');
    expect(wrapper.text()).toContain('Friend Requests');
    expect(wrapper.text()).toContain('Find Friends');
    
    // Check back to chat button
    expect(wrapper.find('a[href="/chat"]').exists()).toBe(true);
  });

  it('should display friends list when friends tab is active', async () => {
    const mockFriendsData = {
      data: [
        {
          id: 1,
          name: 'John Doe',
          email: 'john@example.com',
          avatar: '',
          last_seen_at: '2023-01-01T00:00:00.000Z',
          created_at: '2023-01-01T00:00:00.000Z',
          updated_at: '2023-01-01T00:00:00.000Z'
        },
        {
          id: 2,
          name: 'Jane Smith',
          email: 'jane@example.com',
          avatar: '',
          last_seen_at: '2023-01-01T00:00:00.000Z',
          created_at: '2023-01-01T00:00:00.000Z',
          updated_at: '2023-01-01T00:00:00.000Z'
        }
      ],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 2
    };

    const mockGetFriends = vi.mocked(friendsApi.getFriends);
    mockGetFriends.mockResolvedValue(mockFriendsData);

    const wrapper = mount(FriendsView, {
      global: {
        plugins: [router]
      }
    });

    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));

    expect(wrapper.text()).toContain('John Doe');
    expect(wrapper.text()).toContain('jane@example.com');
    expect(wrapper.text()).toContain('Chat');
    expect(wrapper.text()).toContain('Remove');
  });

  it('should show no friends message when friends list is empty', async () => {
    const mockFriendsData = {
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    };

    const mockGetFriends = vi.mocked(friendsApi.getFriends);
    mockGetFriends.mockResolvedValue(mockFriendsData);

    const wrapper = mount(FriendsView, {
      global: {
        plugins: [router]
      }
    });

    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));

    expect(wrapper.text()).toContain('No friends yet');
    expect(wrapper.text()).toContain('Start by searching for people you know');
  });

  it('should switch to search tab when clicked', async () => {
    const mockFriendsData = {
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    };

    const mockGetFriends = vi.mocked(friendsApi.getFriends);
    mockGetFriends.mockResolvedValue(mockFriendsData);

    const wrapper = mount(FriendsView, {
      global: {
        plugins: [router]
      }
    });

    // Click on Find Friends tab
    const searchTab = wrapper.find('button').filter(btn => btn.text().includes('Find Friends'))[0];
    await searchTab.trigger('click');

    expect(wrapper.find('[data-testid="user-search"]').exists()).toBe(true);
  });

  it('should switch to requests tab when clicked', async () => {
    const mockFriendsData = {
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    };

    const mockGetFriends = vi.mocked(friendsApi.getFriends);
    mockGetFriends.mockResolvedValue(mockFriendsData);

    const wrapper = mount(FriendsView, {
      global: {
        plugins: [router]
      }
    });

    // Click on Friend Requests tab
    const requestsTab = wrapper.find('button').filter(btn => btn.text().includes('Friend Requests'))[0];
    await requestsTab.trigger('click');

    expect(wrapper.find('[data-testid="friend-requests"]').exists()).toBe(true);
  });

  it('should handle friend removal', async () => {
    const mockFriendsData = {
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
    };

    const mockGetFriends = vi.mocked(friendsApi.getFriends);
    const mockRemoveFriend = vi.mocked(friendsApi.removeFriend);
    
    mockGetFriends.mockResolvedValue(mockFriendsData);
    mockRemoveFriend.mockResolvedValue({ message: 'Friend removed successfully' });

    // Mock window.confirm
    const originalConfirm = window.confirm;
    window.confirm = vi.fn(() => true);

    const wrapper = mount(FriendsView, {
      global: {
        plugins: [router]
      }
    });

    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));

    const removeButton = wrapper.find('button').filter(btn => btn.text().includes('Remove'))[0];
    await removeButton.trigger('click');
    await wrapper.vm.$nextTick();

    expect(window.confirm).toHaveBeenCalledWith('Are you sure you want to remove this friend?');
    expect(mockRemoveFriend).toHaveBeenCalledWith(1);

    // Restore original confirm
    window.confirm = originalConfirm;
  });
});