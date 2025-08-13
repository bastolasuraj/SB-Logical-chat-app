<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Or
          <router-link 
            to="/register" 
            class="font-medium text-indigo-600 hover:text-indigo-500"
          >
            create a new account
          </router-link>
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Email address</label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              :class="{ 'border-red-500': errors.email }"
              placeholder="Email address"
            />
            <div v-if="errors.email" class="text-red-500 text-sm mt-1">
              {{ errors.email }}
            </div>
          </div>
          
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              :class="{ 'border-red-500': errors.password }"
              placeholder="Password"
            />
            <div v-if="errors.password" class="text-red-500 text-sm mt-1">
              {{ errors.password }}
            </div>
          </div>
        </div>

        <div v-if="generalError" class="text-red-500 text-sm text-center">
          {{ generalError }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="isLoading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isLoading" class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            {{ isLoading ? 'Signing in...' : 'Sign in' }}
          </button>
        </div>

        <div class="text-center">
          <router-link 
            to="/forgot-password" 
            class="text-sm text-indigo-600 hover:text-indigo-500"
          >
            Forgot your password?
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useValidation } from '../../composables/useValidation';
import type { LoginCredentials } from '../../types/auth';

const router = useRouter();
const authStore = useAuthStore();
const { errors, validate, clearErrors, setServerErrors } = useValidation();

const form = reactive<LoginCredentials>({
  email: '',
  password: ''
});

const isLoading = ref(false);
const generalError = ref('');

const validationRules = {
  email: [
    { required: true, message: 'Email is required' },
    { email: true, message: 'Please enter a valid email address' }
  ],
  password: [
    { required: true, message: 'Password is required' }
  ]
};

const handleSubmit = async () => {
  clearErrors();
  generalError.value = '';
  authStore.clearError();

  // Client-side validation
  const isValid = validate(form, validationRules);
  if (!isValid) {
    return;
  }

  isLoading.value = true;

  try {
    await authStore.login(form);
    
    // Redirect based on email verification status
    if (authStore.isEmailVerified) {
      router.push('/chat');
    } else {
      router.push('/verify-email');
    }
    
  } catch (error: any) {
    if (error.status === 422) {
      // Validation errors from server
      setServerErrors(error.errors);
    } else if (error.status === 401) {
      generalError.value = 'Invalid email or password';
    } else {
      generalError.value = error.message || 'An error occurred. Please try again.';
    }
  } finally {
    isLoading.value = false;
  }
};
</script>