import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { User, LoginCredentials, RegisterData, AuthResponse } from '../types/auth';
import { authApi } from '../services/authApi';

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('auth_token'));
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const isAuthenticated = computed(() => !!user.value && !!token.value);
  const isEmailVerified = computed(() => !!user.value && user.value.email_verified_at !== null);

  // Actions
  const login = async (credentials: LoginCredentials): Promise<void> => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await authApi.login(credentials);
      
      user.value = response.user;
      if (response.token) {
        token.value = response.token;
        localStorage.setItem('auth_token', response.token);
      }
    } catch (err: any) {
      error.value = err.message || 'Login failed';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const register = async (data: RegisterData): Promise<void> => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await authApi.register(data);
      
      user.value = response.user;
      if (response.token) {
        token.value = response.token;
        localStorage.setItem('auth_token', response.token);
      }
    } catch (err: any) {
      error.value = err.message || 'Registration failed';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const verifyEmail = async (code: string): Promise<void> => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await authApi.verifyEmail({ code });
      
      if (response.user) {
        user.value = response.user;
      }
    } catch (err: any) {
      error.value = err.message || 'Email verification failed';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const resendVerificationEmail = async (): Promise<void> => {
    isLoading.value = true;
    error.value = null;

    try {
      await authApi.resendVerificationEmail();
    } catch (err: any) {
      error.value = err.message || 'Failed to resend verification email';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const logout = async (): Promise<void> => {
    isLoading.value = true;
    error.value = null;

    try {
      await authApi.logout();
    } catch (err: any) {
      // Continue with logout even if API call fails
      console.error('Logout API error:', err);
    } finally {
      // Clear local state regardless of API response
      user.value = null;
      token.value = null;
      localStorage.removeItem('auth_token');
      isLoading.value = false;
    }
  };

  const fetchUser = async (): Promise<void> => {
    if (!token.value) return;

    isLoading.value = true;
    error.value = null;

    try {
      const response = await authApi.getUser();
      user.value = response.user;
    } catch (err: any) {
      // If token is invalid, clear it
      if (err.status === 401) {
        token.value = null;
        localStorage.removeItem('auth_token');
      }
      error.value = err.message || 'Failed to fetch user';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const clearError = (): void => {
    error.value = null;
  };

  // Initialize auth state on store creation
  const initializeAuth = async (): Promise<void> => {
    if (token.value) {
      try {
        await fetchUser();
      } catch (err) {
        // Token is invalid, clear it
        token.value = null;
        localStorage.removeItem('auth_token');
      }
    }
  };

  return {
    // State
    user,
    token,
    isLoading,
    error,
    
    // Getters
    isAuthenticated,
    isEmailVerified,
    
    // Actions
    login,
    register,
    verifyEmail,
    resendVerificationEmail,
    logout,
    fetchUser,
    clearError,
    initializeAuth
  };
});