<template>
  <div class="user-search">
    <!-- Search Input -->
    <div class="mb-6">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search for users by name or email..."
          class="w-full px-4 py-3 pl-10 pr-4 text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          @input="handleSearchInput"
        />
        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Search Results -->
    <div v-else-if="searchResults.length > 0" class="space-y-3">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Results</h3>
      <div
        v-for="user in searchResults"
        :key="user.id"
        class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
      >
        <div class="flex items-center space-x-3">
          <div class="flex-shrink-0">
            <img
              v-if="user.avatar"
              :src="user.avatar"
              :alt="user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center"
            >
              <span class="text-gray-600 font-medium text-sm">
                {{ user.name.charAt(0).toUpperCase() }}
              </span>
            </div>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
            <p class="text-sm text-gray-500">{{ user.email }}</p>
          </div>
        </div>

        <div class="flex-shrink-0">
          <!-- Already Friends -->
          <span
            v-if="user.is_friend"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
          >
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Friends
          </span>

          <!-- Request Sent -->
          <span
            v-else-if="user.has_pending_request && user.request_sent_by_me"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
          >
            Request Sent
          </span>

          <!-- Request Received -->
          <span
            v-else-if="user.has_pending_request && !user.request_sent_by_me"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
          >
            Request Received
          </span>

          <!-- Send Friend Request -->
          <button
            v-else
            @click="sendFriendRequest(user.id)"
            :disabled="sendingRequest === user.id"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg
              v-if="sendingRequest === user.id"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ sendingRequest === user.id ? 'Sending...' : 'Add Friend' }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-center mt-6">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
          <button
            @click="loadPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
            {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>
          <button
            @click="loadPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
    </div>

    <!-- No Results -->
    <div v-else-if="searchQuery && !isLoading" class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
      <p class="mt-1 text-sm text-gray-500">Try searching with a different name or email.</p>
    </div>

    <!-- Initial State -->
    <div v-else-if="!searchQuery" class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Search for users</h3>
      <p class="mt-1 text-sm text-gray-500">Start typing to find people you want to add as friends.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { friendsApi } from '../services/friendsApi';
import type { UserSearchResult } from '../types/friends';

// Props
interface Props {
  autoFocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  autoFocus: false
});

// Emits
const emit = defineEmits<{
  friendRequestSent: [userId: number];
}>();

// Reactive state
const searchQuery = ref('');
const searchResults = ref<UserSearchResult[]>([]);
const isLoading = ref(false);
const error = ref('');
const sendingRequest = ref<number | null>(null);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
});

// Search debounce timer
let searchTimeout: NodeJS.Timeout | null = null;

// Methods
const handleSearchInput = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(() => {
    if (searchQuery.value.trim().length >= 2) {
      searchUsers();
    } else {
      searchResults.value = [];
      error.value = '';
    }
  }, 300);
};

const searchUsers = async (page: number = 1) => {
  if (!searchQuery.value.trim()) return;

  isLoading.value = true;
  error.value = '';

  try {
    const response = await friendsApi.searchUsers({
      query: searchQuery.value.trim(),
      page,
      per_page: pagination.per_page
    });

    searchResults.value = response.data;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.per_page = response.per_page;
    pagination.total = response.total;
  } catch (err: any) {
    error.value = err.message || 'Failed to search users';
    searchResults.value = [];
  } finally {
    isLoading.value = false;
  }
};

const loadPage = (page: number) => {
  if (page >= 1 && page <= pagination.last_page) {
    searchUsers(page);
  }
};

const sendFriendRequest = async (userId: number) => {
  sendingRequest.value = userId;

  try {
    await friendsApi.sendFriendRequest({ user_id: userId });
    
    // Update the user in search results
    const userIndex = searchResults.value.findIndex(u => u.id === userId);
    if (userIndex !== -1) {
      searchResults.value[userIndex].has_pending_request = true;
      searchResults.value[userIndex].request_sent_by_me = true;
    }

    emit('friendRequestSent', userId);
  } catch (err: any) {
    error.value = err.message || 'Failed to send friend request';
  } finally {
    sendingRequest.value = null;
  }
};
</script>