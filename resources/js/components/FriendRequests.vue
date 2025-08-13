<template>
  <div class="friend-requests">
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

    <div v-else>
      <!-- Received Friend Requests -->
      <div v-if="receivedRequests.length > 0" class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
          </svg>
          Friend Requests ({{ receivedRequests.length }})
        </h3>
        <div class="space-y-3">
          <div
            v-for="request in receivedRequests"
            :key="request.id"
            class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
          >
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <img
                  v-if="request.requester.avatar"
                  :src="request.requester.avatar"
                  :alt="request.requester.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div
                  v-else
                  class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center"
                >
                  <span class="text-gray-600 font-medium text-sm">
                    {{ request.requester.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ request.requester.name }}</p>
                <p class="text-sm text-gray-500">{{ request.requester.email }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(request.created_at) }}</p>
              </div>
            </div>

            <div class="flex space-x-2">
              <button
                @click="acceptRequest(request.id)"
                :disabled="processingRequest === request.id"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  v-if="processingRequest === request.id && actionType === 'accept'"
                  class="animate-spin -ml-1 mr-1 h-4 w-4 text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Accept
              </button>
              <button
                @click="declineRequest(request.id)"
                :disabled="processingRequest === request.id"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  v-if="processingRequest === request.id && actionType === 'decline'"
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
                Decline
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sent Friend Requests -->
      <div v-if="sentRequests.length > 0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
          </svg>
          Sent Requests ({{ sentRequests.length }})
        </h3>
        <div class="space-y-3">
          <div
            v-for="request in sentRequests"
            :key="request.id"
            class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
          >
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <img
                  v-if="request.addressee.avatar"
                  :src="request.addressee.avatar"
                  :alt="request.addressee.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div
                  v-else
                  class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center"
                >
                  <span class="text-gray-600 font-medium text-sm">
                    {{ request.addressee.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ request.addressee.name }}</p>
                <p class="text-sm text-gray-500">{{ request.addressee.email }}</p>
                <p class="text-xs text-gray-400">Sent {{ formatDate(request.created_at) }}</p>
              </div>
            </div>

            <div class="flex items-center space-x-3">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                Pending
              </span>
              <button
                @click="cancelRequest(request.id)"
                :disabled="processingRequest === request.id"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  v-if="processingRequest === request.id && actionType === 'cancel'"
                  class="animate-spin -ml-1 mr-1 h-4 w-4 text-gray-700"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- No Requests -->
      <div v-if="receivedRequests.length === 0 && sentRequests.length === 0" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No friend requests</h3>
        <p class="mt-1 text-sm text-gray-500">You don't have any pending friend requests at the moment.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { friendsApi } from '../services/friendsApi';
import type { FriendRequest } from '../types/friends';

// Emits
const emit = defineEmits<{
  requestAccepted: [requestId: number];
  requestDeclined: [requestId: number];
  requestCancelled: [requestId: number];
}>();

// Reactive state
const receivedRequests = ref<FriendRequest[]>([]);
const sentRequests = ref<FriendRequest[]>([]);
const isLoading = ref(false);
const error = ref('');
const processingRequest = ref<number | null>(null);
const actionType = ref<'accept' | 'decline' | 'cancel' | null>(null);

// Methods
const loadFriendRequests = async () => {
  isLoading.value = true;
  error.value = '';

  try {
    const response = await friendsApi.getFriendRequests();
    receivedRequests.value = response.received;
    sentRequests.value = response.sent;
  } catch (err: any) {
    error.value = err.message || 'Failed to load friend requests';
  } finally {
    isLoading.value = false;
  }
};

const acceptRequest = async (requestId: number) => {
  processingRequest.value = requestId;
  actionType.value = 'accept';

  try {
    await friendsApi.acceptFriendRequest(requestId);
    
    // Remove from received requests
    receivedRequests.value = receivedRequests.value.filter(req => req.id !== requestId);
    
    emit('requestAccepted', requestId);
  } catch (err: any) {
    error.value = err.message || 'Failed to accept friend request';
  } finally {
    processingRequest.value = null;
    actionType.value = null;
  }
};

const declineRequest = async (requestId: number) => {
  processingRequest.value = requestId;
  actionType.value = 'decline';

  try {
    await friendsApi.declineFriendRequest(requestId);
    
    // Remove from received requests
    receivedRequests.value = receivedRequests.value.filter(req => req.id !== requestId);
    
    emit('requestDeclined', requestId);
  } catch (err: any) {
    error.value = err.message || 'Failed to decline friend request';
  } finally {
    processingRequest.value = null;
    actionType.value = null;
  }
};

const cancelRequest = async (requestId: number) => {
  processingRequest.value = requestId;
  actionType.value = 'cancel';

  try {
    await friendsApi.cancelFriendRequest(requestId);
    
    // Remove from sent requests
    sentRequests.value = sentRequests.value.filter(req => req.id !== requestId);
    
    emit('requestCancelled', requestId);
  } catch (err: any) {
    error.value = err.message || 'Failed to cancel friend request';
  } finally {
    processingRequest.value = null;
    actionType.value = null;
  }
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));

  if (diffInHours < 1) {
    return 'Just now';
  } else if (diffInHours < 24) {
    return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
  } else if (diffInHours < 168) { // 7 days
    const days = Math.floor(diffInHours / 24);
    return `${days} day${days > 1 ? 's' : ''} ago`;
  } else {
    return date.toLocaleDateString();
  }
};

// Expose methods for parent component
defineExpose({
  loadFriendRequests
});

// Load requests on mount
onMounted(() => {
  loadFriendRequests();
});
</script>