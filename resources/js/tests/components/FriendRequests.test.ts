import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import FriendRequests from '../../components/FriendRequests.vue';
import { friendsApi } from '../../services/friendsApi';

// Mock the friends API
vi.mock('../../services/friendsApi', () => ({
  friendsApi: {
    getFriendRequests: vi.fn(),
    acceptFriendRequest: vi.fn(),
    declineFriendRequest: vi.fn(),
    cancelFriendRequest: vi.fn()
  }
}));

describe('FriendRequests', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should render loading state initially', () => {
    const mockGetFriendRequests = vi.mocked(friendsApi.getFriendRequests);
    mockGetFriendRequests.mockImplementation(() => new Promise(() => {})); // Never resolves

    const wrapper = mount(FriendRequests);
    
    expect(wrapper.find('.animate-spin').exists()).toBe(true);
  });

  it('should display received friend requests', async () => {
    const mockRequestsData = {
      received: [
        {
          id: 1,
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
          },
          status: 'pending' as const,
          created_at: '2023-01-01T00:00:00.000Z',
          updated_at: '2023-01-01T00:00:00.000Z'
        }
      ],
      sent: []
    };

    const mockGetFriendRequests = vi.mocked(friendsApi.getFriendRequests);
    mockGetFriendRequests.mockResolvedValue(mockRequestsData);

    const wrapper = mount(FriendRequests);
    
    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));
    
    expect(wrapper.text()).toContain('Friend Requests (1)');
    expect(wrapper.text()).toContain('John Doe');
    expect(wrapper.text()).toContain('john@example.com');
    expect(wrapper.text()).toContain('Accept');
    expect(wrapper.text()).toContain('Decline');
  });

  it('should display sent friend requests', async () => {
    const mockRequestsData = {
      received: [],
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
            name: 'Jane Smith',
            email: 'jane@example.com',
            avatar: '',
            last_seen_at: '2023-01-01T00:00:00.000Z',
            created_at: '2023-01-01T00:00:00.000Z',
            updated_at: '2023-01-01T00:00:00.000Z'
          },
          status: 'pending' as const,
          created_at: '2023-01-01T00:00:00.000Z',
          updated_at: '2023-01-01T00:00:00.000Z'
        }
      ]
    };

    const mockGetFriendRequests = vi.mocked(friendsApi.getFriendRequests);
    mockGetFriendRequests.mockResolvedValue(mockRequestsData);

    const wrapper = mount(FriendRequests);
    
    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));
    
    expect(wrapper.text()).toContain('Sent Requests (1)');
    expect(wrapper.text()).toContain('Jane Smith');
    expect(wrapper.text()).toContain('jane@example.com');
    expect(wrapper.text()).toContain('Pending');
    expect(wrapper.text()).toContain('Cancel');
  });

  it('should show no requests message when there are no requests', async () => {
    const mockRequestsData = {
      received: [],
      sent: []
    };

    const mockGetFriendRequests = vi.mocked(friendsApi.getFriendRequests);
    mockGetFriendRequests.mockResolvedValue(mockRequestsData);

    const wrapper = mount(FriendRequests);
    
    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));
    
    expect(wrapper.text()).toContain('No friend requests');
    expect(wrapper.text()).toContain('You don\'t have any pending friend requests');
  });

  it('should emit requestAccepted when accept button is clicked', async () => {
    const mockRequestsData = {
      received: [
        {
          id: 1,
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
          },
          status: 'pending' as const,
          created_at: '2023-01-01T00:00:00.000Z',
          updated_at: '2023-01-01T00:00:00.000Z'
        }
      ],
      sent: []
    };

    const mockGetFriendRequests = vi.mocked(friendsApi.getFriendRequests);
    const mockAcceptFriendRequest = vi.mocked(friendsApi.acceptFriendRequest);
    
    mockGetFriendRequests.mockResolvedValue(mockRequestsData);
    mockAcceptFriendRequest.mockResolvedValue({
      message: 'Friend request accepted',
      friendship: {
        id: 1,
        requester_id: 2,
        addressee_id: 1,
        status: 'accepted',
        created_at: '2023-01-01T00:00:00.000Z',
        updated_at: '2023-01-01T00:00:00.000Z',
        requester: mockRequestsData.received[0].requester,
        addressee: mockRequestsData.received[0].addressee
      }
    });

    const wrapper = mount(FriendRequests);
    
    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));
    
    const acceptButton = wrapper.find('button').filter(btn => btn.text().includes('Accept'))[0];
    await acceptButton.trigger('click');
    await wrapper.vm.$nextTick();
    
    expect(mockAcceptFriendRequest).toHaveBeenCalledWith(1);
    expect(wrapper.emitted('requestAccepted')).toBeTruthy();
    expect(wrapper.emitted('requestAccepted')?.[0]).toEqual([1]);
  });

  it('should emit requestDeclined when decline button is clicked', async () => {
    const mockRequestsData = {
      received: [
        {
          id: 1,
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
          },
          status: 'pending' as const,
          created_at: '2023-01-01T00:00:00.000Z',
          updated_at: '2023-01-01T00:00:00.000Z'
        }
      ],
      sent: []
    };

    const mockGetFriendRequests = vi.mocked(friendsApi.getFriendRequests);
    const mockDeclineFriendRequest = vi.mocked(friendsApi.declineFriendRequest);
    
    mockGetFriendRequests.mockResolvedValue(mockRequestsData);
    mockDeclineFriendRequest.mockResolvedValue({
      message: 'Friend request declined'
    });

    const wrapper = mount(FriendRequests);
    
    // Wait for component to load
    await wrapper.vm.$nextTick();
    await new Promise(resolve => setTimeout(resolve, 10));
    
    const declineButton = wrapper.find('button').filter(btn => btn.text().includes('Decline'))[0];
    await declineButton.trigger('click');
    await wrapper.vm.$nextTick();
    
    expect(mockDeclineFriendRequest).toHaveBeenCalledWith(1);
    expect(wrapper.emitted('requestDeclined')).toBeTruthy();
    expect(wrapper.emitted('requestDeclined')?.[0]).toEqual([1]);
  });
});