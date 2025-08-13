import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../../stores/auth';
import type { User } from '../../types/auth';

// Mock the authApi
vi.mock('../../services/authApi', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    verifyEmail: vi.fn(),
    resendVerificationEmail: vi.fn(),
    logout: vi.fn(),
    getUser: vi.fn(),
    forgotPassword: vi.fn(),
  }
}));

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
vi.stubGlobal('localStorage', localStorageMock);

describe('Authentication Flow Integration', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(null);
  });

  it('should handle complete login flow', async () => {
    const { authApi } = await import('../../services/authApi');
    const authStore = useAuthStore();

    // Mock successful login response
    const mockUser: User = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: '2023-01-01T00:00:00.000Z',
      avatar: 'https://www.gravatar.com/avatar/...',
      last_seen_at: '2023-01-01T00:00:00.000Z',
      created_at: '2023-01-01T00:00:00.000Z',
      updated_at: '2023-01-01T00:00:00.000Z'
    };

    vi.mocked(authApi.login).mockResolvedValue({
      user: mockUser,
      token: 'test-token',
      message: 'Login successful'
    });

    // Initial state
    expect(authStore.isAuthenticated).toBe(false);
    expect(authStore.isEmailVerified).toBe(false);
    expect(authStore.user).toBeNull();

    // Perform login
    await authStore.login({
      email: 'test@example.com',
      password: 'password123'
    });

    // Verify state after login
    expect(authStore.isAuthenticated).toBe(true);
    expect(authStore.isEmailVerified).toBe(true);
    expect(authStore.user).toEqual(mockUser);
    expect(authStore.token).toBe('test-token');
    expect(localStorageMock.setItem).toHaveBeenCalledWith('auth_token', 'test-token');
  });

  it('should handle complete registration and email verification flow', async () => {
    const { authApi } = await import('../../services/authApi');
    const authStore = useAuthStore();

    // Mock registration response (unverified user)
    const unverifiedUser: User = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null,
      avatar: undefined,
      last_seen_at: '2023-01-01T00:00:00.000Z',
      created_at: '2023-01-01T00:00:00.000Z',
      updated_at: '2023-01-01T00:00:00.000Z'
    };

    vi.mocked(authApi.register).mockResolvedValue({
      user: unverifiedUser,
      message: 'Registration successful. Please check your email for verification.'
    });

    // Perform registration
    await authStore.register({
      name: 'Test User',
      email: 'test@example.com',
      password: 'password123',
      password_confirmation: 'password123'
    });

    // Verify state after registration (user exists but not verified)
    expect(authStore.user).toEqual(unverifiedUser);
    expect(authStore.isEmailVerified).toBe(false);

    // Mock email verification response
    const verifiedUser: User = {
      ...unverifiedUser,
      email_verified_at: '2023-01-01T00:00:00.000Z'
    };

    vi.mocked(authApi.verifyEmail).mockResolvedValue({
      user: verifiedUser,
      message: 'Email verified successfully'
    });

    // Perform email verification
    await authStore.verifyEmail('123456');

    // Verify state after email verification
    expect(authStore.user).toEqual(verifiedUser);
    expect(authStore.isEmailVerified).toBe(true);
  });

  it('should handle logout flow', async () => {
    const { authApi } = await import('../../services/authApi');
    const authStore = useAuthStore();

    // Set up authenticated state
    const mockUser: User = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: '2023-01-01T00:00:00.000Z',
      avatar: 'https://www.gravatar.com/avatar/...',
      last_seen_at: '2023-01-01T00:00:00.000Z',
      created_at: '2023-01-01T00:00:00.000Z',
      updated_at: '2023-01-01T00:00:00.000Z'
    };

    authStore.user = mockUser;
    authStore.token = 'test-token';

    expect(authStore.isAuthenticated).toBe(true);

    // Mock logout response
    vi.mocked(authApi.logout).mockResolvedValue({
      message: 'Logged out successfully'
    });

    // Perform logout
    await authStore.logout();

    // Verify state after logout
    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(authStore.isAuthenticated).toBe(false);
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token');
  });

  it('should handle authentication initialization from localStorage', async () => {
    const { authApi } = await import('../../services/authApi');
    
    // Mock token in localStorage
    localStorageMock.getItem.mockReturnValue('stored-token');

    // Mock user fetch response
    const mockUser: User = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: '2023-01-01T00:00:00.000Z',
      avatar: 'https://www.gravatar.com/avatar/...',
      last_seen_at: '2023-01-01T00:00:00.000Z',
      created_at: '2023-01-01T00:00:00.000Z',
      updated_at: '2023-01-01T00:00:00.000Z'
    };

    vi.mocked(authApi.getUser).mockResolvedValue({
      user: mockUser
    });

    // Create store (this should initialize with token from localStorage)
    const authStore = useAuthStore();
    
    expect(authStore.token).toBe('stored-token');

    // Initialize auth state
    await authStore.initializeAuth();

    // Verify state after initialization
    expect(authStore.user).toEqual(mockUser);
    expect(authStore.isAuthenticated).toBe(true);
    expect(authStore.isEmailVerified).toBe(true);
  });

  it('should handle invalid token during initialization', async () => {
    const { authApi } = await import('../../services/authApi');
    
    // Mock token in localStorage
    localStorageMock.getItem.mockReturnValue('invalid-token');

    // Mock 401 error response
    vi.mocked(authApi.getUser).mockRejectedValue({
      message: 'Unauthenticated',
      status: 401
    });

    // Create store
    const authStore = useAuthStore();
    
    expect(authStore.token).toBe('invalid-token');

    // Initialize auth state (should handle error gracefully and clear token)
    await authStore.initializeAuth();

    // Verify token was cleared
    expect(authStore.token).toBeNull();
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token');
  });

  it('should handle error states correctly', async () => {
    const { authApi } = await import('../../services/authApi');
    const authStore = useAuthStore();

    // Mock login error
    vi.mocked(authApi.login).mockRejectedValue({
      message: 'Invalid credentials',
      status: 401
    });

    // Attempt login
    await expect(authStore.login({
      email: 'test@example.com',
      password: 'wrongpassword'
    })).rejects.toThrow();

    // Verify error state
    expect(authStore.error).toBe('Invalid credentials');
    expect(authStore.isAuthenticated).toBe(false);
    expect(authStore.user).toBeNull();

    // Clear error
    authStore.clearError();
    expect(authStore.error).toBeNull();
  });
});