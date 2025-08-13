<template>
  <div class="friends-view min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Friends</h1>
            <p class="mt-2 text-sm text-gray-600">Manage your friends and friend requests</p>
          </div>
          <router-link
            to="/chat"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0L3 11.414V13a1 1 0 01-2 0V9a1 1 0 011-1h4a1 1 0 110 2H4.414l3.293 3.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to Chat
          </router-link>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="mb-8">
        <nav class="flex space-x-8">
          <button
            @click="activeTab = 'friends'"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeTab === 'friends'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            My Friends
            <span
              v-if="friendsCount > 0"
              class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
            >
              {{ friendsCount }}
            </span>
          </button>
          <button
            @click="activeTab = 'requests'"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeTab === 'requests'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Friend Requests
            <span
              v-if="pendingRequestsCount > 0"
              class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
            >
              {{ pendingRequestsCount }}
            </span>
          </button>
          <button
            @click="activeTab = 'search'"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeTab === 'search'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Find Friends
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="bg-white rounded-lg shadow">
        <!-- Friends List Tab -->
        <div v-if="activeTab === 'friends'" class="p-6">
          <div v-if="isLoadingFriends" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          </div>

          <div v-else-if="friendsError" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ friendsError }}</p>
              </div>
            </div>
          </div>

          <div v-else-if="friends.length > 0">
            <div class="space-y-3">
              <div
                v-for="friend in friends"
                :key="friend.id"
                class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
              >
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <img
                      v-if="friend.avatar"
                      :src="friend.avatar"
                      :alt="friend.name"
                      class="w-10 h-10 rounded-full object-cover"
                    />
                    <div
                      v-else
                      class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center"
                    >
                      <span class="text-gray-600 font-medium text-sm">
                        {{ friend.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ friend.name }}</p>
                    <p class="text-sm text-gray-500">{{ friend.email }}</p>
                    <p class="text-xs text-gray-400">Last seen {{ formatDate(friend.last_seen_at) }}</p>
                  </div>
                </div>

                <div class="flex items-center space-x-2">
                  <button
                    @click="startChat(friend.id)"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                    </svg>
                    Chat
                  </button>
                  <button
                    @click="removeFriend(friend.id)"
                    :disabled="removingFriend === friend.id"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg
                      v-if="removingFriend === friend.id"
                      class="animate-spin -ml-1 mr-1 h-4 w-4 text-gray-700"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Remove
                  </button>
                </div>
              </div>
            </div>

            <!-- Friends Pagination -->
            <div v-if="friendsPagination.last_page > 1" class="flex justify-center mt-6">
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button
                  @click="loadFriendsPage(friendsPagination.current_page - 1)"
                  :disabled="friendsPagination.current_page === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                  {{ friendsPagination.current_page }} of {{ friendsPagination.last_page }}
                </span>
                <button
                  @click="loadFriendsPage(friendsPagination.current_page + 1)"
                  :disabled="friendsPagination.current_page === friendsPagination.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </nav>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No friends yet</h3>
            <p class="mt-1 text-sm text-gray-500">Start by searching for people you know and sending friend requests.</p>
            <div class="mt-6">
              <button
                @click="activeTab = 'search'"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Find Friends
              </button>
            </div>
          </div>
        </div>

        <!-- Friend Requests Tab -->
        <div v-else-if="activeTab === 'requests'" class="p-6">
          <FriendRequests
            ref="friendRequestsRef"
            @request-accepted="handleRequestAccepted"
            @request-declined="handleRequestDeclined"
            @request-cancelled="handleRequestCancelled"
          />
        </div>

        <!-- Search Tab -->
        <div v-else-if="activeTab === 'search'" class="p-6">
          <UserSearch @friend-request-sent="handleFriendRequestSent" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { friendsApi } from '../services/friendsApi';
import type { User } from '../types/auth';
import UserSearch from '../components/UserSearch.vue';
import FriendRequests from '../components/FriendRequests.vue';

// Router
const router = useRouter();

// Reactive state
const activeTab = ref<'friends' | 'requests' | 'search'>('friends');
const friends = ref<User[]>([]);
const isLoadingFriends = ref(false);
const friendsError = ref('');
const removingFriend = ref<number | null>(null);

const friendsPagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
});

// Refs
const friendRequestsRef = ref<InstanceType<typeof FriendRequests> | null>(null);

// Computed
const friendsCount = computed(() => friends.value.length);
const pendingRequestsCount = computed(() => {
  // This would be updated when friend requests are loaded
  return 0; // Placeholder - would be calculated from friend requests data
});

// Methods
const loadFriends = async (page: number = 1) => {
  isLoadingFriends.value = true;
  friendsError.value = '';

  try {
    const response = await friendsApi.getFriends(page, friendsPagination.per_page);
    friends.value = response.data;
    friendsPagination.current_page = response.current_page;
    friendsPagination.last_page = response.last_page;
    friendsPagination.per_page = response.per_page;
    friendsPagination.total = response.total;
  } catch (err: any) {
    friendsError.value = err.message || 'Failed to load friends';
  } finally {
    isLoadingFriends.value = false;
  }
};

const loadFriendsPage = (page: number) => {
  if (page >= 1 && page <= friendsPagination.last_page) {
    loadFriends(page);
  }
};

const removeFriend = async (friendId: number) => {
  if (!confirm('Are you sure you want to remove this friend?')) {
    return;
  }

  removingFriend.value = friendId;

  try {
    // Note: This assumes we have the friendship ID, but we might need to modify the API
    // to accept user ID instead or include friendship ID in the friends list
    await friendsApi.removeFriend(friendId);
    
    // Remove from friends list
    friends.value = friends.value.filter(friend => friend.id !== friendId);
  } catch (err: any) {
    friendsError.value = err.message || 'Failed to remove friend';
  } finally {
    removingFriend.value = null;
  }
};

const startChat = (friendId: number) => {
  // Navigate to chat with this friend
  router.push({ name: 'chat', query: { user: friendId } });
};

const handleFriendRequestSent = (userId: number) => {
  // Optionally show a success message or update UI
  console.log('Friend request sent to user:', userId);
};

const handleRequestAccepted = (requestId: number) => {
  // Reload friends list to include the new friend
  loadFriends();
  console.log('Friend request accepted:', requestId);
};

const handleRequestDeclined = (requestId: number) => {
  console.log('Friend request declined:', requestId);
};

const handleRequestCancelled = (requestId: number) => {
  console.log('Friend request cancelled:', requestId);
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));

  if (diffInHours < 1) {
    return 'just now';
  } else if (diffInHours < 24) {
    return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
  } else if (diffInHours < 168) { // 7 days
    const days = Math.floor(diffInHours / 24);
    return `${days} day${days > 1 ? 's' : ''} ago`;
  } else {
    return date.toLocaleDateString();
  }
};

// Load friends on mount
onMounted(() => {
  loadFriends();
});
</script>